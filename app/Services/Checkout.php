<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\BranchProduct;
use App\Models\CashierShift;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Checkout
{
    public function __construct(private InventoryLedger $ledger) {}

    public function complete(User $cashier, array $data): Order
    {
        abort_unless(
            $cashier->role === Role::Cashier &&
                $cashier->is_active &&
                $cashier->branch?->is_active,
            403,
        );
        $hash = hash(
            'sha256',
            json_encode(
                collect($data)->except('idempotency_key')->all(),
                JSON_THROW_ON_ERROR,
            ),
        );

        return DB::transaction(function () use ($cashier, $data, $hash) {
            User::whereKey($cashier->id)->lockForUpdate()->firstOrFail();
            $existing = Order::where(
                'idempotency_key',
                $data['idempotency_key'],
            )->first();
            if ($existing) {
                abort_unless($existing->cashier_id === $cashier->id, 403);
                if ($existing->request_hash !== $hash) {
                    throw ValidationException::withMessages([
                        'cart' =>
                            'Kunci pembayaran sudah digunakan untuk pesanan berbeda.',
                    ]);
                }

                return $existing;
            }
            $shift = CashierShift::where('cashier_id', $cashier->id)
                ->whereNull('closed_at')
                ->lockForUpdate()
                ->first();
            if (!$shift || $shift->branch_id !== $cashier->branch_id) {
                throw ValidationException::withMessages([
                    'cart' => 'Buka shift kasir terlebih dahulu.',
                ]);
            }
            $lines = [];
            $consumption = [];
            $total = 0;
            $cost = 0;
            foreach (collect($data['items'])->sortBy('branch_product_id') as $item) {
                $menu = BranchProduct::with('product')
                    ->whereKey($item['branch_product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                abort_unless($menu->branch_id === $cashier->branch_id, 403);
                if (!$menu->is_available || !$menu->product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => $menu->product->name . ' sedang tidak tersedia.',
                    ]);
                }
                if ((int) $menu->price !== (int) $item['expected_price']) {
                    throw ValidationException::withMessages([
                        'cart' =>
                            'Harga ' .
                            $menu->product->name .
                            ' berubah. Muat ulang katalog sebelum membayar.',
                    ]);
                }
                $recipe = $menu->product
                    ->recipes()
                    ->with('items.ingredient')
                    ->latest('version')
                    ->first();
                if (!$recipe || $recipe->items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' =>
                            'Resep ' . $menu->product->name . ' belum dikonfigurasi.',
                    ]);
                }
                $lineConsumption = [];
                foreach ($recipe->items as $ingredient) {
                    $used = round((float) $ingredient->quantity * $item['quantity'], 3);
                    $consumption[$ingredient->ingredient_id] =
                        ($consumption[$ingredient->ingredient_id] ?? 0) + $used;
                    $lineConsumption[$ingredient->ingredient_id] = $used;
                }
                $lineTotal = $menu->price * $item['quantity'];
                $total += $lineTotal;
                $lines[] = [
                    'product_id' => $menu->product_id,
                    'product_name_snapshot' => $menu->product->name,
                    'recipe_version_id' => $recipe->id,
                    'unit_price' => $menu->price,
                    'quantity' => $item['quantity'],
                    'total_price' => $lineTotal,
                    'consumption' => $lineConsumption,
                ];
            }
            $stocks = \App\Models\Inventory::where('branch_id', $cashier->branch_id)
                ->whereIn('ingredient_id', array_keys($consumption))
                ->orderBy('ingredient_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('ingredient_id');
            foreach ($lines as &$line) {
                $lineCost = 0;
                foreach ($line['consumption'] as $ingredientId => $used) {
                    $lineCost +=
                        $used * (float) ($stocks[$ingredientId]->average_unit_cost ?? 0);
                }
                $line['cost_amount'] = (int) round($lineCost);
                $cost += $line['cost_amount'];
                unset($line['consumption']);
            }
            unset($line);
            if ($data['method'] === 'cash' && $data['tendered'] < $total) {
                throw ValidationException::withMessages([
                    'tendered' => 'Uang diterima kurang dari total pembayaran.',
                ]);
            }
            $order = Order::create([
                'order_number' =>
                    'VTX-' . now()->format('ymd') . '-' . strtoupper(Str::random(8)),
                'idempotency_key' => $data['idempotency_key'],
                'request_hash' => $hash,
                'branch_id' => $cashier->branch_id,
                'cashier_id' => $cashier->id,
                'shift_id' => $shift->id,
                'subtotal' => $total,
                'total_amount' => $total,
                'cost_amount' => $cost,
                'customer_name' => $data['customer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'completed_at' => now(),
            ]);
            foreach ($lines as $line) {
                $order->items()->create($line);
            }
            ksort($consumption);
            foreach ($consumption as $ingredientId => $quantity) {
                $this->ledger->move(
                    $cashier,
                    $cashier->branch_id,
                    $ingredientId,
                    -$quantity,
                    'sales',
                    'Konsumsi resep',
                    $order->order_number,
                );
            }
            Payment::create([
                'order_id' => $order->id,
                'method' => $data['method'],
                'amount' => $total,
                'tendered' => $data['method'] === 'cash' ? $data['tendered'] : $total,
                'reference_number' => $data['reference_number'] ?? null,
                'paid_at' => now(),
            ]);
            Audit::record($cashier, 'sale.completed', 'order', $order->id, [
                'total_amount' => $total,
            ]);

            return $order;
        }, 3);
    }
}
