<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BusinessInsight;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class BusinessAnalyticsTool implements Tool
{
    public array $evidence = [];

    private array $reports = [];

    public function __construct(
        public User $user,
        public string $start,
        public string $end,
        public ?int $branchId,
        public bool $lockPeriod = false,
    ) {}

    public function name(): string
    {
        return 'business_analytics';
    }

    public function description(): string
    {
        return 'Read authorized coffee-shop business aggregates. Dates are Jakarta dates, ' .
            'maximum 90 days, no future dates. Stock is current, not historical. ' .
            'For any named product use product_sales with product_query or product_id. ' .
            'Choose group_by branch for per-branch quantities, day for daily quantities, ' .
            'or product for totals. Set compare_previous true for backend-calculated changes ' .
            'against an equally long preceding period. top_products contains only five leaders and cannot answer ' .
            'whether another product sold or ranks lowest. summary includes available branch IDs. ' .
            'Only choose a branch when the conversation covers all branches.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'metric' => $schema
                ->string()
                ->enum([
                    'summary',
                    'sales_trend',
                    'top_products',
                    'product_sales',
                    'stock',
                    'expenses',
                    'branch_comparison',
                    'profitability',
                    'insights',
                ])
                ->required(),
            'start' => $schema->string(),
            'end' => $schema->string(),
            'branch_id' => $schema->integer(),
            'product_id' => $schema->integer(),
            'product_query' => $schema->string(),
            'group_by' => $schema->string()->enum(['product', 'branch', 'day']),
            'compare_previous' => $schema->boolean(),
        ];
    }

    public function handle(Request $request): string
    {
        try {
            return json_encode($this->read($request->all()), JSON_THROW_ON_ERROR);
        } catch (ValidationException $exception) {
            return json_encode(['error' => $exception->errors()], JSON_THROW_ON_ERROR);
        }
    }

    /**
     * @param array{
     *     metric: string,
     *     start?: string,
     *     end?: string,
     *     branch_id?: int,
     *     product_id?: int,
     *     product_query?: string,
     *     group_by?: string,
     *     compare_previous?: bool
     * } $arguments
     */
    public function read(array $arguments): array
    {
        abort_unless($this->user->is_active && $this->user->isOwner(), 403);
        $arguments = Validator::make($arguments, [
            'metric' =>
                'required|in:summary,sales_trend,top_products,product_sales,stock,expenses,branch_comparison,profitability,insights',
            'start' => 'sometimes|date_format:Y-m-d|before_or_equal:today',
            'end' => 'sometimes|date_format:Y-m-d|before_or_equal:today',
            'branch_id' => 'sometimes|integer|exists:branches,id',
            'product_id' => 'sometimes|integer|exists:products,id',
            'product_query' => 'sometimes|string|min:1|max:120',
            'group_by' => 'sometimes|in:product,branch,day',
            'compare_previous' => 'sometimes|boolean',
        ])->validate();
        $start = $this->lockPeriod ? $this->start : $arguments['start'] ?? $this->start;
        $end = $this->lockPeriod ? $this->end : $arguments['end'] ?? $this->end;
        if ($start > $end || CarbonImmutable::parse($start)->diffInDays($end) > 89) {
            throw ValidationException::withMessages([
                'start' => 'Pilih rentang maksimal 90 hari.',
            ]);
        }
        $branchId = $arguments['branch_id'] ?? $this->branchId;
        abort_if($this->branchId !== null && $branchId !== $this->branchId, 403);
        $key = "$start:$end:$branchId";
        $metric = $arguments['metric'];
        $report =
            $metric === 'product_sales'
                ? null
                : ($this->reports[$key] ??= app(Analytics::class)->report(
                    $start,
                    $end,
                    $branchId,
                ));
        $productReport =
            $metric === 'product_sales'
                ? app(ProductSales::class)->report(
                    $start,
                    $end,
                    $branchId,
                    $arguments['product_id'] ?? null,
                    $arguments['product_query'] ?? null,
                    $arguments['group_by'] ?? 'product',
                    (bool) ($arguments['compare_previous'] ?? false),
                )
                : null;
        $data = match ($metric) {
            'product_sales' => $productReport['rows'],
            'summary', 'profitability' => $report['metrics'],
            'sales_trend' => $report['trend'],
            'top_products' => $report['topProducts'],
            'stock' => $report['inventory'],
            'expenses' => $report['expenseCategories'],
            'branch_comparison' => $report['branches'],
            'insights' => BusinessInsight::query()
                ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                ->where('start', '<=', $end)
                ->where('end', '>=', $start)
                ->latest('end')
                ->limit(12)
                ->get([
                    'title',
                    'summary',
                    'recommendation',
                    'start',
                    'end',
                    'evidence',
                ]),
        };
        $result = [
            'metric' => $metric,
            'period' => ['start' => $start, 'end' => $end],
            'branch' => $branchId
                ? Branch::findOrFail($branchId)->name
                : 'Seluruh cabang',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'source' => 'Catatan operasional VELTRIX',
            'notes' =>
                'Laba operasional adalah estimasi setelah biaya bahan, pengeluaran disetujui, dan payroll disetujui/dibayar; bukan laba bersih akuntansi. Stok menunjukkan kondisi saat diperiksa.',
            'data' => $data,
        ];
        if ($metric === 'summary') {
            $result['available_branches'] = Branch::when(
                $branchId,
                fn($query) => $query->whereKey($branchId),
            )
                ->get(['id', 'name', 'city'])
                ->toArray();
        }
        if ($metric === 'top_products') {
            $result['coverage'] =
                'Lima produk terlaris saja; bukan seluruh katalog atau daftar terbawah.';
        }
        if ($productReport !== null) {
            $result = [...$result, ...collect($productReport)->except('rows')->all()];
        }
        $this->evidence[] = $result;
        return $result;
    }
}
