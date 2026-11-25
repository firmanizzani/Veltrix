import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, ClipboardCheck, RefreshCw } from 'lucide-react';
import { SalesTrend } from '../components/sales-trend';
import { useState, type FormEvent } from 'react';
import { Button } from '../components/ui/button';
import { Empty, PageTitle, Status } from '../components/common';
import { FinancialSummary } from '../components/financial-summary';
import { date, labels, money, number, type Row, type Shared } from '../lib/utils';

export default function Dashboard({
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
        router.get('/dashboard', range, { preserveState: true });
    };
    return (
        <div className="dashboard-page">
            <Head title="Dashboard" />
            <PageTitle
                title={
                    user.role === 'owner'
                        ? 'Dashboard'
                        : `Dashboard ${user.branch?.city ?? 'cabang'}`
                }
                description={`${user.role === 'owner' ? 'Pantau ritme seluruh kedai' : 'Pantau operasional kedai Anda'}, dari penjualan hingga persediaan.`}
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
                        <Button
                            asChild
                            variant="outline"
                        >
                            <Link
                                href={`/reports?${new URLSearchParams(filters).toString()}`}
                            >
                                Lihat laporan
                            </Link>
                        </Button>
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
                    aria-label="Muat ulang Dashboard"
                >
                    <RefreshCw size={16} />
                </button>
            </form>
            <FinancialSummary
                metrics={m}
                owner={user.role === 'owner'}
            />
            <div className="dashboard-grid">
                <SalesTrend report={report} />
                <section className="panel branch-panel">
                    <div className="panel-header">
                        <div>
                            <h2>Perlu ditindaklanjuti</h2>
                            <p>Status saat ini, di luar filter tanggal</p>
                        </div>
                        <ClipboardCheck size={18} />
                    </div>
                    <div className="manager-tasks">
                        {(report.tasks ?? []).map((task: Row) => (
                            <Link
                                key={task.href}
                                href={`${task.href}${filters.branch_id ? `?branch_id=${filters.branch_id}` : ''}`}
                            >
                                <span>{task.label}</span>
                                <strong>{task.count}</strong>
                            </Link>
                        ))}
                    </div>
                    <div className="panel-footer">
                        <Link
                            className="text-link"
                            href="/alerts"
                        >
                            Buka notifikasi operasional
                        </Link>
                    </div>
                </section>
                <section className="panel products-panel">
                    <div className="panel-header">
                        <div>
                            <h2>Menu terlaris</h2>
                            <p>3 menu dengan unit bersih terbanyak</p>
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
                            {report.topProducts.slice(0, 3).map((p: Row, i: number) => (
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
                    <div className="panel-footer">
                        <Link
                            href={`/reports/products?${new URLSearchParams({ start: filters.start, end: filters.end, branch_id: String(filters.branch_id ?? '') }).toString()}`}
                            className="text-link"
                        >
                            Lihat semua penjualan menu
                        </Link>
                    </div>
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
                            {report.lowStock.slice(0, 3).map((stock: Row) => (
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
                            {report.recentOrders.slice(0, 3).map((order: Row) => (
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
        </div>
    );
}
