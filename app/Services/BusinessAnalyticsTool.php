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
    ) {}

    public function name(): string
    {
        return 'business_analytics';
    }

    public function description(): string
    {
        return 'Read authorized coffee-shop business aggregates. Dates are Jakarta dates, ' .
            'maximum 90 days, no future dates. Stock is current, not historical. ' .
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

    /** @param array{metric: string, start?: string, end?: string, branch_id?: int} $arguments */
    public function read(array $arguments): array
    {
        abort_unless($this->user->is_active && $this->user->isOwner(), 403);
        $arguments = Validator::make($arguments, [
            'metric' =>
                'required|in:summary,sales_trend,top_products,stock,expenses,branch_comparison,profitability,insights',
            'start' => 'sometimes|date_format:Y-m-d|before_or_equal:today',
            'end' => 'sometimes|date_format:Y-m-d|before_or_equal:today',
            'branch_id' => 'sometimes|integer|exists:branches,id',
        ])->validate();
        $start = $arguments['start'] ?? $this->start;
        $end = $arguments['end'] ?? $this->end;
        if ($start > $end || CarbonImmutable::parse($start)->diffInDays($end) > 89) {
            throw ValidationException::withMessages([
                'start' => 'Pilih rentang maksimal 90 hari.',
            ]);
        }
        $branchId = $arguments['branch_id'] ?? $this->branchId;
        abort_if($this->branchId !== null && $branchId !== $this->branchId, 403);
        $key = "$start:$end:$branchId";
        $report = $this->reports[$key] ??= app(Analytics::class)->report(
            $start,
            $end,
            $branchId,
        );
        $metric = $arguments['metric'];
        $data = match ($metric) {
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
        $this->evidence[] = $result;
        return $result;
    }
}
