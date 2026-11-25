<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Services\BranchAccess;
use App\Services\ProductSales;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductSalesController extends Controller
{
    public function index(
        Request $request,
        BranchAccess $access,
        ProductSales $sales,
    ): Response {
        [$filters, $branchId] = $this->filters($request, $access);
        $report = $sales->listing($filters, $branchId);
        $productId =
            $request->validate(['product_id' => 'nullable|integer|exists:products,id'])[
                'product_id'
            ] ?? null;
        return Inertia::render('ProductSalesReport', [
            ...$report,
            'filters' => [...$filters, 'branch_id' => $branchId ?? ''],
            'branches' => $request->user()->isOwner()
                ? Branch::orderBy('name')->get(['id', 'name'])
                : [],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'detail' => $productId
                ? [
                    'product' => Product::findOrFail($productId)->only('id', 'name'),
                    ...$sales->report(
                        $filters['start'],
                        $filters['end'],
                        $branchId,
                        $productId,
                        null,
                        'branch',
                    ),
                ]
                : null,
        ]);
    }

    public function export(
        Request $request,
        BranchAccess $access,
        ProductSales $sales,
    ): StreamedResponse {
        [$filters, $branchId] = $this->filters($request, $access);
        $scope = $branchId ? Branch::findOrFail($branchId)->name : 'Seluruh cabang';
        return response()->streamDownload(
            function () use ($filters, $branchId, $scope, $sales) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                $write = function (array $values) use ($stream): void {
                    $safe = array_map(
                        fn($value) => is_string($value) &&
                        preg_match('/^[=+@\-\t\r]/', $value)
                            ? "'" . $value
                            : $value,
                        $values,
                    );
                    fputcsv($stream, $safe, ',', '"', '');
                };
                $write(['Periode', $filters['start'], $filters['end']]);
                $write(['Cabang', $scope]);
                $write([
                    'Menu',
                    'SKU',
                    'Kategori',
                    'Unit terjual',
                    'Unit refund',
                    'Unit bersih',
                    'Penjualan kotor (IDR)',
                    'Refund (IDR)',
                    'Penjualan bersih (IDR)',
                ]);
                foreach ($sales->export($filters, $branchId) as $row) {
                    $write([
                        $row['name'],
                        $row['sku'],
                        $row['category'],
                        $row['sold_quantity'],
                        $row['refunded_quantity'],
                        $row['quantity'],
                        $row['gross_amount'],
                        $row['refund_amount'],
                        $row['amount'],
                    ]);
                }
                fclose($stream);
            },
            'veltrix-menu-sales-' . $filters['start'] . '-' . $filters['end'] . '.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private function filters(Request $request, BranchAccess $access): array
    {
        abort_unless(
            $request->user()->is_active && $request->user()->role !== Role::Cashier,
            403,
        );
        $data = $request->validate([
            'start' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'end' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'category_id' => 'nullable|integer|exists:categories,id',
            'search' => 'nullable|string|max:120',
            'sort' => 'nullable|in:quantity_desc,amount_desc,name_asc',
            'page' => 'nullable|integer|min:1',
        ]);
        $start = $data['start'] ?? now()->startOfMonth()->toDateString();
        $end = $data['end'] ?? now()->toDateString();
        if ($start > $end || CarbonImmutable::parse($start)->diffInDays($end) > 366) {
            throw ValidationException::withMessages([
                'start' => 'Pilih rentang tanggal berurutan, maksimal 366 hari.',
            ]);
        }
        $branchId = $access->selected($request);
        abort_if(!$request->user()->isOwner() && $branchId === null, 403);
        return [
            [
                'start' => $start,
                'end' => $end,
                'category_id' => $data['category_id'] ?? '',
                'search' => $data['search'] ?? '',
                'sort' => $data['sort'] ?? 'quantity_desc',
            ],
            $branchId,
        ];
    }
}
