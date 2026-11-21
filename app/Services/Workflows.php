<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use App\Models\StockReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Workflows
{
    public function __construct(
        private ResourceRecords $records,
        private InventoryLedger $ledger,
        private BranchAccess $access,
    ) {}

    public function run(
        string $key,
        int $id,
        string $action,
        User $actor,
        array $data,
    ): void {
        DB::transaction(function () use ($key, $id, $action, $actor, $data) {
            $record = $this->records
                ->query($key, $actor)
                ->lockForUpdate()
                ->findOrFail($id);
            if ($key === 'branch-prices' && $action === 'availability') {
                abort_if($actor->role === Role::Cashier, 403);
                $record->update(['is_available' => !$record->is_available]);
            } elseif ($key === 'inventory' && in_array($action, ['adjust', 'count'])) {
                abort_if($actor->role === Role::Cashier, 403);
                $quantity = (float) $data['quantity'];
                if ($action === 'count') {
                    $quantity -= (float) $record->quantity;
                }
                $this->ledger->move(
                    $actor,
                    $record->branch_id,
                    $record->ingredient_id,
                    $quantity,
                    $action === 'count' ? 'stocktake' : 'adjustment',
                    $data['reason'],
                );
            } elseif ($key === 'purchases' && $action === 'receive') {
                $quantities = collect($data['quantities'])
                    ->map(fn($quantity) => (float) $quantity)
                    ->sortKeys();
                $hash = hash('sha256', $quantities->toJson());
                $previous = StockReceipt::where(
                    'idempotency_key',
                    $data['idempotency_key'],
                )->first();
                if ($previous) {
                    if (
                        $previous->purchase_order_id !== $record->id ||
                        $previous->request_hash !== $hash
                    ) {
                        throw ValidationException::withMessages([
                            'quantities' =>
                                'Kunci penerimaan sudah dipakai untuk data lain.',
                        ]);
                    }
                    return;
                }
                abort_unless(in_array($record->status, ['ordered', 'partial']), 422);
                $itemIds = $record->items()->pluck('id');
                if (
                    $quantities->sum() <= 0 ||
                    $quantities->keys()->diff($itemIds)->isNotEmpty()
                ) {
                    throw ValidationException::withMessages([
                        'quantities' =>
                            'Masukkan jumlah diterima untuk bahan pada pesanan ini.',
                    ]);
                }
                $receipt = StockReceipt::create([
                    'idempotency_key' => $data['idempotency_key'],
                    'request_hash' => $hash,
                    'purchase_order_id' => $record->id,
                    'branch_id' => $record->branch_id,
                    'received_by' => $actor->id,
                ]);
                foreach (
                    $record->items()->orderBy('ingredient_id')->lockForUpdate()->get()
                    as $item
                ) {
                    $quantity = (float) ($data['quantities'][$item->id] ?? 0);
                    if ($quantity <= 0) {
                        continue;
                    }
                    if ($quantity > round($item->quantity - $item->received, 3)) {
                        throw ValidationException::withMessages([
                            'quantities' => 'Jumlah diterima melebihi sisa pesanan.',
                        ]);
                    }
                    $this->ledger->move(
                        $actor,
                        $record->branch_id,
                        $item->ingredient_id,
                        $quantity,
                        'receiving',
                        'Penerimaan pembelian',
                        'RCV-' . $receipt->id . ' / ' . $record->number,
                        $item->unit_price,
                    );
                    $item->increment('received', $quantity);
                    $receipt->items()->create([
                        'ingredient_id' => $item->ingredient_id,
                        'quantity' => $quantity,
                        'unit_cost' => $item->unit_price,
                    ]);
                }
                $items = $record->items()->get();
                $record->update([
                    'status' => $items->every(
                        fn($item) => (float) $item->received >= (float) $item->quantity,
                    )
                        ? 'received'
                        : ($items->sum('received') > 0
                            ? 'partial'
                            : 'ordered'),
                ]);
            } elseif ($key === 'purchases' && $action === 'cancel') {
                abort_unless(
                    $record->status === 'ordered' &&
                        (float) $record->items()->sum('received') === 0.0,
                    422,
                );
                $record->update(['status' => 'cancelled']);
            } elseif ($key === 'transfers' && $action === 'ship') {
                abort_unless($actor->isOwner(), 403);
                abort_unless($record->status === 'pending', 422);
                $this->ledger->move(
                    $actor,
                    $record->source_branch_id,
                    $record->ingredient_id,
                    -(float) $record->quantity,
                    'transfer_out',
                    'Pengiriman transfer',
                    'TR-' . $record->id,
                );
                $record->update(['status' => 'shipped']);
            } elseif ($key === 'transfers' && $action === 'receive') {
                $this->access->require($actor, $record->destination_branch_id);
                abort_unless($record->status === 'shipped', 422);
                $this->ledger->move(
                    $actor,
                    $record->destination_branch_id,
                    $record->ingredient_id,
                    (float) $record->quantity,
                    'transfer_in',
                    'Penerimaan transfer',
                    'TR-' . $record->id,
                );
                $record->update(['status' => 'received']);
            } elseif ($key === 'expenses' && in_array($action, ['approve', 'reject'])) {
                abort_unless($actor->isOwner(), 403);
                abort_unless($record->approval_status === 'pending', 422);
                $record->update([
                    'approval_status' => $action === 'approve' ? 'approved' : 'rejected',
                ]);
            } elseif ($key === 'payroll' && in_array($action, ['approve', 'pay'])) {
                abort_unless($actor->isOwner(), 403);
                abort_unless(
                    $record->status === ($action === 'approve' ? 'draft' : 'approved'),
                    422,
                );
                $record->update([
                    'status' => $action === 'approve' ? 'approved' : 'paid',
                ]);
            } elseif (
                $key === 'price-requests' &&
                in_array($action, ['approve', 'reject'])
            ) {
                abort_unless($actor->isOwner(), 403);
                abort_unless($record->status === 'pending', 422);
                if ($action === 'approve') {
                    $record->branchProduct()->update(['price' => $record->price]);
                }
                $record->update([
                    'status' => $action === 'approve' ? 'approved' : 'rejected',
                ]);
            } elseif ($key === 'refunds' && in_array($action, ['approve', 'reject'])) {
                abort_if($actor->role === Role::Cashier, 403);
                abort_unless($record->status === 'pending', 422);
                $record->update([
                    'status' => $action === 'approve' ? 'approved' : 'rejected',
                    'approved_by' => $actor->id,
                    'processed_at' => now(),
                ]);
                if ($action === 'approve') {
                    $record->order()->update(['status' => 'refunded']);
                }
            } else {
                abort(404);
            }
            Audit::record(
                $actor,
                $key . '.' . $action,
                $key,
                $id,
                collect($data)->except('quantities')->all(),
                $record->branch_id,
            );
        }, 3);
    }
}
