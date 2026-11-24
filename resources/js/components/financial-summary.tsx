import { money, number, type Row } from '../lib/utils';

export function FinancialSummary({ metrics, owner }: { metrics: Row; owner: boolean }) {
    const stats = [
        {
            name: 'Penjualan bersih',
            value: money(metrics.net),
            note: 'Setelah pengurangan refund',
        },
        {
            name: 'Transaksi',
            value: number(metrics.transactions),
            note: `${number(metrics.units)} produk bersih terjual`,
        },
        {
            name: owner ? 'Estimasi laba operasional' : 'Estimasi laba kotor',
            value: money(owner ? metrics.profit : metrics.gross_profit),
            note: owner ? 'Setelah seluruh biaya tercatat' : 'Setelah biaya bahan',
        },
    ];
    return (
        <section aria-label="Ringkasan kinerja">
            <div className="kpi-strip">
                {stats.map((stat) => (
                    <div
                        className="kpi"
                        key={stat.name}
                    >
                        <span>{stat.name}</span>
                        <strong>{stat.value}</strong>
                        <p>{stat.note}</p>
                    </div>
                ))}
            </div>
            <dl className="supporting-metrics">
                <div>
                    <dt>Rata-rata transaksi</dt>
                    <dd>{money(metrics.average)}</dd>
                </div>
                <div>
                    <dt>Produk bersih terjual</dt>
                    <dd>{number(metrics.units)}</dd>
                </div>
                <div>
                    <dt>{owner ? 'Cabang aktif' : 'Biaya operasional disetujui'}</dt>
                    <dd>
                        {owner
                            ? number(metrics.active_branches)
                            : money(metrics.expenses)}
                    </dd>
                </div>
            </dl>
        </section>
    );
}

export function FinancialBreakdown({ metrics, owner }: { metrics: Row; owner: boolean }) {
    const calculation = [
        { label: 'Penjualan bersih', amount: metrics.net },
        { label: 'Dikurangi biaya bahan', amount: -metrics.cogs },
        { label: 'Estimasi laba kotor', amount: metrics.gross_profit, subtotal: true },
        ...(owner
            ? [
                  { label: 'Dikurangi biaya operasional', amount: -metrics.expenses },
                  { label: 'Dikurangi payroll', amount: -metrics.payroll },
                  {
                      label: 'Estimasi laba operasional',
                      amount: metrics.profit,
                      subtotal: true,
                  },
              ]
            : []),
    ];
    const noRecognizedCosts =
        Number(metrics.expenses) === 0 && Number(metrics.payroll) === 0;

    return (
        <div className="profit-breakdown">
            <dl className="profit-calculation">
                {calculation.map((item) => (
                    <div
                        key={item.label}
                        className={item.subtotal ? 'profit-subtotal' : undefined}
                    >
                        <dt>{item.label}</dt>
                        <dd>{money(Number(item.amount) || 0)}</dd>
                    </div>
                ))}
            </dl>
            <div className="profit-explanation">
                <p>
                    Penjualan kotor {money(metrics.gross)} − refund{' '}
                    {money(metrics.refunds)} = penjualan bersih {money(metrics.net)}.
                </p>
                {owner && (
                    <p className="profit-cost-note">
                        {noRecognizedCosts
                            ? 'Biaya operasional dan payroll tercatat Rp0. Laba operasional sama dengan laba kotor.'
                            : 'Biaya operasional dihitung dari pengeluaran disetujui; payroll dari periode yang disetujui atau dibayar.'}
                    </p>
                )}
                {!owner && (
                    <p>Pengeluaran operasional belum dikurangkan dari laba kotor.</p>
                )}
                <p>
                    Estimasi belum mencakup pajak, bunga, dan depresiasi. Biaya bahan
                    mengikuti catatan penjualan dan tidak kembali saat refund.
                    {owner && ' Payroll diakui pada awal periode bulanan.'}
                </p>
            </div>
        </div>
    );
}
