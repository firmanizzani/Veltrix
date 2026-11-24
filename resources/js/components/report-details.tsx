import { Link } from '@inertiajs/react';
import { Empty } from './common';
import { date, labels, money, number, type Row } from '../lib/utils';

export function ReportDetails({ report, owner }: { report: Row; owner: boolean }) {
    return (
        <>
            <section
                id="report-expenses"
                className="finance-report"
            >
                <div className="section-heading">
                    <h2>Pengeluaran per kategori</h2>
                    <Link
                        className="text-link"
                        href="/manage/expenses"
                    >
                        Buka rincian pengeluaran
                    </Link>
                </div>
                {report.expenseCategories.length ? (
                    report.expenseCategories.map((row: Row) => (
                        <div
                            className="key-value"
                            key={row.name}
                        >
                            <span>{row.name}</span>
                            <strong>{money(row.amount)}</strong>
                        </div>
                    ))
                ) : (
                    <Empty>Belum ada pengeluaran disetujui pada periode ini.</Empty>
                )}
            </section>
            <section
                id="report-inventory"
                className="recent-section"
            >
                <div className="section-heading">
                    <div>
                        <h2>Nilai persediaan saat ini</h2>
                        <p>
                            Saldo terkini dengan biaya rata-rata tertimbang, bukan saldo
                            historis pada tanggal akhir laporan.
                        </p>
                    </div>
                    <Link
                        className="text-link"
                        href="/manage/inventory"
                    >
                        Buka persediaan
                    </Link>
                </div>
                <div className="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Cabang</th>
                                <th>Bahan</th>
                                <th className="num">Stok</th>
                                <th>Satuan</th>
                                <th className="num">Nilai persediaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.inventory.map((stock: Row) => (
                                <tr key={stock.id}>
                                    <td>{stock.branch}</td>
                                    <td>{stock.ingredient}</td>
                                    <td className="num">{number(stock.quantity)}</td>
                                    <td>{stock.unit}</td>
                                    <td className="num">{money(stock.value)}</td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colSpan={4}>Total nilai persediaan saat ini</th>
                                <th className="num">
                                    {money(
                                        report.inventory.reduce(
                                            (sum: number, row: Row) => sum + row.value,
                                            0,
                                        ),
                                    )}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                    {!report.inventory.length && <Empty>Belum ada persediaan.</Empty>}
                </div>
                <div className="panel-footer">
                    <h3>Aktivitas stok pada periode terpilih</h3>
                    {report.movements.map((row: Row) => (
                        <div
                            className="key-value"
                            key={row.type}
                        >
                            <Link
                                className="text-link"
                                href="/manage/movements"
                            >
                                {labels[row.type] ?? row.type}
                            </Link>
                            <span>{number(row.movements)} pergerakan</span>
                        </div>
                    ))}
                    {!report.movements.length && (
                        <p className="muted">Belum ada pergerakan stok.</p>
                    )}
                </div>
            </section>
        </>
    );
}

export function BranchComparison({ report, owner }: { report: Row; owner: boolean }) {
    return (
        <section
            id="report-branches"
            className="recent-section"
        >
            <div className="section-heading">
                <div>
                    <h2>Perbandingan cabang</h2>
                    <p>
                        Pertumbuhan dibandingkan {date(report.comparison.start)} sampai{' '}
                        {date(report.comparison.end)}, dengan durasi yang sama. Tanpa
                        penjualan pembanding positif, persentase tidak dihitung.
                    </p>
                </div>
            </div>
            <div className="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Cabang</th>
                            <th className="num">Penjualan bersih</th>
                            <th className="num">Transaksi</th>
                            <th className="num">Rata-rata</th>
                            <th className="num">Unit bersih</th>
                            <th className="num">Laba kotor</th>
                            <th className="num">Pengeluaran</th>
                            {owner && <th className="num">Payroll</th>}
                            {owner && <th className="num">Laba operasional</th>}
                            <th className="num">Pertumbuhan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {report.branches.map((branch: Row) => (
                            <tr key={branch.id}>
                                <td>
                                    <Link
                                        className="identity-link"
                                        href={`/manage/orders?branch_id=${branch.id}`}
                                    >
                                        {branch.city}
                                    </Link>
                                </td>
                                <td className="num">{money(branch.amount)}</td>
                                <td className="num">{number(branch.transactions)}</td>
                                <td className="num">{money(branch.average)}</td>
                                <td className="num">{number(branch.units)}</td>
                                <td className="num">{money(branch.gross_profit)}</td>
                                <td className="num">{money(branch.expenses)}</td>
                                {owner && (
                                    <td className="num">{money(branch.payroll)}</td>
                                )}
                                {owner && <td className="num">{money(branch.profit)}</td>}
                                <td className="num">
                                    {branch.growth === null
                                        ? 'Belum ada pembanding'
                                        : `${number(branch.growth)}%`}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </section>
    );
}
