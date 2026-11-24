import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Coffee,
    Plus,
    Minus,
    Trash2,
    ShoppingBag,
    ReceiptText,
    CreditCard,
    Banknote,
    QrCode,
    Wallet,
    Clock3,
    LockKeyhole,
    Search,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useRef, useState, type FormEvent } from 'react';
import { Button } from '../components/ui/button';
import { Dialog } from '../components/ui/dialog';
import { Empty, Errors, PageTitle, ProductImage, SearchBox } from '../components/common';
import { date, money, number, type Row, type Shared } from '../lib/utils';

type CartLine = { menu: Row; quantity: number };
export default function Pos({
    products,
    shift,
    branch,
    branches,
    canOperate,
}: {
    products: Row[];
    shift: Row | null;
    branch: Row | null;
    branches: Row[];
    canOperate: boolean;
}) {
    const {
        auth: { user },
    } = usePage<Shared>().props;
    const [search, setSearch] = useState('');
    const [category, setCategory] = useState('Semua menu');
    const [cart, setCart] = useState<CartLine[]>([]);
    const cartRef = useRef<HTMLElement>(null);
    const [cartVisible, setCartVisible] = useState(false);
    useEffect(() => {
        const observer = new IntersectionObserver(
            ([entry]) => setCartVisible(entry.isIntersecting),
            { threshold: 0.05 },
        );
        if (cartRef.current) {
            observer.observe(cartRef.current);
        }
        return () => observer.disconnect();
    }, []);
    const [shiftDialog, setShiftDialog] = useState<'open' | 'close' | null>(null);
    const [confirmClear, setConfirmClear] = useState(false);
    const [key, setKey] = useState(() => crypto.randomUUID());
    const payment = useForm({
        method: 'cash',
        tendered: '',
        customer_name: '',
        notes: '',
        reference_number: '',
    });
    const shiftForm = useForm({ opening_cash: '', actual_cash: '' });
    const categories = useMemo(
        () => [
            'Semua menu',
            ...new Set(products.map((menu) => menu.product.category.name)),
        ],
        [products],
    );
    const visible = products.filter(
        (menu) =>
            (category === 'Semua menu' || menu.product.category.name === category) &&
            `${menu.product.name} ${menu.product.sku}`
                .toLowerCase()
                .includes(search.toLowerCase()),
    );
    const total = cart.reduce(
        (sum, line) => sum + Number(line.menu.price) * line.quantity,
        0,
    );
    const count = cart.reduce((sum, line) => sum + line.quantity, 0);
    function add(menu: Row) {
        if (payment.processing || !canOperate || !menu.is_available) {
            return;
        }
        setCart((current) => {
            const exists = current.find((line) => line.menu.id === menu.id);
            return exists
                ? current.map((line) =>
                      line.menu.id === menu.id
                          ? { ...line, quantity: Math.min(100, line.quantity + 1) }
                          : line,
                  )
                : [...current, { menu, quantity: 1 }];
        });
    }
    function quantity(id: number, change: number) {
        if (!payment.processing) {
            setCart((current) =>
                current
                    .map((line) =>
                        line.menu.id === id
                            ? { ...line, quantity: Math.min(100, line.quantity + change) }
                            : line,
                    )
                    .filter((line) => line.quantity > 0),
            );
        }
    }
    function checkout(e: FormEvent) {
        e.preventDefault();
        payment.transform((data) => ({
            ...data,
            tendered: data.method === 'cash' ? Number(data.tendered) : total,
            idempotency_key: key,
            items: cart.map((line) => ({
                branch_product_id: line.menu.id,
                quantity: line.quantity,
                expected_price: Number(line.menu.price),
            })),
        }));
        payment.post('/pos/checkout', {
            onSuccess: () => {
                setCart([]);
                setKey(crypto.randomUUID());
                payment.reset();
            },
        });
    }
    function changeShift(e: FormEvent) {
        e.preventDefault();
        shiftForm.post(shiftDialog === 'open' ? '/shifts/open' : '/shifts/close', {
            preserveScroll: true,
            onSuccess: () => {
                setShiftDialog(null);
                shiftForm.reset();
            },
        });
    }
    const methods = [
        { key: 'cash', label: 'Tunai', icon: Banknote },
        { key: 'qris', label: 'QRIS', icon: QrCode },
        { key: 'card', label: 'Kartu', icon: CreditCard },
        { key: 'ewallet', label: 'E-Wallet', icon: Wallet },
    ];
    return (
        <>
            <Head title="Point of Sale" />
            <PageTitle
                className="pos-heading"
                title="Point of Sale"
                description={branch?.name ?? 'Belum ada cabang aktif'}
                action={
                    <div className="pos-shift">
                        <Clock3 size={16} />
                        <span>
                            {shift
                                ? `Shift dibuka ${date(shift.opened_at)}`
                                : canOperate
                                  ? 'Shift belum dibuka'
                                  : 'Mode pengawasan'}
                        </span>
                        {canOperate && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => {
                                    shiftForm.clearErrors();
                                    setShiftDialog(shift ? 'close' : 'open');
                                }}
                            >
                                {shift ? 'Tutup shift' : 'Buka shift'}
                            </Button>
                        )}
                    </div>
                }
            />
            {!canOperate && (
                <div className="notice">
                    <LockKeyhole size={16} />
                    <span>
                        Anda melihat katalog untuk pengawasan. Transaksi diproses melalui
                        akun kasir.
                    </span>
                    {branches.length > 0 && (
                        <select
                            aria-label="Pilih cabang katalog"
                            value={branch?.id ?? ''}
                            onChange={(e) =>
                                router.get('/pos', { branch_id: e.target.value })
                            }
                        >
                            {branches.map((b) => (
                                <option
                                    key={b.id}
                                    value={b.id}
                                >
                                    {b.city}
                                </option>
                            ))}
                        </select>
                    )}
                </div>
            )}
            <div className="pos-layout">
                <section
                    className="pos-catalog"
                    aria-label="Katalog menu"
                >
                    <div className="pos-search">
                        <SearchBox
                            value={search}
                            onChange={setSearch}
                            placeholder="Cari menu atau SKU…"
                        />
                        <span>{number(visible.length)} menu</span>
                    </div>
                    <div
                        className="category-tabs"
                        role="tablist"
                        aria-label="Kategori menu"
                    >
                        {categories.map((c) => (
                            <button
                                key={c}
                                type="button"
                                role="tab"
                                aria-selected={category === c}
                                className={category === c ? 'category-active' : ''}
                                onClick={() => setCategory(c)}
                            >
                                {c}
                            </button>
                        ))}
                    </div>
                    <div className="product-grid">
                        {visible.map((menu) => {
                            const selected = cart.find(
                                (line) => line.menu.id === menu.id,
                            )?.quantity;
                            return (
                                <button
                                    key={menu.id}
                                    className={`product-card ${selected ? 'product-selected' : ''}`}
                                    disabled={
                                        !menu.is_available ||
                                        !canOperate ||
                                        payment.processing
                                    }
                                    onClick={() => add(menu)}
                                    aria-label={`Tambah ${menu.product.name}, ${money(menu.price)}`}
                                >
                                    <div className="product-photo">
                                        <ProductImage
                                            product={menu.product}
                                            large
                                        />
                                        {selected && (
                                            <span className="product-count">
                                                {selected}
                                            </span>
                                        )}
                                        {!menu.is_available && (
                                            <span className="unavailable-label">
                                                Tidak tersedia
                                            </span>
                                        )}
                                    </div>
                                    <div className="product-info">
                                        <span>{menu.product.category.name}</span>
                                        <strong>{menu.product.name}</strong>
                                        <div>
                                            <b>{money(menu.price)}</b>
                                            {canOperate && menu.is_available && (
                                                <Plus size={17} />
                                            )}
                                        </div>
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                    {!visible.length && (
                        <Empty>
                            {products.length
                                ? 'Menu tidak ditemukan. Coba kata kunci atau kategori lain.'
                                : 'Belum ada menu untuk cabang ini. Owner dapat menambahkan produk melalui katalog.'}
                        </Empty>
                    )}
                </section>
                <aside
                    id="active-order"
                    ref={cartRef}
                    className="cart-panel"
                    aria-label="Pesanan aktif"
                >
                    <div className="cart-title">
                        <div>
                            <ShoppingBag size={20} />
                            <h2>Pesanan baru</h2>
                            <span className="count-chip">{count}</span>
                        </div>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Kosongkan pesanan"
                            disabled={!cart.length || payment.processing}
                            onClick={() => setConfirmClear(true)}
                        >
                            <Trash2 size={16} />
                        </Button>
                    </div>
                    <form onSubmit={checkout}>
                        <div className="cart-customer">
                            <label
                                className="sr-only"
                                htmlFor="customer-name"
                            >
                                Nama pelanggan
                            </label>
                            <input
                                id="customer-name"
                                placeholder="Nama pelanggan (opsional)"
                                maxLength={120}
                                value={payment.data.customer_name}
                                onChange={(e) =>
                                    payment.setData('customer_name', e.target.value)
                                }
                                disabled={payment.processing}
                            />
                        </div>
                        <div
                            className={`cart-items ${!cart.length ? 'cart-is-empty' : ''}`}
                        >
                            {cart.length ? (
                                cart.map((line) => (
                                    <div
                                        className="cart-line"
                                        key={line.menu.id}
                                    >
                                        <ProductImage product={line.menu.product} />
                                        <div className="cart-line-info">
                                            <strong>{line.menu.product.name}</strong>
                                            <span>{money(line.menu.price)}</span>
                                            <div className="quantity-stepper">
                                                <button
                                                    type="button"
                                                    aria-label={`Kurangi ${line.menu.product.name}`}
                                                    onClick={() =>
                                                        quantity(line.menu.id, -1)
                                                    }
                                                    disabled={payment.processing}
                                                >
                                                    <Minus size={12} />
                                                </button>
                                                <span>{line.quantity}</span>
                                                <button
                                                    type="button"
                                                    aria-label={`Tambah ${line.menu.product.name}`}
                                                    onClick={() =>
                                                        quantity(line.menu.id, 1)
                                                    }
                                                    disabled={payment.processing}
                                                >
                                                    <Plus size={12} />
                                                </button>
                                            </div>
                                        </div>
                                        <strong className="num">
                                            {money(
                                                Number(line.menu.price) * line.quantity,
                                            )}
                                        </strong>
                                    </div>
                                ))
                            ) : (
                                <div className="cart-empty">
                                    <ShoppingBag
                                        size={32}
                                        strokeWidth={1.1}
                                    />
                                    <strong>Pesanan masih kosong</strong>
                                    <p>
                                        Pilih menu di sebelah kiri
                                        <br />
                                        untuk mulai melayani.
                                    </p>
                                </div>
                            )}
                        </div>
                        <div className="cart-payment">
                            <Errors errors={payment.errors} />
                            <details className="cart-notes">
                                <summary>Catatan pesanan (opsional)</summary>
                                <label className="field cart-note">
                                    <span className="sr-only">Catatan pesanan</span>
                                    <input
                                        placeholder="Contoh: tanpa es, bawa pulang"
                                        maxLength={1000}
                                        value={payment.data.notes}
                                        onChange={(e) =>
                                            payment.setData('notes', e.target.value)
                                        }
                                        disabled={payment.processing}
                                    />
                                </label>
                            </details>
                            <div className="cart-subtotal">
                                <span>Subtotal ({count} produk)</span>
                                <span>{money(total)}</span>
                            </div>
                            <div className="cart-total">
                                <span>Total pembayaran</span>
                                <strong>{money(total)}</strong>
                            </div>
                            <label className="payment-label">Metode pembayaran</label>
                            <div className="payment-methods">
                                {methods.map(({ key: value, label, icon: Icon }) => (
                                    <button
                                        key={value}
                                        type="button"
                                        className={
                                            payment.data.method === value
                                                ? 'method-active'
                                                : ''
                                        }
                                        onClick={() => payment.setData('method', value)}
                                        disabled={payment.processing}
                                        aria-pressed={payment.data.method === value}
                                    >
                                        <Icon size={18} />
                                        <span>{label}</span>
                                    </button>
                                ))}
                            </div>
                            {payment.data.method === 'cash' ? (
                                <>
                                    <label className="field">
                                        Uang diterima (Rp)
                                        <input
                                            type="number"
                                            min={total}
                                            max={100000000}
                                            step="1"
                                            inputMode="numeric"
                                            value={payment.data.tendered}
                                            onChange={(e) =>
                                                payment.setData(
                                                    'tendered',
                                                    e.target.value,
                                                )
                                            }
                                            required
                                            disabled={payment.processing}
                                        />
                                    </label>
                                    <div className="cash-shortcuts">
                                        {[
                                            total,
                                            ...[50000, 100000, 150000, 200000]
                                                .filter((v) => v > total)
                                                .slice(0, 2),
                                        ].map((v) => (
                                            <button
                                                type="button"
                                                key={v}
                                                onClick={() =>
                                                    payment.setData('tendered', String(v))
                                                }
                                                disabled={
                                                    !cart.length || payment.processing
                                                }
                                            >
                                                {v === total ? 'Uang pas' : money(v)}
                                            </button>
                                        ))}
                                    </div>
                                    <div className="cart-change">
                                        <span>Kembalian</span>
                                        <strong>
                                            {money(
                                                Math.max(
                                                    0,
                                                    Number(payment.data.tendered) - total,
                                                ),
                                            )}
                                        </strong>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <label className="field">
                                        Referensi pembayaran (opsional)
                                        <input
                                            value={payment.data.reference_number}
                                            onChange={(e) =>
                                                payment.setData(
                                                    'reference_number',
                                                    e.target.value,
                                                )
                                            }
                                            maxLength={120}
                                            disabled={payment.processing}
                                        />
                                    </label>
                                    <p className="payment-notice">
                                        Pastikan dana sudah diterima. Pembayaran non-tunai
                                        dicatat manual.
                                    </p>
                                </>
                            )}
                            <Button
                                className="checkout-button"
                                disabled={
                                    !cart.length ||
                                    !shift ||
                                    !canOperate ||
                                    payment.processing
                                }
                            >
                                <ReceiptText size={17} />
                                {payment.processing
                                    ? 'Mencatat pembayaran…'
                                    : 'Selesaikan pembayaran'}
                            </Button>
                            {canOperate && !shift && (
                                <p className="shift-hint">
                                    Buka shift sebelum memproses pembayaran.
                                </p>
                            )}
                        </div>
                    </form>
                </aside>
            </div>
            {count > 0 && !cartVisible && (
                <a
                    className="mobile-cart-shortcut"
                    href="#active-order"
                >
                    <span>Lihat pesanan ({count})</span>
                    <strong>{money(total)}</strong>
                </a>
            )}
            <Dialog
                open={!!shiftDialog}
                onOpenChange={(open) => {
                    if (!open && !shiftForm.processing) {
                        setShiftDialog(null);
                    }
                }}
                title={
                    shiftDialog === 'open'
                        ? 'Buka shift kasir'
                        : 'Tutup & rekonsiliasi shift'
                }
                description={
                    shiftDialog === 'open'
                        ? 'Hitung uang modal tunai di laci kasir.'
                        : 'Hitung uang fisik untuk membandingkannya dengan kas seharusnya.'
                }
            >
                <form onSubmit={changeShift}>
                    <Errors errors={shiftForm.errors} />
                    <label className="field">
                        {shiftDialog === 'open'
                            ? 'Kas awal (Rp)'
                            : 'Kas fisik akhir (Rp)'}
                        <input
                            type="number"
                            step="1"
                            min="0"
                            required
                            value={
                                shiftDialog === 'open'
                                    ? shiftForm.data.opening_cash
                                    : shiftForm.data.actual_cash
                            }
                            onChange={(e) =>
                                shiftForm.setData(
                                    shiftDialog === 'open'
                                        ? 'opening_cash'
                                        : 'actual_cash',
                                    e.target.value,
                                )
                            }
                        />
                    </label>
                    <div className="dialog-footer">
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setShiftDialog(null)}
                        >
                            Batal
                        </Button>
                        <Button disabled={shiftForm.processing}>
                            {shiftForm.processing
                                ? 'Menyimpan…'
                                : shiftDialog === 'open'
                                  ? 'Buka shift'
                                  : 'Tutup shift'}
                        </Button>
                    </div>
                </form>
            </Dialog>
            <Dialog
                open={confirmClear}
                onOpenChange={setConfirmClear}
                title="Kosongkan pesanan?"
                description="Seluruh menu di keranjang ini akan dihapus. Transaksi yang sudah selesai tidak terpengaruh."
            >
                <div className="dialog-footer">
                    <Button
                        variant="outline"
                        onClick={() => setConfirmClear(false)}
                    >
                        Batal
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={() => {
                            setCart([]);
                            setKey(crypto.randomUUID());
                            setConfirmClear(false);
                        }}
                    >
                        Kosongkan pesanan
                    </Button>
                </div>
            </Dialog>
        </>
    );
}
