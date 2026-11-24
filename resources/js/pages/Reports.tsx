import { Head, router, usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Empty, PageTitle } from '../components/common';
import { FinancialBreakdown } from '../components/financial-summary';
import { BranchComparison, ReportDetails } from '../components/report-details';
import { Button } from '../components/ui/button';
import { date, labels, money, number, type Row, type Shared } from '../lib/utils';

export default function Reports({
    report,
    filters,
    branches,
}: {
    report: Row;
    filters: Row;
    branches: Row[];
}) {
    const { auth } = usePage<Shared>().props;
    const owner = auth.user.role === 'owner';
    const [range, setRange] = useState(filters);
    const query = new URLSearchParams(filters).toString();
    const previousSales = report.branches.reduce(
        (total: number, branch: Row) => total + Number(branch.previous_sales),
        0,
    );
    const growth =
        previousSales > 0
            ? ((report.metrics.net - previousSales) / previousSales) * 100
            : null;

    function apply(event: FormEvent) {
        event.preventDefault();
        router.get('/reports', range, { preserveState: true });
    }

    return (
        <>
            <Head title="Laporan bisnis" />
            <PageTitle
                title="Laporan bisnis"
                description="Analisis keuangan, pertumbuhan, dan kontribusi penjualan pada periode terpilih."
                action={
                    <div className="page-actions">
                        <Button
                            asChild
                            variant="outline"
                        >
                            <a href={`/reports/export?${query}`}>
                                <Download size={16} />
                                Ekspor CSV
                            </a>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                        >
                            <a href={`/reports/pdf?${query}`}>
                                <Download size={16} />
                                Ekspor PDF
                            </a>
                        </Button>
                    </div>
                }
            />
            <form
                className="analysis-filters"
                onSubmit={apply}
            >
                <label>
                    Tanggal mulai
                    <input
                        type="date"
                        value={range.start}
                        onChange={(event) =>
                            setRange({ ...range, start: event.target.value })
                        }
                        required
                    />
                </label>
                <label>
                    Tanggal selesai
                    <input
                        type="date"
                        value={range.end}
                        onChange={(event) =>
                            setRange({ ...range, end: event.target.value })
                        }
                        required
                    />
                </label>
                {owner && (
                    <label>
                        Cakupan cabang
                        <select
                            value={range.branch_id}
                            onChange={(event) =>
                                setRange({ ...range, branch_id: event.target.value })
                            }
                        >
                            <option value="">Seluruh cabang</option>
                            {branches.map((branch) => (
                                <option
                                    key={branch.id}
                                    value={branch.id}
                                >
                                    {branch.city}
                                </option>
                            ))}
                        </select>
                    </label>
                )}
                <Button
                    type="submit"
                    variant="outline"
                >
                    Tampilkan laporan
                </Button>
            </form>
            <nav
                className="analysis-index"
                aria-label="Bagian laporan"
            >
                <a href="#report-finance">Keuangan</a>
                <a href="#report-branches">Perbandingan cabang</a>
                <a href="#report-sales">Penjualan</a>
                <a href="#report-expenses">Pengeluaran</a>
                <a href="#report-inventory">Persediaan</a>
            </nav>
            <div className="analysis-opening">
                <section
                    id="report-finance"
                    className="analysis-ledger"
                >
                    <div className="section-heading">
                        <div>
                            <h2>Laporan laba & biaya</h2>
                            <p>
                                {date(filters.start)} — {date(filters.end)}
                            </p>
                        </div>
                    </div>
                    <FinancialBreakdown
                        metrics={report.metrics}
                        owner={owner}
                    />
                </section>
                <aside
                    className="analysis-context"
                    aria-label="Konteks periode laporan"
                >
                    <h2>Perubahan penjualan bersih</h2>
                    <strong className="analysis-growth">
                        {growth === null
                            ? 'Belum ada pembanding'
                            : `${growth > 0 ? '+' : ''}${number(Math.round(growth * 10) / 10)}%`}
                    </strong>
                    <p>
                        Dibandingkan {date(report.comparison.start)} —{' '}
                        {date(report.comparison.end)}, dengan durasi yang sama.
                        {growth === null &&
                            ' Persentase memerlukan penjualan pembanding positif.'}
                    </p>
                    <dl className="analysis-facts">
                        <div>
                            <dt>Penjualan periode sebelumnya</dt>
                            <dd>{money(previousSales)}</dd>
                        </div>
                        <div>
                            <dt>Penjualan periode ini</dt>
                            <dd>{money(report.metrics.net)}</dd>
                        </div>
                        <div>
                            <dt>Transaksi</dt>
                            <dd>{number(report.metrics.transactions)}</dd>
                        </div>
                        <div>
                            <dt>Rata-rata transaksi</dt>
                            <dd>{money(report.metrics.average)}</dd>
                        </div>
                        <div>
                            <dt>Produk bersih terjual</dt>
                            <dd>{number(report.metrics.units)}</dd>
                        </div>
                    </dl>
                    <h3>Komposisi pembayaran kotor</h3>
                    {report.payments.length ? (
                        report.payments.map((payment: Row) => (
                            <div
                                className="key-value"
                                key={payment.name}
                            >
                                <span>{labels[payment.name] ?? payment.name}</span>
                                <strong>{money(payment.amount)}</strong>
                            </div>
                        ))
                    ) : (
                        <p>Belum ada pembayaran pada periode ini.</p>
                    )}
                </aside>
            </div>
            <BranchComparison
                report={report}
                owner={owner}
            />
            <section
                id="report-sales"
                className="analysis-sales"
            >
                <div className="section-heading">
                    <div>
                        <h2>Rincian penjualan</h2>
                        <p>
                            Rekap per {report.granularity === 'hour' ? 'jam' : 'hari'} dan
                            kontribusi menu terlaris.
                        </p>
                    </div>
                </div>
                <div className="analysis-tables">
                    <div className="analysis-periods table-scroll">
                        <table>
                            <caption>
                                Penjualan bersih per{' '}
                                {report.granularity === 'hour' ? 'jam' : 'hari'}
                            </caption>
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th className="num">Penjualan bersih</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.trend.map((entry: Row) => (
                                    <tr key={entry.date}>
                                        <td>{date(entry.date)}</td>
                                        <td className="num">{money(entry.amount)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="table-scroll">
                        <table>
                            <caption>Menu terlaris pada periode ini</caption>
                            <thead>
                                <tr>
                                    <th>Menu</th>
                                    <th className="num">Unit bersih</th>
                                    <th className="num">Penjualan bersih</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.topProducts.map((product: Row) => (
                                    <tr key={product.product_id}>
                                        <td>{product.name}</td>
                                        <td className="num">
                                            {number(product.quantity)}
                                        </td>
                                        <td className="num">{money(product.amount)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {!report.topProducts.length && (
                            <Empty>Belum ada menu terjual pada periode ini.</Empty>
                        )}
                    </div>
                </div>
            </section>
            <ReportDetails
                report={report}
                owner={owner}
            />
        </>
    );
}
