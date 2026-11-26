import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    CalendarDays,
    ChevronRight,
    PackagePlus,
    RefreshCw,
    RotateCcw,
    Wallet,
} from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { SalesTrend } from '../components/sales-trend';
import { Button } from '../components/ui/button';
import { Empty, PageTitle, Status } from '../components/common';
import { FinancialSummary } from '../components/financial-summary';
import { date, labels, money, number, type Row, type Shared } from '../lib/utils';
import '../../css/dashboard.css';

const taskIcons = [PackagePlus, RotateCcw, Wallet];

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
    useEffect(() => setRange(filters), [filters.start, filters.end, filters.branch_id]);
    const owner = user.role === 'owner';
    const pendingTasks = (report.tasks ?? []).reduce(
        (total: number, task: Row) => total + Number(task.count),
        0,
    );
    const branchRanking = [...report.branches].sort(
        (a: Row, b: Row) => Number(b.amount) - Number(a.amount),
    );
    const highestBranchSales = Math.max(
        0,
        ...branchRanking.map((branch: Row) => Number(branch.amount)),
    );
    const highestProductQuantity = Math.max(
        0,
        ...report.topProducts.slice(0, 3).map((product: Row) => Number(product.quantity)),
    );
    const reportQuery = new URLSearchParams(filters).toString();
    const apply = (event: FormEvent) => {
        event.preventDefault();
        router.get('/dashboard', range, { preserveState: true, preserveScroll: true });
    };

    return (
        <div className="dashboard-page">
            <Head title="Dashboard" />
            <PageTitle
                title={
                    owner
                        ? 'Ringkasan bisnis'
                        : `Dashboard ${user.branch?.city ?? 'cabang'}`
                }
                description={
                    owner
                        ? 'Satu pandangan untuk seluruh kedai Anda.'
                        : 'Penjualan, persediaan, dan prioritas cabang Anda.'
                }
                action={
                    <Button
                        asChild
                        variant="outline"
                    >
                        <Link href={`/reports?${reportQuery}`}>
                            Lihat laporan <ArrowUpRight size={15} />
                        </Link>
                    </Button>
                }
            />
            <form
                id="report-filters"
                className="report-toolbar dashboard-toolbar"
                onSubmit={apply}
            >
                <div className="dashboard-scope">
                    {owner ? (
                        <select
                            aria-label="Filter cabang"
                            value={range.branch_id}
                            onChange={(event) =>
                                setRange({ ...range, branch_id: event.target.value })
                            }
                        >
                            <option value="">Seluruh cabang</option>
                            {branches.map((branch) => (
                                <option
                                    value={branch.id}
                                    key={branch.id}
                                >
                                    {branch.city}
                                </option>
                            ))}
                        </select>
                    ) : (
                        <span className="dashboard-branch-name">
                            {user.branch?.city ?? 'Cabang Anda'}
                        </span>
                    )}
                    <div className="date-range">
                        <CalendarDays
                            size={16}
                            aria-hidden="true"
                        />
                        <label>
                            <span className="sr-only">Tanggal mulai</span>
                            <input
                                type="date"
                                value={range.start}
                                max={range.end}
                                onChange={(event) =>
                                    setRange({ ...range, start: event.target.value })
                                }
                                required
                            />
                        </label>
                        <span aria-hidden="true">—</span>
                        <label>
                            <span className="sr-only">Tanggal selesai</span>
                            <input
                                type="date"
                                value={range.end}
                                min={range.start}
                                onChange={(event) =>
                                    setRange({ ...range, end: event.target.value })
                                }
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
                </div>
                <button
                    className="refresh-control"
                    type="button"
                    onClick={() => router.reload()}
                    aria-label="Muat ulang dashboard"
                >
                    <RefreshCw size={15} /> <span>Perbarui data</span>
                </button>
            </form>

            <FinancialSummary
                metrics={report.metrics}
                owner={owner}
            />

            <div className="dashboard-overview-grid">
                <SalesTrend report={report} />
                <section className="panel dashboard-priorities">
                    <div className="panel-header">
                        <div>
                            <h2>Prioritas operasional</h2>
                            <p>Status saat ini, di luar filter tanggal</p>
                        </div>
                        <span
                            className={`dashboard-task-total ${pendingTasks > 0 ? 'has-pending' : ''}`}
                        >
                            {number(pendingTasks)} menunggu
                        </span>
                    </div>
                    <div className="dashboard-task-list">
                        {(report.tasks ?? []).map((task: Row, index: number) => {
                            const Icon = taskIcons[index] ?? PackagePlus;
                            return (
                                <Link
                                    key={task.href}
                                    href={`${task.href}${filters.branch_id ? `?branch_id=${filters.branch_id}` : ''}`}
                                    className={
                                        Number(task.count) > 0 ? 'has-pending' : ''
                                    }
                                >
                                    <Icon
                                        size={17}
                                        strokeWidth={1.6}
                                        aria-hidden="true"
                                    />
                                    <span>{task.label}</span>
                                    <strong>{number(task.count)}</strong>
                                    <ChevronRight
                                        size={15}
                                        aria-hidden="true"
                                    />
                                </Link>
                            );
                        })}
                    </div>
                    <div className="panel-footer">
                        <Link
                            className="text-link"
                            href="/alerts"
                        >
                            Buka notifikasi <ArrowUpRight size={14} />
                        </Link>
                    </div>
                </section>
            </div>

            <div
                className={`dashboard-detail-grid ${owner ? 'dashboard-owner-details' : ''}`}
            >
                {owner && (
                    <section className="panel dashboard-branches">
                        <div className="panel-header">
                            <div>
                                <h2>Performa cabang</h2>
                                <p>Penjualan bersih pada periode terpilih</p>
                            </div>
                        </div>
                        <div className="dashboard-branch-list">
                            {branchRanking.slice(0, 3).map((branch: Row) => (
                                <Link
                                    key={branch.id}
                                    href={`/reports?${new URLSearchParams({ start: filters.start, end: filters.end, branch_id: String(branch.id) }).toString()}`}
                                >
                                    <div>
                                        <strong>{branch.city}</strong>
                                        <strong className="num">
                                            {money(branch.amount)}
                                        </strong>
                                    </div>
                                    <div
                                        className="dashboard-volume-track"
                                        aria-hidden="true"
                                    >
                                        <span
                                            style={{
                                                width: `${highestBranchSales > 0 ? (Math.max(0, Number(branch.amount)) / highestBranchSales) * 100 : 0}%`,
                                            }}
                                        />
                                    </div>
                                    <p>{number(branch.transactions)} transaksi</p>
                                </Link>
                            ))}
                            {!branchRanking.length && (
                                <Empty>Belum ada cabang pada cakupan ini.</Empty>
                            )}
                        </div>
                        <div className="panel-footer">
                            <Link
                                className="text-link"
                                href={`/reports?${reportQuery}`}
                            >
                                Bandingkan cabang <ArrowUpRight size={14} />
                            </Link>
                        </div>
                    </section>
                )}
                <section className="panel dashboard-products">
                    <div className="panel-header">
                        <div>
                            <h2>Menu terlaris</h2>
                            <p>3 menu dengan unit bersih terbanyak</p>
                        </div>
                    </div>
                    {report.topProducts.length ? (
                        <div className="dashboard-product-list">
                            {report.topProducts
                                .slice(0, 3)
                                .map((product: Row, index: number) => (
                                    <div key={product.product_id}>
                                        <span className="dashboard-product-rank">
                                            {index + 1}
                                        </span>
                                        <div className="dashboard-product-info">
                                            <div>
                                                <strong>{product.name}</strong>
                                                <span>
                                                    {number(product.quantity)} produk
                                                </span>
                                            </div>
                                            <div
                                                className="dashboard-volume-track"
                                                aria-hidden="true"
                                            >
                                                <span
                                                    style={{
                                                        width: `${highestProductQuantity > 0 ? (Math.max(0, Number(product.quantity)) / highestProductQuantity) * 100 : 0}%`,
                                                    }}
                                                />
                                            </div>
                                            <p>{money(product.amount)}</p>
                                        </div>
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
                            href={`/reports/products?${reportQuery}`}
                            className="text-link"
                        >
                            Lihat penjualan menu <ArrowUpRight size={14} />
                        </Link>
                    </div>
                </section>
                <section className="panel dashboard-stock">
                    <div className="panel-header">
                        <div>
                            <h2>Persediaan minimum</h2>
                            <p>Kondisi bahan saat ini</p>
                        </div>
                    </div>
                    {report.lowStock.length ? (
                        <div className="dashboard-stock-list">
                            {report.lowStock.slice(0, 3).map((stock: Row) => (
                                <div key={stock.id}>
                                    <div>
                                        <strong>{stock.ingredient.name}</strong>
                                        <p>{stock.branch.city}</p>
                                    </div>
                                    <span
                                        className={`dashboard-stock-quantity ${Number(stock.quantity) === 0 ? 'is-empty' : ''}`}
                                    >
                                        {number(stock.quantity)}{' '}
                                        <span>{stock.ingredient.unit}</span>
                                    </span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <Empty>Semua bahan berada di atas batas minimum.</Empty>
                    )}
                    <div className="panel-footer">
                        <Link
                            href="/manage/inventory"
                            className="text-link"
                        >
                            Periksa persediaan <ArrowUpRight size={14} />
                        </Link>
                    </div>
                </section>
            </div>

            <section className="dashboard-recent panel">
                <div className="panel-header">
                    <div>
                        <h2>Transaksi terbaru</h2>
                        <p>Aktivitas terakhir pada periode terpilih</p>
                    </div>
                    <Link
                        href="/manage/orders"
                        className="text-link"
                    >
                        Lihat semua <ArrowUpRight size={14} />
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
                            {report.recentOrders.slice(0, 5).map((order: Row) => (
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
