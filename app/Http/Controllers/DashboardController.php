<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\Refund;
use App\Services\Analytics;
use App\Services\BranchAccess;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        Analytics $analytics,
        BranchAccess $access,
    ): Response|RedirectResponse {
        if ($request->user()->role === Role::Cashier) {
            abort_if($request->is('reports'), 403);
            return redirect('/pos');
        }
        $filters = $request->validate([
            'start' => 'nullable|date_format:Y-m-d',
            'end' =>
                'nullable|date_format:Y-m-d|after_or_equal:start|before_or_equal:today',
        ]);
        $start =
            $filters['start'] ??
            ($request->user()->isOwner() || $request->is('reports')
                ? now()->startOfMonth()->toDateString()
                : now()->toDateString());
        $end = $filters['end'] ?? now()->toDateString();
        if (Carbon::parse($start)->diffInDays($end) > 366 || $start > $end) {
            throw ValidationException::withMessages([
                'start' => 'Pilih rentang maksimal 366 hari.',
            ]);
        }
        $branchId = $access->selected($request);
        $report = $analytics->report($start, $end, $branchId, $request->is('reports'));
        if (!$request->user()->isOwner()) {
            unset($report['metrics']['payroll'], $report['metrics']['profit']);
            $report['branches'] = $report['branches']->map(
                fn($branch) => collect($branch)
                    ->except(['payroll', 'profit'])
                    ->all(),
            );
        }
        if (!$request->is('reports')) {
            $report['tasks'] = [
                [
                    'label' => 'Pembelian menunggu bahan',
                    'href' => '/manage/purchases',
                    'count' => PurchaseOrder::query()
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->whereIn('status', ['ordered', 'partial'])
                        ->count(),
                ],
                [
                    'label' => 'Refund perlu ditinjau',
                    'href' => '/manage/refunds',
                    'count' => Refund::where('status', 'pending')
                        ->when(
                            $branchId,
                            fn($q) => $q->whereHas(
                                'order',
                                fn($order) => $order->where('branch_id', $branchId),
                            ),
                        )
                        ->count(),
                ],
                [
                    'label' => 'Pengeluaran diajukan',
                    'href' => '/manage/expenses',
                    'count' => \App\Models\Expense::query()
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->where('approval_status', 'pending')
                        ->count(),
                ],
            ];
        }

        return Inertia::render($request->is('reports') ? 'Reports' : 'Dashboard', [
            'report' => $report,
            'filters' => [
                'start' => $start,
                'end' => $end,
                'branch_id' => $branchId ?? '',
            ],
            'branches' => $request->user()->isOwner() ? Branch::all() : [],
        ]);
    }

    public function export(
        Request $request,
        Analytics $analytics,
        BranchAccess $access,
    ): StreamedResponse {
        abort_if($request->user()->role === Role::Cashier, 403);
        $data = $request->validate([
            'start' => 'required|date_format:Y-m-d',
            'end' =>
                'required|date_format:Y-m-d|after_or_equal:start|before_or_equal:today',
        ]);
        abort_if(Carbon::parse($data['start'])->diffInDays($data['end']) > 366, 422);
        $report = $analytics->report(
            $data['start'],
            $data['end'],
            $access->selected($request),
        );
        if (!$request->user()->isOwner()) {
            unset($report['metrics']['payroll'], $report['metrics']['profit']);
            $report['branches'] = $report['branches']->map(
                fn($branch) => collect($branch)
                    ->except(['payroll', 'profit'])
                    ->all(),
            );
        }

        return response()->streamDownload(
            function () use ($report, $data) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ['VELTRIX', $data['start'], $data['end']], ',', '"', '');
                foreach ($report['metrics'] as $key => $value) {
                    fputcsv($handle, [$key, $value], ',', '"', '');
                }
                fputcsv($handle, ['Tanggal', 'Penjualan bersih (IDR)'], ',', '"', '');
                foreach ($report['trend'] as $row) {
                    fputcsv($handle, [$row['date'], $row['amount']], ',', '"', '');
                }
                foreach (
                    ['branches', 'expenseCategories', 'inventory', 'movements']
                    as $section
                ) {
                    fputcsv($handle, [$section], ',', '"', '');
                    $rows = collect($report[$section])->map(
                        fn($row) => $row instanceof
                        \Illuminate\Contracts\Support\Arrayable
                            ? $row->toArray()
                            : (array) $row,
                    );
                    if ($rows->isNotEmpty()) {
                        fputcsv($handle, array_keys($rows->first()), ',', '"', '');
                        foreach ($rows as $row) {
                            $safe = array_map(
                                fn($value) => is_string($value) &&
                                preg_match('/^[=+@\-\t\r]/', $value)
                                    ? "'" . $value
                                    : $value,
                                $row,
                            );
                            fputcsv($handle, $safe, ',', '"', '');
                        }
                    }
                }
                fclose($handle);
            },
            'veltrix-report-' . $data['start'] . '.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function pdf(
        Request $request,
        Analytics $analytics,
        BranchAccess $access,
    ): \Illuminate\Http\Response {
        abort_if($request->user()->role === Role::Cashier, 403);
        $filters = $request->validate([
            'start' => 'required|date_format:Y-m-d',
            'end' =>
                'required|date_format:Y-m-d|after_or_equal:start|before_or_equal:today',
        ]);
        abort_if(
            Carbon::parse($filters['start'])->diffInDays($filters['end']) > 366,
            422,
        );
        $branchId = $access->selected($request);
        $report = $analytics->report($filters['start'], $filters['end'], $branchId);
        if (!$request->user()->isOwner()) {
            unset($report['metrics']['payroll'], $report['metrics']['profit']);
            $report['branches'] = $report['branches']->map(
                fn($branch) => collect($branch)
                    ->except(['payroll', 'profit'])
                    ->all(),
            );
        }
        $pdf = new \Dompdf\Dompdf();
        $pdf->setPaper('A4');
        $pdf->loadHtml(
            view('reports.summary', [
                'report' => $report,
                'filters' => $filters,
                'scope' => $branchId
                    ? Branch::findOrFail($branchId)->name
                    : 'Seluruh cabang',
            ])->render(),
        );
        $pdf->render();
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' =>
                'attachment; filename="veltrix-report-' . $filters['start'] . '.pdf"',
        ]);
    }

    public function alerts(Request $request, BranchAccess $access): Response
    {
        abort_if($request->user()->role === Role::Cashier, 403);
        $branchId = $access->selected($request);

        return Inertia::render('Alerts', [
            'notifications' => \App\Models\OperationalNotification::where(
                'user_id',
                $request->user()->id,
            )
                ->latest('updated_at')
                ->limit(30)
                ->get(),
            'stock' => Inventory::with('ingredient', 'branch')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereColumn('quantity', '<=', 'minimum')
                ->get(),
            'purchases' => PurchaseOrder::with('branch', 'supplier')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereIn('status', ['ordered', 'partial'])
                ->get(),
            'refunds' => Refund::with('order.branch')
                ->whereHas(
                    'order',
                    fn($q) => $q->when(
                        $branchId,
                        fn($q) => $q->where('branch_id', $branchId),
                    ),
                )
                ->where('status', 'pending')
                ->get(),
        ]);
    }
}
