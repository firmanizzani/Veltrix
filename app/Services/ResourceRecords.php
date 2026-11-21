<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\CashierShift;
use App\Models\Employee;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\RecipeVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResourceRecords
{
    public function __construct(
        private ResourceCatalog $catalog,
        private BranchAccess $access,
    ) {}

    public function query(string $key, User $user): Builder
    {
        $definition = $this->catalog->definition($key);
        abort_unless($this->catalog->allowed($user, $definition['read']), 403);
        $query = $definition['model']::with($definition['relations']);
        if ($key === 'branches') {
            $query->withCount('users');
        }
        if ($key === 'recipes') {
            $query->withCount('items');
        }
        if (!$user->isOwner()) {
            match ($key) {
                'branch-prices',
                'inventory',
                'purchases',
                'employees',
                'expenses',
                'orders',
                'shifts'
                    => $query->where('branch_id', $user->branch_id),
                'movements' => $query->whereHas(
                    'inventory',
                    fn($q) => $q->where('branch_id', $user->branch_id),
                ),
                'schedules' => $query->whereHas(
                    'employee',
                    fn($q) => $q->where('branch_id', $user->branch_id),
                ),
                'refunds' => $query->whereHas(
                    'order',
                    fn($q) => $q->where('branch_id', $user->branch_id),
                ),
                'price-requests' => $query->whereHas(
                    'branchProduct',
                    fn($q) => $q->where('branch_id', $user->branch_id),
                ),
                'transfers' => $query->where(
                    fn($q) => $q
                        ->where('source_branch_id', $user->branch_id)
                        ->orWhere('destination_branch_id', $user->branch_id),
                ),
                default => null,
            };
            if ($user->role === Role::Cashier) {
                match ($key) {
                    'orders', 'shifts' => $query->where('cashier_id', $user->id),
                    'refunds' => $query->whereHas(
                        'order',
                        fn($q) => $q->where('cashier_id', $user->id),
                    ),
                    default => null,
                };
            }
        }

        return $query;
    }

    public function save(string $key, User $actor, array $data, ?int $id = null): Model
    {
        $definition = $this->catalog->definition($key);
        abort_unless($this->catalog->allowed($actor, $definition['write']), 403);

        return DB::transaction(function () use ($key, $actor, $data, $id, $definition) {
            $record = $id
                ? $this->query($key, $actor)->lockForUpdate()->findOrFail($id)
                : new ($definition['model'])();
            if (isset($data['branch_id'])) {
                $this->access->require($actor, (int) $data['branch_id']);
            }
            if (isset($data['employee_id'])) {
                $employee = Employee::findOrFail($data['employee_id']);
                $this->access->require($actor, $employee->branch_id);
            }
            if ($key === 'users') {
                abort_if(
                    $record->exists && ($record->isOwner() || $record->id === $actor->id),
                    403,
                );
                if (
                    $record->exists &&
                    CashierShift::where('cashier_id', $record->id)
                        ->whereNull('closed_at')
                        ->exists() &&
                    ((int) $data['branch_id'] !== $record->branch_id ||
                        $data['role'] !== $record->role->value)
                ) {
                    throw ValidationException::withMessages([
                        'branch_id' =>
                            'Tutup shift pengguna sebelum mengubah cabang atau peran.',
                    ]);
                }
                unset($data['password_confirmation']);
                if (empty($data['password'])) {
                    unset($data['password']);
                }
                $data['session_version'] = ($record->session_version ?? 0) + 1;
            }
            if ($key === 'expenses') {
                if ($record->exists && $record->approval_status !== 'pending') {
                    throw ValidationException::withMessages([
                        'record' => 'Pengeluaran final tidak dapat diubah.',
                    ]);
                }
                if (!empty($data['receipt'])) {
                    $data['receipt_path'] = $data['receipt']->store(
                        'expense-receipts',
                        'local',
                    );
                }
                unset($data['receipt']);
                $data['created_by'] = $record->created_by ?? $actor->id;
            }
            if ($key === 'payroll') {
                if ($record->exists && $record->status !== 'draft') {
                    throw ValidationException::withMessages([
                        'record' => 'Payroll yang sudah disetujui tidak dapat diubah.',
                    ]);
                }
                if (substr($data['period'], -2) !== '01') {
                    throw ValidationException::withMessages([
                        'period' => 'Gunakan tanggal pertama bulan payroll.',
                    ]);
                }
                $data['branch_id'] = $employee->branch_id;
                $data['net_pay'] =
                    $data['base_salary'] +
                    $data['allowance'] +
                    $data['bonus'] -
                    $data['deduction'];
                if ($data['net_pay'] < 0) {
                    throw ValidationException::withMessages([
                        'deduction' => 'Potongan melebihi total gaji.',
                    ]);
                }
            }
            if ($key === 'transfers') {
                abort_if($id, 403);
                $this->access->require($actor, (int) $data['source_branch_id']);
                abort_unless(
                    Branch::findOrFail($data['destination_branch_id'])->is_active,
                    422,
                );
                $data['requested_by'] = $actor->id;
            }
            if ($key === 'price-requests') {
                abort_if($id, 403);
                $menu = BranchProduct::findOrFail($data['branch_product_id']);
                $this->access->require($actor, $menu->branch_id);
                $data['requested_by'] = $actor->id;
            }
            if ($key === 'recipes') {
                abort_if($id, 403);
                $product = Product::whereKey($data['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $record = RecipeVersion::create([
                    'product_id' => $product->id,
                    'version' => ($product->recipes()->max('version') ?? 0) + 1,
                ]);
                foreach ($data['items'] as $item) {
                    $record
                        ->items()
                        ->create(
                            collect($item)->only('ingredient_id', 'quantity')->all(),
                        );
                }
            } elseif ($key === 'purchases') {
                abort_if($id, 403);
                $total = 0;
                foreach ($data['items'] as $item) {
                    $total += (int) round($item['quantity'] * $item['unit_price']);
                }
                $record = PurchaseOrder::create([
                    'branch_id' => $data['branch_id'],
                    'supplier_id' => $data['supplier_id'],
                    'number' => 'PO-' . strtoupper(Str::random(10)),
                    'total_amount' => $total,
                ]);
                foreach ($data['items'] as $item) {
                    $record
                        ->items()
                        ->create(
                            collect($item)
                                ->only('ingredient_id', 'quantity', 'unit_price')
                                ->all(),
                        );
                }
            } else {
                $record->fill($data)->save();
            }
            if ($key === 'products' && !$id) {
                foreach (Branch::all() as $branch) {
                    BranchProduct::create([
                        'branch_id' => $branch->id,
                        'product_id' => $record->id,
                        'price' => $record->base_price,
                    ]);
                }
            }
            if ($key === 'branches' && !$id) {
                foreach (Product::all() as $product) {
                    BranchProduct::create([
                        'branch_id' => $record->id,
                        'product_id' => $product->id,
                        'price' => $product->base_price,
                    ]);
                }
            }
            Audit::record(
                $actor,
                $key . ($id ? '.updated' : '.created'),
                $key,
                $record->id,
                collect($record->getChanges())
                    ->except(['password', 'remember_token'])
                    ->all(),
                $record->branch_id,
            );

            return $record;
        });
    }
}
