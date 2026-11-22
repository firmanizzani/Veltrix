<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\Refund;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class Analytics
{
    public function report(
        string $start,
        string $end,
        ?int $branchId,
        bool $details = true,
    ): array {
        $from = CarbonImmutable::parse($start)->startOfDay();
        $until = CarbonImmutable::parse($end)->endOfDay();
        $orders = Order::whereBetween('completed_at', [$from, $until])->when(
            $branchId,
            fn($q) => $q->where('orders.branch_id', $branchId),
        );
        $refunds = Refund::where('refunds.status', 'approved')
            ->whereBetween('processed_at', [$from, $until])
            ->when(
                $branchId,
                fn($q) => $q->whereHas(
                    'order',
                    fn($o) => $o->where('branch_id', $branchId),
                ),
            );
        $totals = (clone $orders)
            ->selectRaw(
                'COALESCE(SUM(total_amount),0) AS gross, COALESCE(SUM(cost_amount),0) AS cost, COUNT(*) AS transactions',
            )
            ->first();
        $refundAmount = (int) (clone $refunds)->sum('amount');
        $expenses = Expense::where('approval_status', 'approved')
            ->whereBetween('expense_date', [$start, $end])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));
        $expenseAmount = (int) (clone $expenses)->sum('amount');
        $payrollAmount = (int) Payroll::whereIn('status', ['approved', 'paid'])
            ->whereBetween('period', [$start, $end])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum('net_pay');
        $salesByDay =
            $start !== $end
                ? (clone $orders)
                    ->selectRaw('DATE(completed_at) AS day, SUM(total_amount) AS amount')
                    ->groupByRaw('DATE(completed_at)')
                    ->pluck('amount', 'day')
                : collect();
        $refundsByDay =
            $start !== $end
                ? (clone $refunds)
                    ->selectRaw('DATE(processed_at) AS day, SUM(amount) AS amount')
                    ->groupByRaw('DATE(processed_at)')
                    ->pluck('amount', 'day')
                : collect();
        $trend = [];
        for ($day = $from; $day->lte($until); $day = $day->addDay()) {
            $date = $day->toDateString();
            $trend[] = [
                'date' => $date,
                'amount' =>
                    (int) ($salesByDay[$date] ?? 0) - (int) ($refundsByDay[$date] ?? 0),
            ];
        }
        if ($start === $end) {
            $hourSql =
                DB::getDriverName() === 'pgsql'
                    ? 'EXTRACT(HOUR FROM %s)::int'
                    : "CAST(strftime('%%H', %s) AS INTEGER)";
            $salesByHour = (clone $orders)
                ->selectRaw(
                    sprintf($hourSql, 'completed_at') .
                        ' AS hour, SUM(total_amount) AS amount',
                )
                ->groupByRaw(sprintf($hourSql, 'completed_at'))
                ->pluck('amount', 'hour');
            $refundsByHour = (clone $refunds)
                ->selectRaw(
                    sprintf($hourSql, 'processed_at') . ' AS hour, SUM(amount) AS amount',
                )
                ->groupByRaw(sprintf($hourSql, 'processed_at'))
                ->pluck('amount', 'hour');
            $trend = [];
            foreach (range(0, 23) as $hour) {
                $trend[] = [
                    'date' =>
                        $start .
                        'T' .
                        str_pad((string) $hour, 2, '0', STR_PAD_LEFT) .
                        ':00:00+07:00',
                    'amount' =>
                        (int) ($salesByHour[$hour] ?? 0) -
                        (int) ($refundsByHour[$hour] ?? 0),
                ];
            }
        }
        $top = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.completed_at', [$from, $until])
            ->when($branchId, fn($q) => $q->where('orders.branch_id', $branchId))
            ->selectRaw(
                'product_id, product_name_snapshot AS name, SUM(quantity) AS quantity, SUM(total_price) AS amount',
            )
            ->groupBy('product_id', 'product_name_snapshot')
            ->get();
        $refundedProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('refunds', 'refunds.order_id', '=', 'orders.id')
            ->where('refunds.status', 'approved')
            ->whereBetween('refunds.processed_at', [$from, $until])
            ->when($branchId, fn($q) => $q->where('orders.branch_id', $branchId))
            ->selectRaw(
                'product_id, SUM(quantity) AS quantity, SUM(total_price) AS amount',
            )
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');
        $units = (int) $top->sum('quantity') - (int) $refundedProducts->sum('quantity');
        $top = $top
            ->map(function ($row) use ($refundedProducts) {
                $row->quantity =
                    (int) $row->quantity -
                    (int) ($refundedProducts[$row->product_id]->quantity ?? 0);
                $row->amount =
                    (int) $row->amount -
                    (int) ($refundedProducts[$row->product_id]->amount ?? 0);

                return $row;
            })
            ->sortByDesc('quantity')
            ->take(5)
            ->values();
        $branchSales = (clone $orders)
            ->selectRaw(
                'branch_id, SUM(total_amount) AS amount, SUM(cost_amount) AS cost, COUNT(*) AS transactions',
            )
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $branchRefunds = (clone $refunds)
            ->join('orders', 'orders.id', '=', 'refunds.order_id')
            ->selectRaw('orders.branch_id, SUM(refunds.amount) AS amount')
            ->groupBy('orders.branch_id')
            ->pluck('amount', 'branch_id');
        $days = (int) $from->diffInDays($until->startOfDay()) + 1;
        $previousFrom = $from->subDays($days);
        $previousUntil = $from->subSecond();
        $previousSales = $previousRefunds = $branchUnits = $refundedUnits = $branchExpenses = $branchPayroll = collect();
        if ($details) {
            $previousSales = Order::whereBetween('completed_at', [
                $previousFrom,
                $previousUntil,
            ])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->selectRaw('branch_id, SUM(total_amount) AS amount')
                ->groupBy('branch_id')
                ->pluck('amount', 'branch_id');
            $previousRefunds = Refund::where('refunds.status', 'approved')
                ->whereBetween('processed_at', [$previousFrom, $previousUntil])
                ->join('orders', 'orders.id', '=', 'refunds.order_id')
                ->when($branchId, fn($q) => $q->where('orders.branch_id', $branchId))
                ->selectRaw('orders.branch_id, SUM(refunds.amount) AS amount')
                ->groupBy('orders.branch_id')
                ->pluck('amount', 'branch_id');
            $branchUnits = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereBetween('orders.completed_at', [$from, $until])
                ->when($branchId, fn($q) => $q->where('orders.branch_id', $branchId))
                ->selectRaw('orders.branch_id, SUM(quantity) AS quantity')
                ->groupBy('orders.branch_id')
                ->pluck('quantity', 'branch_id');
            $refundedUnits = (clone $refunds)
                ->join('orders', 'orders.id', '=', 'refunds.order_id')
                ->join('order_items', 'order_items.order_id', '=', 'orders.id')
                ->selectRaw('orders.branch_id, SUM(quantity) AS quantity')
                ->groupBy('orders.branch_id')
                ->pluck('quantity', 'branch_id');
            $branchExpenses = (clone $expenses)
                ->selectRaw('branch_id, SUM(amount) AS amount')
                ->groupBy('branch_id')
                ->pluck('amount', 'branch_id');
            $branchPayroll = Payroll::whereIn('status', ['approved', 'paid'])
                ->whereBetween('period', [$start, $end])
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->selectRaw('branch_id, SUM(net_pay) AS amount')
                ->groupBy('branch_id')
                ->pluck('amount', 'branch_id');
        }
        $branches = Branch::when($branchId, fn($q) => $q->whereKey($branchId))
            ->get()
            ->map(function ($b) use (
                $branchSales,
                $branchRefunds,
                $previousSales,
                $previousRefunds,
                $branchUnits,
                $refundedUnits,
                $branchExpenses,
                $branchPayroll,
                $details,
            ) {
                $amount =
                    (int) ($branchSales[$b->id]->amount ?? 0) -
                    (int) ($branchRefunds[$b->id] ?? 0);
                $count = (int) ($branchSales[$b->id]->transactions ?? 0);
                $summary = [
                    'id' => $b->id,
                    'name' => $b->name,
                    'city' => $b->city,
                    'is_active' => $b->is_active,
                    'amount' => $amount,
                    'transactions' => $count,
                ];
                if (!$details) {
                    return $summary;
                }
                $previous =
                    (int) ($previousSales[$b->id] ?? 0) -
                    (int) ($previousRefunds[$b->id] ?? 0);
                $count = (int) ($branchSales[$b->id]->transactions ?? 0);
                $cost = (int) ($branchSales[$b->id]->cost ?? 0);
                $expense = (int) ($branchExpenses[$b->id] ?? 0);
                $payroll = (int) ($branchPayroll[$b->id] ?? 0);
                return [
                    ...$summary,
                    'average' => $count ? (int) round($amount / $count) : 0,
                    'units' =>
                        (int) ($branchUnits[$b->id] ?? 0) -
                        (int) ($refundedUnits[$b->id] ?? 0),
                    'cogs' => $cost,
                    'expenses' => $expense,
                    'payroll' => $payroll,
                    'gross_profit' => $amount - $cost,
                    'profit' => $amount - $cost - $expense - $payroll,
                    'previous_sales' => $previous,
                    'growth' =>
                        $previous > 0
                            ? round((($amount - $previous) / $previous) * 100, 1)
                            : null,
                ];
            });
        $net = (int) $totals->gross - $refundAmount;
        $payments = $details
            ? (clone $orders)
                ->join('payments', 'payments.order_id', '=', 'orders.id')
                ->selectRaw('payments.method AS name, SUM(payments.amount) AS amount')
                ->groupBy('payments.method')
                ->get()
            : [];
        $lowStock = Inventory::query()
            ->join('ingredients', 'ingredients.id', '=', 'inventories.ingredient_id')
            ->join('branches', 'branches.id', '=', 'inventories.branch_id')
            ->whereColumn('inventories.quantity', '<=', 'inventories.minimum')
            ->when($branchId, fn($q) => $q->where('inventories.branch_id', $branchId))
            ->select([
                'inventories.id',
                'inventories.quantity',
                'ingredients.name as ingredient_name',
                'ingredients.unit as ingredient_unit',
                'branches.city as branch_city',
            ])
            ->orderBy('inventories.quantity')
            ->limit(5)
            ->get()
            ->map(
                fn($stock) => [
                    'id' => $stock->id,
                    'quantity' => $stock->quantity,
                    'ingredient' => [
                        'name' => $stock->ingredient_name,
                        'unit' => $stock->ingredient_unit,
                    ],
                    'branch' => ['city' => $stock->branch_city],
                ],
            );

        return [
            'comparison' => [
                'start' => $previousFrom->toDateString(),
                'end' => $previousUntil->toDateString(),
            ],
            'expenseCategories' => $details
                ? (clone $expenses)
                    ->selectRaw('category AS name, SUM(amount) AS amount')
                    ->groupBy('category')
                    ->orderByDesc('amount')
                    ->get()
                : [],
            'inventory' => $details
                ? Inventory::with('ingredient', 'branch')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->orderBy('branch_id')
                    ->orderBy('ingredient_id')
                    ->get()
                    ->map(
                        fn($stock) => [
                            'id' => $stock->id,
                            'branch' => $stock->branch->city,
                            'ingredient' => $stock->ingredient->name,
                            'unit' => $stock->ingredient->unit,
                            'quantity' => (float) $stock->quantity,
                            'value' => (int) round(
                                $stock->quantity * $stock->average_unit_cost,
                            ),
                        ],
                    )
                : [],
            'movements' => $details
                ? InventoryMovement::whereBetween('created_at', [$from, $until])
                    ->when(
                        $branchId,
                        fn($q) => $q->whereHas(
                            'inventory',
                            fn($q) => $q->where('branch_id', $branchId),
                        ),
                    )
                    ->selectRaw('type, COUNT(*) AS movements')
                    ->groupBy('type')
                    ->orderBy('type')
                    ->get()
                : [],
            'granularity' => $start === $end ? 'hour' : 'day',
            'metrics' => [
                'gross' => (int) $totals->gross,
                'net' => $net,
                'refunds' => $refundAmount,
                'transactions' => (int) $totals->transactions,
                'average' => $totals->transactions
                    ? (int) round($net / $totals->transactions)
                    : 0,
                'units' => $units,
                'cogs' => (int) $totals->cost,
                'gross_profit' => $net - (int) $totals->cost,
                'expenses' => $expenseAmount,
                'payroll' => $payrollAmount,
                'profit' => $net - (int) $totals->cost - $expenseAmount - $payrollAmount,
                'active_branches' => $branches->where('is_active', true)->count(),
            ],
            'trend' => $trend,
            'branches' => $branches,
            'topProducts' => $top,
            'payments' => $payments,
            'lowStock' => $lowStock,
            'recentOrders' => (clone $orders)
                ->join('branches', 'branches.id', '=', 'orders.branch_id')
                ->join('users', 'users.id', '=', 'orders.cashier_id')
                ->leftJoin('payments', 'payments.order_id', '=', 'orders.id')
                ->select([
                    'orders.id',
                    'orders.order_number',
                    'orders.total_amount',
                    'orders.status',
                    'orders.completed_at',
                    'branches.city as branch_city',
                    'users.name as cashier_name',
                    'payments.method as payment_method',
                ])
                ->latest('orders.completed_at')
                ->limit(5)
                ->get()
                ->map(
                    fn($order) => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'completed_at' => $order->completed_at,
                        'branch' => ['city' => $order->branch_city],
                        'cashier' => ['name' => $order->cashier_name],
                        'payment' => ['method' => $order->payment_method],
                    ],
                ),
        ];
    }
}
