import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Printer, ShoppingBag, RotateCcw, CheckCircle2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Button } from '../components/ui/button';
import { Dialog } from '../components/ui/dialog';
import { Errors, PageTitle, Status } from '../components/common';
import { date, labels, money, type Row, type Shared } from '../lib/utils';
export default function Receipt({ order }: { order: Row }) {
    const { business } = usePage<Shared>().props;
    const [refund, setRefund] = useState(false);
    const form = useForm({ reason: '' });
    function submit(e: FormEvent) {
        e.preventDefault();
        form.post(`/orders/${order.id}/refund`, { onSuccess: () => setRefund(false) });
    }
    return (
        <>
            <Head title={order.order_number} />
            <PageTitle
                title="Detail transaksi"
                description="Pembayaran telah dicatat. Struk dapat dicetak kembali kapan saja."
                action={
                    <Button
                        variant="outline"
                        onClick={() => window.print()}
                    >
                        <Printer size={16} />
                        Cetak struk
                    </Button>
                }
            />
            <div className="receipt-layout">
                <article className="receipt printable">
                    <div className="receipt-brand">VELTRIX</div>
                    <h2>{order.branch.name}</h2>
                    <p>{order.branch.address}</p>
                    <p>{order.branch.phone}</p>
                    <div className="receipt-rule" />
                    <div className="key-value">
                        <span>No. transaksi</span>
                        <strong>{order.order_number}</strong>
                    </div>
                    <div className="key-value">
                        <span>Waktu</span>
                        <span>{date(order.completed_at)}</span>
                    </div>
                    <div className="key-value">
                        <span>Kasir</span>
                        <span>{order.cashier.name}</span>
                    </div>
                    {order.customer_name && (
                        <div className="key-value">
                            <span>Pelanggan</span>
                            <span>{order.customer_name}</span>
                        </div>
                    )}
                    <div className="receipt-rule" />
                    {order.items.map((item: Row) => (
                        <div
                            className="receipt-item"
                            key={item.id}
                        >
                            <div>
                                <strong>{item.product_name_snapshot}</strong>
                                <p>
                                    {item.quantity} × {money(item.unit_price)}
                                </p>
                            </div>
                            <strong>{money(item.total_price)}</strong>
                        </div>
                    ))}
                    <div className="receipt-rule" />
                    <div className="key-value receipt-total">
                        <strong>Total</strong>
                        <strong>{money(order.total_amount)}</strong>
                    </div>
                    <div className="key-value">
                        <span>{labels[order.payment.method]}</span>
                        <span>{money(order.payment.tendered)}</span>
                    </div>
                    {order.payment.method === 'cash' && (
                        <div className="key-value">
                            <span>Kembalian</span>
                            <span>
                                {money(order.payment.tendered - order.total_amount)}
                            </span>
                        </div>
                    )}
                    {order.notes && (
                        <p className="receipt-note">Catatan: {order.notes}</p>
                    )}
                    <div className="receipt-thanks">
                        <p>{business.receipt_footer}</p>
                    </div>
                </article>
                <aside className="receipt-status">
                    <CheckCircle2
                        size={28}
                        strokeWidth={1.5}
                    />
                    <h2>Transaksi tercatat</h2>
                    <Status value={order.status} />
                    <p className="muted">
                        Produk terjual dan konsumsi bahan tersimpan bersama transaksi ini.
                    </p>
                    <Button asChild>
                        <Link href="/pos">
                            <ShoppingBag size={16} />
                            Kembali ke POS
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        asChild
                    >
                        <Link href="/manage/orders">Riwayat transaksi</Link>
                    </Button>
                    {!order.refund && order.status === 'completed' ? (
                        <Button
                            variant="ghost"
                            onClick={() => setRefund(true)}
                        >
                            <RotateCcw size={15} />
                            Ajukan refund penuh
                        </Button>
                    ) : (
                        order.refund && (
                            <div className="notice-block">
                                <strong>Permintaan refund</strong>
                                <Status value={order.refund.status} />
                                <p>{order.refund.reason}</p>
                                <p className="muted">{money(order.refund.amount)}</p>
                            </div>
                        )
                    )}
                </aside>
            </div>
            <Dialog
                open={refund}
                onOpenChange={setRefund}
                title="Ajukan refund penuh"
                description="Persetujuan manager atau owner diperlukan. Bahan yang telah dipakai tidak dikembalikan ke stok otomatis."
            >
                <form onSubmit={submit}>
                    <Errors errors={form.errors} />
                    <p className="workflow-record">
                        {order.order_number} · {money(order.total_amount)}
                    </p>
                    <label className="field">
                        Alasan pengembalian
                        <textarea
                            rows={4}
                            required
                            minLength={5}
                            value={form.data.reason}
                            onChange={(e) => form.setData('reason', e.target.value)}
                        />
                    </label>
                    <div className="dialog-footer">
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setRefund(false)}
                        >
                            Batal
                        </Button>
                        <Button disabled={form.processing}>
                            {form.processing ? 'Mengirim…' : 'Ajukan refund'}
                        </Button>
                    </div>
                </form>
            </Dialog>
        </>
    );
}
