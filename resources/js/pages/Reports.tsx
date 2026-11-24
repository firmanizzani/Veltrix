import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, CalendarDays, ClipboardCheck, RefreshCw, Store } from 'lucide-react';
import { SalesTrend } from '../components/sales-trend';
import { useState, type FormEvent } from 'react';
import { Button } from '../components/ui/button';
import { Empty, PageTitle, Status } from '../components/common';
import { ReportDetails } from '../components/report-details';
import { ReportSummary } from '../components/financial-summary';
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
    const {
        auth: { user },
    } = usePage<Shared>().props;
    const [range, setRange] = useState(filters);
    const m = report.metrics;
    const apply = (e: FormEvent) => {
        e.preventDefault();
        router.get('/reports', range, { preserveState: true });
    };
    return (
        <>
            <Head title="Laporan bisnis" />
            <PageTitle
                title="Laporan bisnis"
                description="Penjualan, biaya, dan performa kedai dari catatan operasional."
                action={
                    <>
                        {user.role === 'owner' && (
                            <select
                                aria-label="Filter cabang"
                                form="report-filters"
                                value={range.branch_id}
                                onChange={(e) =>
                                    setRange({ ...range, branch_id: e.target.value })
                                }
                            >
                                <option value="">Seluruh cabang</option>
                                {branches.map((b) => (
                                    <option
                                        value={b.id}
                                        key={b.id}
                                    >
                                        {b.city}
                                    </option>
                                ))}
                            </select>
                        )}

                        <div className="page-actions">
                            <Button
                                variant="outline"
                                asChild
                            >
                                <a
                                    href={`/reports/export?${new URLSearchParams(filters).toString()}`}
                                >
                                    <Download size={16} />
                                    Ekspor CSV
                                </a>
                            </Button>
                            <Button
                                variant="outline"
                                asChild
                            >
                                <a
                                    href={`/reports/pdf?${new URLSearchParams(filters).toString()}`}
                                >
                                    Ekspor PDF
                                </a>
                            </Button>
                        </div>
                    </>
                }
            />
            <form
                id="report-filters"
                className="report-toolbar"
                onSubmit={apply}
            >
                <div className="date-range">
                    <CalendarDays size={16} />
                    <label>
                        <span className="sr-only">Tanggal mulai</span>
                        <input
                            type="date"
                            value={range.start}
                            onChange={(e) =>
                                setRange({ ...range, start: e.target.value })
                            }
                            required
                        />
                    </label>
                    <span>—</span>
                    <label>
                        <span className="sr-only">Tanggal selesai</span>
                        <input
                            type="date"
                            value={range.end}
                            onChange={(e) => setRange({ ...range, end: e.target.value })}
                            required
                        />
                    </label>
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    size="sm"
                >
                    Terapkan
                </Button>
                <button
                    className="refresh-control"
                    type="button"
                    onClick={() => router.reload()}
                    aria-label="Muat ulang laporan"
                >
                    <RefreshCw size={16} />
                </button>
            </form>
            <ReportSummary
                metrics={m}
                owner={user.role === 'owner'}
            />
            <div className="dashboard-grid">
                <SalesTrend report={report} />
                <section className="panel branch-panel">
                    <div className="panel-header">
                        <div>
                            <h2>Performa cabang</h2>
                            <p>{report.branches.length} cabang dalam cakupan</p>
                        </div>
                        <Store size={18} />
                    </div>
                    <div className="branch-ranking">
                        {[...report.branches]
                            .sort((a: Row, b: Row) => b.amount - a.amount)
                            .map((b: Row) => (
                                <div
                                    className="branch-rank"
                                    key={b.id}
                                >
                                    <div>
                                        <span className="branch-initial">
                                            {b.city.slice(0, 2)}
                                        </span>
                                        <div>
                                            <strong>{b.city}</strong>
                                            <p>{number(b.transactions)} transaksi</p>
                                        </div>
                                        <strong className="num">{money(b.amount)}</strong>
                                    </div>
                                    <div className="thin-track">
                                        <span
                                            style={{
                                                width: `${(Math.max(0, b.amount) / Math.max(1, ...report.branches.map((v: Row) => v.amount))) * 100}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                    </div>
                    <div className="panel-footer">
                        {number(m.active_branches)} cabang aktif dalam cakupan
                    </div>
                </section>
                <section className="panel products-panel">
                    <div className="panel-header">
                        <div>
                            <h2>Menu terlaris</h2>
                            <p>Diurutkan dari unit bersih terjual</p>
                        </div>
                        <Link
                            href="/manage/products"
                            className="text-link"
                        >
                            Katalog
                        </Link>
                    </div>
                    {report.topProducts.length ? (
                        <div className="top-products">
                            {report.topProducts.map((p: Row, i: number) => (
                                <div key={p.product_id}>
                                    <span className="ranking-position">{i + 1}</span>
                                    <div>
                                        <strong>{p.name}</strong>
                                        <p>{number(p.quantity)} produk</p>
                                    </div>
                                    <strong className="num">{money(p.amount)}</strong>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <Empty>
                            Menu terlaris akan muncul setelah transaksi tercatat.
                        </Empty>
                    )}
                </section>
                <section className="panel stock-panel">
                    <div className="panel-header">
                        <div>
                            <h2>Perlu perhatian</h2>
                            <p>Persediaan mencapai batas minimum</p>
                        </div>
                        <ClipboardCheck size={18} />
                    </div>
                    {report.lowStock.length ? (
                        <div className="stock-alerts">
                            {report.lowStock.map((stock: Row) => (
                                <div key={stock.id}>
                                    <div>
                                        <strong>{stock.ingredient.name}</strong>
                                        <p>{stock.branch.city}</p>
                                    </div>
                                    <span
                                        className={`stock-quantity ${Number(stock.quantity) === 0 ? 'out-of-stock' : ''}`}
                                    >
                                        {number(stock.quantity)} {stock.ingredient.unit}
                                    </span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <Empty>Tidak ada bahan pada atau di bawah batas minimum.</Empty>
                    )}
                    <div className="panel-footer">
                        <Link
                            href="/manage/inventory"
                            className="text-link"
                        >
                            Periksa persediaan
                        </Link>
                    </div>
                </section>
            </div>
            <section className="finance-report">
                <div className="payment-breakdown">
                    <h3>Komposisi pembayaran kotor</h3>
                    {report.payments.length ? (
                        report.payments.map((p: Row) => (
                            <div
                                className="key-value"
                                key={p.name}
                            >
                                <span>{labels[p.name]}</span>
                                <strong>{money(p.amount)}</strong>
                            </div>
                        ))
                    ) : (
                        <p className="muted">Belum ada pembayaran.</p>
                    )}
                </div>
            </section>
            <section className="recent-section">
                <div className="section-heading">
                    <h2>Transaksi terbaru</h2>
                    <Link
                        href="/manage/orders"
                        className="text-link"
                    >
                        Lihat semua transaksi
                    </Link>
                </div>
                <div className="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaksi</th>
                                <th>Cabang / kasir</th>
                                <th>Waktu</th>
                                <th>Pembayaran</th>
                                <th className="num">Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.recentOrders.map((order: Row) => (
                                <tr key={order.id}>
                                    <td>
                                        <Link
                                            href={`/orders/${order.id}`}
                                            className="identity-link"
                                        >
                                            {order.order_number}
                                        </Link>
                                    </td>
                                    <td>
                                        {order.branch.city}
                                        <small>{order.cashier.name}</small>
                                    </td>
                                    <td>{date(order.completed_at)}</td>
                                    <td>{labels[order.payment?.method]}</td>
                                    <td className="num">{money(order.total_amount)}</td>
                                    <td>
                                        <Status value={order.status} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {!report.recentOrders.length && (
                        <Empty>Belum ada transaksi pada periode terpilih.</Empty>
                    )}
                </div>
            </section>
            <ReportDetails
                report={report}
                owner={user.role === 'owner'}
            />
        </>
    );
}
