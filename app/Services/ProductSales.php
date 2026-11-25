<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ProductSales
{
    private const SUMS = 'COALESCE(SUM(sold_quantity), 0) AS sold_quantity,
        COALESCE(SUM(refunded_quantity), 0) AS refunded_quantity,
        COALESCE(SUM(sold_quantity - refunded_quantity), 0) AS quantity,
        COALESCE(SUM(gross_amount), 0) AS gross_amount,
        COALESCE(SUM(refund_amount), 0) AS refund_amount,
        COALESCE(SUM(gross_amount - refund_amount), 0) AS amount';

    /**
     * @param array{
     *     start: string,
     *     end: string,
     *     category_id?: int|string|null,
     *     search?: string|null,
     *     sort?: string
     * } $filters
     */
    public function listing(array $filters, ?int $branchId): array
    {
        $query = $this->catalogQuery($filters, $branchId);
        $totals = (array) DB::query()
            ->fromSub((clone $query)->reorder(), 'menu_sales')
            ->selectRaw('COUNT(*) AS menus, ' . self::SUMS)
            ->first();
        return [
            'totals' => array_map(intval(...), $totals),
            'products' => $query
                ->paginate(25)
                ->withQueryString()
                ->through(fn($row) => $this->menuRow($row)),
        ];
    }

    /**
     * @param array{
     *     start: string,
     *     end: string,
     *     category_id?: int|string|null,
     *     search?: string|null,
     *     sort?: string
     * } $filters
     */
    public function export(array $filters, ?int $branchId): iterable
    {
        foreach ($this->catalogQuery($filters, $branchId)->cursor() as $row) {
            yield $this->menuRow($row);
        }
    }

    private function catalogQuery(array $filters, ?int $branchId): Builder
    {
        $aggregates = DB::query()
            ->fromSub(
                $this->facts($filters['start'], $filters['end'], $branchId),
                'facts',
            )
            ->select('product_id')
            ->selectRaw(self::SUMS)
            ->groupBy('product_id');
        $query = DB::table('products')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoinSub($aggregates, 'sales', 'sales.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.is_active',
                'categories.name as category',
            )
            ->when(
                $filters['category_id'] ?? null,
                fn($query, $category) => $query->where('products.category_id', $category),
            );
        foreach (
            [
                'sold_quantity',
                'refunded_quantity',
                'quantity',
                'gross_amount',
                'refund_amount',
                'amount',
            ]
            as $field
        ) {
            $query->selectRaw("COALESCE(sales.$field, 0) AS $field");
        }
        if (filled($filters['search'] ?? null)) {
            $pattern =
                '%' .
                str_replace(
                    ['!', '%', '_'],
                    ['!!', '!%', '!_'],
                    mb_strtolower(trim($filters['search'])),
                ) .
                '%';
            $query->where(
                fn($query) => $query
                    ->whereRaw("LOWER(products.name) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(products.sku) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereIn(
                        'products.id',
                        DB::table('order_items')
                            ->select('product_id')
                            ->whereRaw("LOWER(product_name_snapshot) LIKE ? ESCAPE '!'", [
                                $pattern,
                            ]),
                    ),
            );
        }
        match ($filters['sort'] ?? 'quantity_desc') {
            'name_asc' => $query->orderBy('products.name'),
            'amount_desc' => $query->orderByDesc('amount'),
            default => $query->orderByDesc('quantity'),
        };
        return $query->orderBy('products.id');
    }

    private function menuRow(object $row): array
    {
        $result = [
            'id' => (int) $row->id,
            'name' => $row->name,
            'sku' => $row->sku,
            'category' => $row->category,
            'is_active' => (bool) $row->is_active,
        ];
        foreach (
            [
                'sold_quantity',
                'refunded_quantity',
                'quantity',
                'gross_amount',
                'refund_amount',
                'amount',
            ]
            as $field
        ) {
            $result[$field] = (int) $row->$field;
        }
        return $result;
    }

    public function report(
        string $start,
        string $end,
        ?int $branchId,
        ?int $productId,
        ?string $search,
        string $group,
        bool $comparePrevious = false,
    ): array {
        $products = Product::query()->when(
            $productId,
            fn($query) => $query->whereKey($productId),
        );
        if (!$productId && filled($search)) {
            $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $search)));
            $pattern =
                '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $normalized) . '%';
            $products->where(function ($query) use ($pattern) {
                $query
                    ->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("LOWER(sku) LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereIn(
                        'id',
                        DB::table('order_items')
                            ->select('product_id')
                            ->whereRaw("LOWER(product_name_snapshot) LIKE ? ESCAPE '!'", [
                                $pattern,
                            ]),
                    );
            });
        }
        $matchCount = (clone $products)->count();
        $matches = $products
            ->orderBy('id')
            ->limit(25)
            ->get(['id', 'name', 'sku']);
        if ($matchCount === 0 || $matchCount > 25) {
            return [
                'status' =>
                    $matchCount === 0 ? 'product_not_found' : 'refine_product_filter',
                'products' => $matches->toArray(),
                'matched_products' => $matchCount,
                'rows' => [],
                'totals' => null,
                'truncated' => $matchCount > 25,
                'notes' =>
                    'Produk tidak ditemukan bukan berarti penjualan nol. Perjelas nama atau SKU; jangan menebak.',
            ];
        }
        $ids = $matches->modelKeys();
        $from = CarbonImmutable::parse($start)->startOfDay();
        $until = CarbonImmutable::parse($end)->endOfDay();
        $facts = $this->facts($start, $end, $branchId, $ids);
        $totals = (array) DB::query()
            ->fromSub(clone $facts, 'product_facts')
            ->selectRaw(self::SUMS)
            ->first();
        $totals = array_map(intval(...), $totals);
        $query = DB::query()
            ->fromSub($facts, 'product_facts')
            ->select('product_id')
            ->selectRaw(self::SUMS)
            ->groupBy('product_id');
        if ($group === 'branch') {
            $query->addSelect('branch_id')->groupBy('branch_id');
        } elseif ($group === 'day') {
            $query->addSelect('day')->groupBy('day');
        }
        $aggregates = $query->get()->keyBy(
            fn($row) => $row->product_id .
                ':' .
                match ($group) {
                    'branch' => $row->branch_id,
                    'day' => $row->day,
                    default => '',
                },
        );
        $branches = Branch::when($branchId, fn($query) => $query->whereKey($branchId))
            ->orderBy('id')
            ->get(['id', 'name']);
        $dimensions = match ($group) {
            'branch' => $branches->pluck('name', 'id')->all(),
            'day' => collect($from->daysUntil($until))
                ->mapWithKeys(fn($day) => [$day->toDateString() => $day->toDateString()])
                ->all(),
            default => ['' => ''],
        };
        $rows = [];
        foreach ($matches as $product) {
            foreach ($dimensions as $key => $label) {
                $aggregate = $aggregates[$product->id . ':' . $key] ?? null;
                $row = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                ];
                if ($group === 'branch') {
                    $row += ['branch_id' => $key, 'branch' => $label];
                } elseif ($group === 'day') {
                    $row['date'] = $key;
                }
                foreach (array_keys($totals) as $field) {
                    $row[$field] = (int) ($aggregate->$field ?? 0);
                }
                $rows[] = $row;
                if (count($rows) > 200) {
                    break 2;
                }
            }
        }
        $report = [
            'status' => 'ok',
            'products' => $matches->toArray(),
            'matched_products' => $matchCount,
            'group_by' => $group,
            'rows' => array_slice($rows, 0, 200),
            'totals' => $totals,
            'truncated' => count($rows) > 200,
            'notes' =>
                'Semua produk yang cocok diperiksa, bukan hanya lima terlaris. ' .
                'Jumlah bersih = terjual pada periode ini dikurangi refund disetujui yang diproses pada periode ini. ' .
                'Cabang tanpa transaksi tetap ditampilkan sebagai nol. Nama produk mengikuti katalog saat ini. ' .
                'Jika beberapa produk cocok, jelaskan rinciannya atau minta pilihan; jangan menganggap satu produk. ' .
                'Jika truncated=true, persempit filter sebelum menyimpulkan seluruh rincian.',
        ];
        if ($comparePrevious) {
            $days = (int) $from->diffInDays($until->startOfDay()) + 1;
            $previousStart = $from->subDays($days)->toDateString();
            $previousEnd = $from->subDay()->toDateString();
            $previous = $this->report(
                $previousStart,
                $previousEnd,
                $branchId,
                $productId,
                $search,
                $group,
            );
            $comparisonRows = [];
            if (!$report['truncated'] && !$previous['truncated']) {
                $previousRows = collect($previous['rows'])->keyBy(
                    fn($row) => $this->rowKey($row),
                );
                foreach ($report['rows'] as $row) {
                    $baseline = $previousRows[$this->rowKey($row, $days)] ?? null;
                    if ($baseline !== null) {
                        $comparisonRows[] = [...$row, ...$this->changes($row, $baseline)];
                    }
                }
            }
            $report['comparison'] = [
                'period' => ['start' => $previousStart, 'end' => $previousEnd],
                'basis' =>
                    'Periode sebelumnya dengan jumlah hari yang sama, bukan otomatis satu minggu penuh.',
                'totals' => $previous['totals'],
                'changes' => $this->changes($totals, $previous['totals']),
                'rows' => $comparisonRows,
                'complete' => !$report['truncated'] && !$previous['truncated'],
            ];
        }
        return $report;
    }

    private function facts(
        string $start,
        string $end,
        ?int $branchId,
        ?array $ids = null,
    ): Builder {
        $from = CarbonImmutable::parse($start)->startOfDay();
        $until = CarbonImmutable::parse($end)->endOfDay();
        $sales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when(
                $ids !== null,
                fn($query) => $query->whereIn('order_items.product_id', $ids),
            )
            ->whereBetween('orders.completed_at', [$from, $until])
            ->when($branchId, fn($query) => $query->where('orders.branch_id', $branchId))
            ->selectRaw('order_items.product_id, orders.branch_id, DATE(orders.completed_at) AS day,
                quantity AS sold_quantity, 0 AS refunded_quantity,
                total_price AS gross_amount, 0 AS refund_amount');
        $refunds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('refunds', 'refunds.order_id', '=', 'orders.id')
            ->where('refunds.status', 'approved')
            ->when(
                $ids !== null,
                fn($query) => $query->whereIn('order_items.product_id', $ids),
            )
            ->whereBetween('refunds.processed_at', [$from, $until])
            ->when($branchId, fn($query) => $query->where('orders.branch_id', $branchId))
            ->selectRaw('order_items.product_id, orders.branch_id, DATE(refunds.processed_at) AS day,
                0 AS sold_quantity, quantity AS refunded_quantity,
                0 AS gross_amount, total_price AS refund_amount');
        return $sales->unionAll($refunds);
    }

    private function rowKey(array $row, int $shiftDays = 0): string
    {
        $date = isset($row['date'])
            ? CarbonImmutable::parse($row['date'])->subDays($shiftDays)->toDateString()
            : '';
        return $row['product_id'] . ':' . ($row['branch_id'] ?? '') . ':' . $date;
    }

    private function changes(array $current, array $previous): array
    {
        return [
            'previous_quantity' => $previous['quantity'],
            'quantity_change' => $current['quantity'] - $previous['quantity'],
            'quantity_change_percent' =>
                $previous['quantity'] > 0
                    ? round(($current['quantity'] / $previous['quantity'] - 1) * 100, 1)
                    : null,
            'previous_amount' => $previous['amount'],
            'percentage_note' =>
                'Persentase null berarti tidak dapat dihitung karena nilai pembanding nol/negatif; bukan kenaikan 100%. Gunakan perubahan unit atau rupiah.',
            'amount_change' => $current['amount'] - $previous['amount'],
            'amount_change_percent' =>
                $previous['amount'] > 0
                    ? round(($current['amount'] / $previous['amount'] - 1) * 100, 1)
                    : null,
        ];
    }
}
