<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BusinessInsight;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\OperationalNotification;
use App\Models\User;
use Carbon\CarbonImmutable;

class BusinessInsights
{
    public function __construct(public Analytics $analytics) {}

    public function generate(?string $through = null): int
    {
        $end = CarbonImmutable::parse(
            $through ?? now()->subDay()->toDateString(),
        )->startOfDay();
        $start = $end->subDays(6)->toDateString();
        $previousStart = $end->subDays(13)->toDateString();
        $previousEnd = $end->subDays(7)->toDateString();
        $created = 0;
        $branchResults = [];
        foreach (Branch::where('is_active', true)->get() as $branch) {
            $current = $this->analytics->report(
                $start,
                $end->toDateString(),
                $branch->id,
            );
            $previous = $this->analytics->report(
                $previousStart,
                $previousEnd,
                $branch->id,
            );
            $evidence = [
                'current' => $current['metrics'],
                'previous' => $previous['metrics'],
                'comparison' => ['start' => $previousStart, 'end' => $previousEnd],
                'source' => 'Analisis aturan VELTRIX',
            ];
            $minimum = config('intelligence.minimum_transactions');
            if ($current['metrics']['transactions'] >= $minimum) {
                $branchResults[] = [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'net' => $current['metrics']['net'],
                    'transactions' => $current['metrics']['transactions'],
                ];
            }
            if (
                $current['metrics']['transactions'] >= $minimum &&
                $previous['metrics']['transactions'] >= $minimum &&
                $previous['metrics']['net'] > 0
            ) {
                $change = round(
                    ($current['metrics']['net'] / $previous['metrics']['net'] - 1) * 100,
                    1,
                );
                if (abs($change) >= 10) {
                    $decline = $change < 0;
                    $created += $this->store(
                        $branch->id,
                        $start,
                        $end->toDateString(),
                        $decline ? 'sales_decline' : 'sales_growth',
                        $decline ? 'high' : 'normal',
                        'Penjualan ' .
                            ($decline ? 'turun ' : 'naik ') .
                            abs($change) .
                            '%',
                        'Penjualan bersih dibandingkan tujuh hari sebelumnya dengan volume transaksi yang memenuhi batas minimum.',
                        $decline
                            ? 'Tinjau hari penjualan dan menu yang berubah sebelum menentukan penyebab.'
                            : 'Periksa menu dan jam ramai untuk menjaga kesiapan bahan serta tim.',
                        [...$evidence, 'change_percent' => $change],
                    );
                }
                foreach ($current['topProducts'] as $product) {
                    $baseline = $previous['topProducts']->firstWhere(
                        'product_id',
                        $product->product_id,
                    );
                    if ($baseline && $baseline->quantity >= 10) {
                        $growth = round(
                            ($product->quantity / $baseline->quantity - 1) * 100,
                            1,
                        );
                        if (abs($growth) >= 20) {
                            $created += $this->store(
                                $branch->id,
                                $start,
                                $end->toDateString(),
                                $growth > 0 ? 'product_opportunity' : 'product_trend',
                                'normal',
                                'Perubahan penjualan ' . $product->name,
                                'Jumlah bersih terjual berubah ' .
                                    $growth .
                                    '% dibandingkan tujuh hari sebelumnya.',
                                'Tinjau ketersediaan bahan dan pola pesanan menu ini; perubahan tidak membuktikan penyebab tertentu.',
                                [
                                    'product_id' => $product->product_id,
                                    'current_units' => $product->quantity,
                                    'previous_units' => $baseline->quantity,
                                    'comparison' => $evidence['comparison'],
                                ],
                                (string) $product->product_id,
                            );
                        }
                    }
                }
            }
            if (
                $previous['metrics']['expenses'] >= 100000 &&
                $current['metrics']['expenses'] >= $previous['metrics']['expenses'] * 1.5
            ) {
                $created += $this->store(
                    $branch->id,
                    $start,
                    $end->toDateString(),
                    'expense_anomaly',
                    'high',
                    'Pengeluaran meningkat setidaknya 50%',
                    'Pengeluaran yang disetujui lebih tinggi daripada tujuh hari sebelumnya.',
                    'Periksa kategori pengeluaran dan bukti transaksi sebelum menyimpulkan adanya pemborosan.',
                    $evidence,
                );
            }
        }
        $ranked = collect($branchResults)->sortByDesc('net')->values();
        if (
            $ranked->count() >= 2 &&
            $ranked->last()['net'] > 0 &&
            $ranked->first()['net'] >= $ranked->last()['net'] * 1.2
        ) {
            $created += $this->store(
                $ranked->first()['id'],
                $start,
                $end->toDateString(),
                'branch_comparison',
                'normal',
                'Perbedaan penjualan antar cabang',
                'Penjualan bersih cabang tertinggi setidaknya 20% di atas cabang terendah yang memenuhi batas transaksi.',
                'Bandingkan jam operasional, jumlah transaksi, dan kapasitas cabang sebelum menyimpulkan perbedaan kinerja.',
                ['branches' => $ranked->all()],
            );
        }
        $stocks = Inventory::with('ingredient', 'branch')
            ->whereHas('branch', fn($query) => $query->where('is_active', true))
            ->get();
        foreach ($stocks as $stock) {
            $usage = (float) InventoryMovement::where('inventory_id', $stock->id)
                ->where('type', 'sales')
                ->where('quantity', '<', 0)
                ->whereBetween('created_at', [
                    $end->subDays(6)->startOfDay(),
                    $end->endOfDay(),
                ])
                ->sum('quantity');
            $daily = abs($usage) / 7;
            $days = $daily > 0 ? round((float) $stock->quantity / $daily, 1) : null;
            $low = (float) $stock->quantity <= (float) $stock->minimum;
            if ($low || ($days !== null && $days <= 3)) {
                $created += $this->store(
                    $stock->branch_id,
                    $start,
                    $end->toDateString(),
                    'stock_risk',
                    'high',
                    'Periksa stok ' . $stock->ingredient->name,
                    $low
                        ? 'Persediaan saat diperiksa berada pada atau di bawah batas minimum.'
                        : 'Cakupan stok diperkirakan tidak lebih dari tiga hari jika pemakaian tujuh hari terakhir berlanjut.',
                    'Periksa stok fisik dan pesanan supplier sebelum membuat pembelian.',
                    [
                        'ingredient' => $stock->ingredient->name,
                        'quantity' => (float) $stock->quantity,
                        'unit' => $stock->ingredient->unit,
                        'minimum' => (float) $stock->minimum,
                        'daily_usage' => $daily,
                        'estimated_days' => $days,
                        'observed_at' => now()->toIso8601String(),
                        'assumption' =>
                            'Rata-rata pemakaian penjualan per hari selama tujuh hari; bukan jaminan stok habis.',
                    ],
                    (string) $stock->id,
                );
            }
        }
        return $created;
    }

    private function store(
        int $branchId,
        string $start,
        string $end,
        string $type,
        string $priority,
        string $title,
        string $summary,
        string $recommendation,
        array $evidence,
        string $entity = '',
    ): int {
        $insight = BusinessInsight::firstOrCreate(
            [
                'fingerprint' => hash('sha256', "$branchId:$type:$start:$end:$entity"),
            ],
            compact(
                'start',
                'end',
                'type',
                'priority',
                'title',
                'summary',
                'recommendation',
                'evidence',
            ) + ['branch_id' => $branchId],
        );
        if (!$insight->wasRecentlyCreated) {
            return 0;
        }
        foreach (
            User::where('role', 'owner')->where('is_active', true)->get()
            as $owner
        ) {
            OperationalNotification::firstOrCreate(
                [
                    'user_id' => $owner->id,
                    'event_key' => 'business-insight:' . $insight->id,
                ],
                [
                    'title' => $title,
                    'message' => $summary,
                    'link' => '/intelligence?tab=insights',
                ],
            );
        }
        return 1;
    }
}
