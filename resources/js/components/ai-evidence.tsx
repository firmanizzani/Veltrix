import { money } from '../lib/utils';

export type Evidence = {
    metric: string;
    period: { start: string; end: string };
    branch: string;
    data: Record<string, unknown> | Record<string, unknown>[];
    notes: string;
    totals?: Record<string, unknown> | null;
    truncated?: boolean;
    comparison?: {
        period: { start: string; end: string };
        totals: Record<string, unknown>;
        changes: Record<string, unknown>;
        rows: Record<string, unknown>[];
        complete: boolean;
    };
};
const evidenceLabels: Record<string, string> = {
    gross: 'Penjualan kotor',
    net: 'Penjualan bersih',
    refunds: 'Refund',
    transactions: 'Transaksi',
    average: 'Rata-rata transaksi',
    units: 'Produk terjual',
    cogs: 'Biaya bahan',
    gross_profit: 'Estimasi laba kotor',
    expenses: 'Pengeluaran disetujui',
    payroll: 'Payroll',
    profit: 'Estimasi laba operasional',
    active_branches: 'Cabang aktif',
    title: 'Temuan',
    summary: 'Ringkasan',
    recommendation: 'Saran pemeriksaan',
    name: 'Nama',
    quantity: 'Jumlah',
    product_name: 'Produk',
    sku: 'SKU',
    sold_quantity: 'Unit terjual',
    refunded_quantity: 'Unit direfund',
    gross_amount: 'Penjualan sebelum refund',
    refund_amount: 'Nilai refund',
    previous_quantity: 'Jumlah periode pembanding',
    quantity_change: 'Perubahan jumlah',
    quantity_change_percent: 'Perubahan jumlah (%)',
    percentage_note: 'Batas perhitungan persentase',
    previous_amount: 'Penjualan periode pembanding',
    amount_change: 'Perubahan penjualan',
    amount_change_percent: 'Perubahan penjualan (%)',
    amount: 'Nilai',
    branch: 'Cabang',
    ingredient: 'Bahan',
    unit: 'Satuan',
    value: 'Nilai stok',
    date: 'Tanggal',
    current_units: 'Jumlah periode ini',
    previous_units: 'Jumlah pembanding',
    minimum: 'Stok minimum',
    daily_usage: 'Pemakaian rata-rata per hari',
    estimated_days: 'Estimasi cakupan (hari)',
    observed_at: 'Waktu pemeriksaan',
    assumption: 'Asumsi',
    change_percent: 'Perubahan (%)',
};
const currencyFields = new Set([
    'gross',
    'net',
    'refunds',
    'average',
    'cogs',
    'gross_profit',
    'expenses',
    'payroll',
    'profit',
    'amount',
    'value',
    'gross_amount',
    'refund_amount',
    'previous_amount',
    'amount_change',
]);
export function EvidenceRows({ data }: { data: Record<string, unknown> }) {
    return (
        <dl className="intelligence-evidence-rows">
            {Object.entries(data)
                .filter(
                    ([key, value]) =>
                        key in evidenceLabels &&
                        (typeof value === 'number' || typeof value === 'string'),
                )
                .map(([key, value]) => (
                    <div key={key}>
                        <dt>{evidenceLabels[key]}</dt>
                        <dd>
                            {currencyFields.has(key)
                                ? money(Number(value))
                                : String(value)}
                        </dd>
                    </div>
                ))}
        </dl>
    );
}

export function MessageEvidence({ sources }: { sources: Evidence[] }) {
    return (
        <details className="intelligence-evidence">
            <summary>Lihat data pendukung ({sources.length})</summary>
            {sources.map((source, index) => (
                <section key={index}>
                    <h3>{source.branch}</h3>
                    <p>
                        {source.period.start} — {source.period.end} · IDR
                    </p>
                    {Array.isArray(source.data) ? (
                        source.data.slice(0, 8).map((row, rowIndex) => (
                            <EvidenceRows
                                key={rowIndex}
                                data={row}
                            />
                        ))
                    ) : (
                        <EvidenceRows data={source.data} />
                    )}
                    {Array.isArray(source.data) && source.data.length > 8 && (
                        <p>
                            Menampilkan 8 dari {source.data.length} rincian pada bukti
                            ini.
                        </p>
                    )}
                    {source.totals && (
                        <>
                            <h4>Total seluruh hasil yang cocok</h4>
                            <EvidenceRows data={source.totals} />
                        </>
                    )}
                    {source.comparison && (
                        <>
                            <h4>
                                Pembanding: {source.comparison.period.start} —{' '}
                                {source.comparison.period.end}
                            </h4>
                            <EvidenceRows data={source.comparison.totals} />
                            <EvidenceRows data={source.comparison.changes} />
                            {source.comparison.rows.slice(0, 8).map((row, rowIndex) => (
                                <EvidenceRows
                                    key={rowIndex}
                                    data={row}
                                />
                            ))}
                        </>
                    )}
                    {source.truncated && (
                        <p>
                            Rincian dibatasi. Persempit produk atau periode untuk
                            memeriksa keseluruhan rincian.
                        </p>
                    )}
                    <p>{source.notes}</p>
                </section>
            ))}
        </details>
    );
}
