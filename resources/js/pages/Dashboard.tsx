import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Download,
    CalendarDays,
    Coffee,
    ArrowUpRight,
    ClipboardCheck,
    RefreshCw,
    Sparkles,
    Store,
    ShoppingBag,
} from 'lucide-react';
import {
    ResponsiveContainer,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
} from 'recharts';
import { useState, type FormEvent } from 'react';
import { Button } from '../components/ui/button';
import { Empty, PageTitle, Status } from '../components/common';
import { ReportDetails } from '../components/report-details';
import { FinancialSummary } from '../components/financial-summary';
import { date, labels, money, number, type Row, type Shared } from '../lib/utils';

export default function Dashboard({
    report,
    filters,
    branches,
    isReport,
}: {
    report: Row;
    filters: Row;
    branches: Row[];
    isReport: boolean;
}) {
    const {
        auth: { user },
    } = usePage<Shared>().props;
    const [range, setRange] = useState(filters);
    const m = report.metrics;
    const apply = (e: FormEvent) => {
        e.preventDefault();
        router.get(isReport ? '/reports' : '/dashboard', range, { preserveState: true });
    };
    return (
        <>
            <Head title={isReport ? 'Laporan bisnis' : 'Dashboard'} />
            <PageTitle
                title={
                    isReport
                        ? 'Laporan bisnis'
                        : user.role === 'owner'
                          ? 'Dashboard'
                          : `Dashboard ${user.branch?.city ?? 'cabang'}`
                }
                description={
                    isReport
                        ? 'Penjualan, biaya, dan performa kedai dari catatan operasional.'
                        : `${user.role === 'owner' ? 'Pantau ritme seluruh kedai' : 'Pantau operasional kedai Anda'}, dari penjualan hingga persediaan.`
                }
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
                        {isReport ? (
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
                        ) : (
                            <Button
                                asChild
                                variant="outline"
                            >
                                <Link href="/reports">Lihat laporan</Link>
                            </Button>
                        )}
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
                <section className="panel revenue-panel">
                    <div className="panel-header">
                        <div>
                            <h2>Tren penjualan</h2>
                            <p>
                                Penjualan bersih per{' '}
                                {report.granularity === 'hour' ? 'jam' : 'hari'}, dalam
                                Rupiah
                            </p>
                        </div>
                        <span className="series-key">Periode terpilih</span>
                    </div>
                    <div className="revenue-chart">
                        <ResponsiveContainer
                            width="100%"
                            height="100%"
                        >
                            <LineChart
                                data={report.trend}
                                margin={{ top: 12, right: 12, bottom: 8, left: 0 }}
                            >
                                <CartesianGrid
                                    vertical={false}
                                    stroke="#edf0ee"
                                />
                                <XAxis
                                    dataKey="date"
                                    axisLine={false}
                                    tickLine={false}
                                    minTickGap={40}
                                    tick={{ fill: '#626b65', fontSize: 12 }}
                                    tickFormatter={(v) =>
                                        report.granularity === 'hour'
                                            ? new Date(v).toLocaleTimeString('id-ID', {
                                                  hour: '2-digit',
                                                  minute: '2-digit',
                                                  timeZone: 'Asia/Jakarta',
                                              })
                                            : new Date(
                                                  v + 'T12:00:00',
                                              ).toLocaleDateString('id-ID', {
                                                  day: 'numeric',
                                                  month: 'short',
                                              })
                                    }
                                />
                                <YAxis
                                    axisLine={false}
                                    tickLine={false}
                                    width={58}
                                    tick={{ fill: '#626b65', fontSize: 12 }}
                                    tickFormatter={(v) =>
                                        Math.abs(v) >= 1000000
                                            ? `${v / 1000000} jt`
                                            : Math.abs(v) >= 1000
                                              ? `${v / 1000} rb`
                                              : v
                                    }
                                    domain={
                                        m.transactions ? ['auto', 'auto'] : [0, 100000]
                                    }
                                />
                                <Tooltip
                                    formatter={(v) => money(Number(v))}
                                    labelFormatter={(v) => date(String(v))}
                                    contentStyle={{
                                        border: '1px solid #e4e9e6',
                                        borderRadius: 8,
                                    }}
                                />
                                <Line
                                    dataKey="amount"
                                    name="Penjualan bersih"
                                    type="linear"
                                    stroke="#166348"
                                    strokeWidth={2}
                                    dot={false}
                                    activeDot={{ r: 4 }}
                                    isAnimationActive={false}
                                />
                            </LineChart>
                        </ResponsiveContainer>
                    </div>
                    {!m.transactions && (
                        <p className="chart-caption">
                            Belum ada transaksi pada periode ini. Nilai ditampilkan
                            sebagai nol.
                        </p>
                    )}
                </section>
                {user.role === 'manager' ? (
                    <section className="panel branch-panel">
                        <div className="panel-header">
                            <div>
                                <h2>Tindak lanjut cabang</h2>
                                <p>Pekerjaan yang masih menunggu</p>
                            </div>
                            <ClipboardCheck size={18} />
                        </div>
                        <div className="manager-tasks">
                            {(report.tasks ?? []).map((task: Row) => (
                                <Link
                                    key={task.href}
                                    href={task.href}
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
                ) : (
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
                                            <strong className="num">
                                                {money(b.amount)}
                                            </strong>
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
                )}
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
            {isReport && (
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
            )}
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
            {isReport && (
                <ReportDetails
                    report={report}
                    owner={user.role === 'owner'}
                />
            )}
            {user.role === 'owner' && (
                <div className="intelligence-strip">
                    <Sparkles size={18} />
                    <div>
                        <strong>AI Business Intelligence</strong>
                        <span>Integrasi Gemini belum diaktifkan.</span>
                    </div>
                    <Link
                        href="/intelligence"
                        className="text-link"
                    >
                        Lihat status integrasi
                    </Link>
                </div>
            )}
        </>
    );
}
