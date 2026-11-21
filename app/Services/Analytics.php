<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\Refund;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class Analytics
{
    public function report(string $start, string $end, ?int $branchId): array
    {
        $from = CarbonImmutable::parse($start)->startOfDay();
        $until = CarbonImmutable::parse($end)->endOfDay();
        $orders = Order::whereBetween('completed_at', [$from, $until])->when(
            $branchId,
            fn($q) => $q->where('branch_id', $branchId),
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
        $salesByDay = (clone $orders)
            ->selectRaw('DATE(completed_at) AS day, SUM(total_amount) AS amount')
            ->groupByRaw('DATE(completed_at)')
            ->pluck('amount', 'day');
        $refundsByDay = (clone $refunds)
            ->selectRaw('DATE(processed_at) AS day, SUM(amount) AS amount')
            ->groupByRaw('DATE(processed_at)')
            ->pluck('amount', 'day');
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
                'branch_id, SUM(total_amount) AS amount, COUNT(*) AS transactions',
            )
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
        $branchRefunds = (clone $refunds)
            ->join('orders', 'orders.id', '=', 'refunds.order_id')
            ->selectRaw('orders.branch_id, SUM(refunds.amount) AS amount')
            ->groupBy('orders.branch_id')
            ->pluck('amount', 'branch_id');
        $branches = Branch::when($branchId, fn($q) => $q->whereKey($branchId))
            ->get()
            ->map(
                fn($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'city' => $b->city,
                    'is_active' => $b->is_active,
                    'amount' =>
                        (int) ($branchSales[$b->id]->amount ?? 0) -
                        (int) ($branchRefunds[$b->id] ?? 0),
                    'transactions' => (int) ($branchSales[$b->id]->transactions ?? 0),
                ],
            );
        $net = (int) $totals->gross - $refundAmount;
        $payments = (clone $orders)
            ->join('payments', 'payments.order_id', '=', 'orders.id')
            ->selectRaw('payments.method AS name, SUM(payments.amount) AS amount')
            ->groupBy('payments.method')
            ->get();
        $lowStock = Inventory::with('ingredient', 'branch')
            ->whereColumn('quantity', '<=', 'minimum')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('quantity')
            ->limit(5)
            ->get();

        return [
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
                ->with('branch', 'payment', 'cashier')
                ->latest('completed_at')
                ->limit(5)
                ->get(),
        ];
    }
}
