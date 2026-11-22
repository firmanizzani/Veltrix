<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ResourceRequest;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Refund;
use App\Services\Audit;
use App\Services\BranchAccess;
use App\Services\ResourceCatalog;
use App\Services\ResourceRecords;
use App\Services\Workflows;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceController extends Controller
{
    public function index(
        Request $request,
        string $resource,
        ResourceCatalog $catalog,
        ResourceRecords $records,
        BranchAccess $access,
    ): Response {
        $definition = $catalog->definition($resource);
        $query = $records->query($resource, $request->user());
        $branchId = $access->selected($request);
        $request->validate([
            'q' => 'nullable|string|max:100',
            'direction' => 'nullable|in:asc,desc',
            'category_id' => 'nullable|integer|exists:categories,id',
            'start' => 'nullable|date_format:Y-m-d',
            'end' => 'nullable|date_format:Y-m-d|after_or_equal:start',
            'category' => 'nullable|string|max:100',
        ]);
        $directColumns = array_filter(
            array_keys($definition['columns']),
            fn($column) => !str_contains($column, '.') &&
                !in_array($column, [
                    'users_count',
                    'items_count',
                    'stock_value',
                    'estimated_cost',
                ]),
        );
        $request->validate([
            'sort' => [
                'nullable',
                \Illuminate\Validation\Rule::in(['id', ...$directColumns]),
            ],
        ]);
        if ($request->user()->isOwner() && $branchId) {
            match ($resource) {
                'branches' => $query->whereKey($branchId),
                'branch-prices',
                'inventory',
                'purchases',
                'receipts',
                'employees',
                'expenses',
                'payroll',
                'orders',
                'shifts'
                    => $query->where('branch_id', $branchId),
                'movements' => $query->whereHas(
                    'inventory',
                    fn($q) => $q->where('branch_id', $branchId),
                ),
                'refunds' => $query->whereHas(
                    'order',
                    fn($q) => $q->where('branch_id', $branchId),
                ),
                'schedules' => $query->whereHas(
                    'employee',
                    fn($q) => $q->where('branch_id', $branchId),
                ),
                default => null,
            };
        }
        if ($resource === 'products' && $request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        $dateColumn = match ($resource) {
            'orders' => 'completed_at',
            'shifts' => 'opened_at',
            'expenses' => 'expense_date',
            'payroll' => 'period',
            'schedules' => 'work_date',
            'purchases', 'receipts', 'movements', 'refunds', 'audit' => 'created_at',
            default => null,
        };
        if ($dateColumn) {
            $dateOnly = in_array($dateColumn, ['expense_date', 'period', 'work_date']);
            if ($request->filled('start')) {
                $query->where(
                    $dateColumn,
                    '>=',
                    $request->input('start') . ($dateOnly ? '' : ' 00:00:00'),
                );
            }
            if ($request->filled('end')) {
                $query->where(
                    $dateColumn,
                    '<=',
                    $request->input('end') . ($dateOnly ? '' : ' 23:59:59.999999'),
                );
            }
        }
        if ($resource === 'expenses' && $request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('q')) {
            $searchable = array_filter(
                array_keys($definition['columns']),
                fn($key) => in_array(\Illuminate\Support\Str::afterLast($key, '.'), [
                    'name',
                    'email',
                    'code',
                    'sku',
                    'number',
                    'order_number',
                    'reason',
                    'description',
                    'action',
                    'category',
                ]),
            );
            if ($searchable) {
                $query->where(function ($q) use ($searchable, $request) {
                    foreach ($searchable as $key) {
                        $value = '%' . $request->string('q') . '%';
                        if (str_contains($key, '.')) {
                            $relation = \Illuminate\Support\Str::beforeLast($key, '.');
                            $column = \Illuminate\Support\Str::afterLast($key, '.');
                            $q->orWhereHas(
                                $relation,
                                fn($related) => $related->whereLike($column, $value),
                            );
                        } else {
                            $q->orWhereLike($key, $value);
                        }
                    }
                });
            }
        }

        $sort = $request->input('sort') ?? 'id';
        if ($sort === 'id') {
            $sort = match ($resource) {
                'orders' => 'completed_at',
                'shifts' => 'opened_at',
                'audit', 'movements', 'receipts' => 'created_at',
                default => 'id',
            };
        }
        return Inertia::render('Records', [
            'definition' => collect($definition)
                ->except(['model', 'relations'])
                ->all(),
            'records' => $query
                ->orderBy($sort, $request->input('direction') ?? 'desc')
                ->paginate(15)
                ->withQueryString(),
            'options' =>
                $request->user()->role === Role::Cashier
                    ? []
                    : $catalog->options($request->user(), $resource),
            'canWrite' => $catalog->allowed($request->user(), $definition['write']),
            'filters' => (object) $request->only(
                'q',
                'sort',
                'direction',
                'category_id',
                'branch_id',
                'start',
                'end',
                'category',
            ),
        ]);
    }

    public function store(
        ResourceRequest $request,
        string $resource,
        ResourceRecords $records,
    ): RedirectResponse {
        return $this->persist($request, $resource, $records);
    }

    public function update(
        ResourceRequest $request,
        string $resource,
        int $record,
        ResourceRecords $records,
    ): RedirectResponse {
        return $this->persist($request, $resource, $records, $record);
    }

    private function persist(
        ResourceRequest $request,
        string $resource,
        ResourceRecords $records,
        ?int $id = null,
    ): RedirectResponse {
        try {
            $records->save($resource, $request->user(), $request->validated(), $id);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'record' => 'Kombinasi data sudah tercatat. Ubah catatan yang sudah ada.',
            ]);
        }

        return back()->with('success', 'Data berhasil disimpan.');
    }

    public function action(
        Request $request,
        string $resource,
        int $record,
        string $action,
        Workflows $workflows,
    ): RedirectResponse {
        $rules = match ($resource . '.' . $action) {
            'inventory.adjust' => [
                'quantity' => 'required|numeric|not_in:0|between:-100000000,100000000',
                'reason' => 'required|string|min:5|max:1000',
            ],
            'inventory.count' => [
                'quantity' => 'required|numeric|min:0|max:100000000',
                'reason' => 'required|string|min:5|max:1000',
            ],
            'purchases.receive' => [
                'idempotency_key' => 'required|uuid',
                'quantities' => 'required|array|min:1',
                'quantities.*' => 'required|numeric|min:0|max:100000000',
            ],
            default => [],
        };
        $workflows->run(
            $resource,
            $record,
            $action,
            $request->user(),
            $request->validate($rules),
        );

        return back()->with('success', 'Perubahan berhasil dicatat.');
    }

    public function requestRefund(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        DB::transaction(function () use ($request, $order, $data) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'completed' || $order->refund()->exists()) {
                throw ValidationException::withMessages([
                    'reason' => 'Transaksi sudah memiliki permintaan refund.',
                ]);
            }
            $refund = Refund::create([
                'order_id' => $order->id,
                'requested_by' => $request->user()->id,
                'amount' => $order->total_amount,
                'reason' => $data['reason'],
            ]);
            Audit::record(
                $request->user(),
                'refund.requested',
                'refund',
                $refund->id,
                $data,
                $order->branch_id,
            );
        });

        return back()->with('success', 'Permintaan refund dikirim untuk persetujuan.');
    }

    public function receipt(Request $request, Expense $expense): BinaryFileResponse
    {
        abort_if($request->user()->role === Role::Cashier, 403);
        abort_unless($request->user()->canAccessBranch($expense->branch_id), 403);
        abort_unless(
            $expense->receipt_path &&
                Storage::disk('local')->exists($expense->receipt_path),
            404,
        );

        return response()->file(Storage::disk('local')->path($expense->receipt_path));
    }
}
