import { Head, Link } from '@inertiajs/react';
import { Bell, PackageSearch, ClipboardCheck, RotateCcw } from 'lucide-react';
import { Empty, PageTitle, Status } from '../components/common';
import { money, number, type Row } from '../lib/utils';
export default function Alerts({
    stock,
    purchases,
    refunds,
}: {
    stock: Row[];
    purchases: Row[];
    refunds: Row[];
}) {
    return (
        <>
            <Head title="Notifikasi operasional" />
            <PageTitle
                title="Notifikasi operasional"
                description="Pekerjaan yang perlu ditindaklanjuti berdasarkan data saat ini."
            />
            <div className="alerts-layout">
                <section>
                    <div className="section-heading">
                        <h2>
                            <ClipboardCheck size={18} />
                            Stok perlu diisi
                        </h2>
                        <span>{stock.length} bahan</span>
                    </div>
                    {stock.length ? (
                        stock.map((row) => (
                            <div
                                className="alert-row"
                                key={row.id}
                            >
                                <div>
                                    <strong>{row.ingredient.name}</strong>
                                    <p>
                                        {row.branch.city} · minimum {number(row.minimum)}{' '}
                                        {row.ingredient.unit}
                                    </p>
                                </div>
                                <strong>
                                    {number(row.quantity)} {row.ingredient.unit}
                                </strong>
                                <Link
                                    href="/manage/purchases"
                                    className="text-link"
                                >
                                    Buat pembelian
                                </Link>
                            </div>
                        ))
                    ) : (
                        <Empty>Tidak ada bahan di bawah batas minimum.</Empty>
                    )}
                </section>
                <section>
                    <div className="section-heading">
                        <h2>
                            <PackageSearch size={18} />
                            Pembelian menunggu bahan
                        </h2>
                        <span>{purchases.length} pesanan</span>
                    </div>
                    {purchases.length ? (
                        purchases.map((row) => (
                            <div
                                className="alert-row"
                                key={row.id}
                            >
                                <div>
                                    <strong>{row.number}</strong>
                                    <p>
                                        {row.supplier.name} · {row.branch.city}
                                    </p>
                                </div>
                                <Status value={row.status} />
                                <Link
                                    href="/manage/purchases"
                                    className="text-link"
                                >
                                    Periksa penerimaan
                                </Link>
                            </div>
                        ))
                    ) : (
                        <Empty>Semua pesanan pembelian sudah ditindaklanjuti.</Empty>
                    )}
                </section>
                <section>
                    <div className="section-heading">
                        <h2>
                            <RotateCcw size={18} />
                            Permintaan refund
                        </h2>
                        <span>{refunds.length} permintaan</span>
                    </div>
                    {refunds.length ? (
                        refunds.map((row) => (
                            <div
                                className="alert-row"
                                key={row.id}
                            >
                                <div>
                                    <strong>{row.order.order_number}</strong>
                                    <p>
                                        {row.order.branch.city} · {row.reason}
                                    </p>
                                </div>
                                <strong>{money(row.amount)}</strong>
                                <Link
                                    href="/manage/refunds"
                                    className="text-link"
                                >
                                    Tinjau permintaan
                                </Link>
                            </div>
                        ))
                    ) : (
                        <Empty>Tidak ada refund yang menunggu persetujuan.</Empty>
                    )}
                </section>
            </div>
        </>
    );
}
