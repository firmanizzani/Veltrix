import {
    Check,
    ChevronDown,
    Coffee,
    History,
    Minus,
    Plus,
    ReceiptText,
    Search,
    ShoppingBag,
} from 'lucide-react';
import { useState } from 'react';
import { money } from '../lib/utils';
import '../../css/landing-pos-preview.css';

const menu = [
    {
        id: 'aren',
        name: 'Kopi Susu Gula Aren',
        price: 22000,
        image: 'kopi-susu-gula-aren',
        category: 'Coffee',
    },
    {
        id: 'americano',
        name: 'Americano',
        price: 20000,
        image: 'americano',
        category: 'Coffee',
    },
    {
        id: 'latte',
        name: 'Caffè Latte',
        price: 26000,
        image: 'caffe-latte',
        category: 'Coffee',
    },
    {
        id: 'croissant',
        name: 'Butter Croissant',
        price: 18000,
        image: 'butter-croissant',
        category: 'Food',
    },
];

export function LandingPosPreview() {
    const [cart, setCart] = useState<Record<string, number>>({ aren: 1 });
    const [category, setCategory] = useState('Semua menu');
    const [search, setSearch] = useState('');
    const [payment, setPayment] = useState('Tunai');
    const [receipt, setReceipt] = useState(false);
    const items = menu.filter((product) => cart[product.id] > 0);
    const total = items.reduce(
        (sum, product) => sum + product.price * cart[product.id],
        0,
    );
    const count = items.reduce((sum, product) => sum + cart[product.id], 0);
    const visibleMenu = menu.filter(
        (product) =>
            (category === 'Semua menu' || product.category === category) &&
            product.name.toLowerCase().includes(search.toLowerCase()),
    );

    function changeQuantity(id: string, change: number) {
        setReceipt(false);
        setCart((current) => ({
            ...current,
            [id]: Math.max(0, Math.min(5, (current[id] ?? 0) + change)),
        }));
    }

    return (
        <div
            className="landing-pos-preview"
            data-ui
            aria-label="Contoh interaktif aplikasi POS VELTRIX"
        >
            <div className="pos-preview-chrome">
                <strong>
                    <svg
                        viewBox="0 0 32 32"
                        aria-hidden="true"
                    >
                        <path
                            d="M2 5h8l6 12L22 5h8L18 28h-4z"
                            fill="currentColor"
                        />
                    </svg>
                    VELTRIX
                </strong>
                <span>Contoh tampilan</span>
            </div>
            <div className="pos-preview-body">
                <div
                    className="pos-preview-sidebar"
                    aria-hidden="true"
                >
                    <div className="pos-preview-active">
                        <ShoppingBag size={16} />
                        Point of Sale
                    </div>
                    <div>
                        <ReceiptText size={16} />
                        Transaksi
                    </div>
                    <div>
                        <History size={16} />
                        Sesi kasir
                    </div>
                    <span>
                        <Coffee size={18} />
                        Veltrix Coffee
                    </span>
                </div>
                <div className="pos-preview-catalog">
                    <h2>Point of Sale</h2>
                    <label className="pos-preview-search">
                        <Search size={14} />
                        <input
                            type="search"
                            aria-label="Cari menu contoh"
                            placeholder="Cari menu..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </label>
                    <div
                        className="pos-preview-categories"
                        aria-label="Kategori menu contoh"
                    >
                        {['Semua menu', 'Coffee', 'Food'].map((entry) => (
                            <button
                                key={entry}
                                aria-pressed={entry === category}
                                onClick={() => setCategory(entry)}
                            >
                                {entry}
                            </button>
                        ))}
                    </div>
                    <div className="pos-preview-products">
                        {visibleMenu.map((product) => (
                            <button
                                key={product.id}
                                className="pos-preview-product"
                                onClick={() => changeQuantity(product.id, 1)}
                                disabled={cart[product.id] >= 5}
                                aria-label={`Tambah ${product.name} ke contoh pesanan`}
                            >
                                <img
                                    src={`/images/menu/${product.image}.jpg`}
                                    alt=""
                                    width={240}
                                    height={150}
                                />
                                <span>{product.name}</span>
                                <strong>{money(product.price)}</strong>
                            </button>
                        ))}
                        {!visibleMenu.length && (
                            <p className="pos-preview-empty">Menu tidak ditemukan.</p>
                        )}
                    </div>
                </div>
                <div className="pos-preview-order">
                    <h3>
                        Pesanan baru <span>{count}</span>
                    </h3>
                    <div className="pos-preview-lines">
                        {items.map((product) => (
                            <div
                                className="pos-preview-line"
                                key={product.id}
                            >
                                <span>{product.name}</span>
                                <strong>{money(product.price * cart[product.id])}</strong>
                                <div className="pos-preview-quantity">
                                    <button
                                        onClick={() => changeQuantity(product.id, -1)}
                                        aria-label={`Kurangi ${product.name}`}
                                    >
                                        <Minus size={12} />
                                    </button>
                                    <output aria-label={`Jumlah ${product.name}`}>
                                        {cart[product.id]}
                                    </output>
                                    <button
                                        onClick={() => changeQuantity(product.id, 1)}
                                        aria-label={`Tambah ${product.name}`}
                                        disabled={cart[product.id] >= 5}
                                    >
                                        <Plus size={12} />
                                    </button>
                                </div>
                            </div>
                        ))}
                        {!items.length && (
                            <p className="pos-preview-empty">
                                Pilih menu untuk mencoba pesanan.
                            </p>
                        )}
                    </div>
                    <div className="pos-preview-checkout">
                        <div
                            className="pos-preview-total"
                            aria-live="polite"
                        >
                            <span>Total</span>
                            <strong>{money(total)}</strong>
                        </div>
                        <label className="pos-preview-payment">
                            <span className="sr-only">Metode pembayaran contoh</span>
                            <select
                                value={payment}
                                onChange={(event) => setPayment(event.target.value)}
                            >
                                <option>Tunai</option>
                                <option>QRIS</option>
                                <option>Kartu</option>
                                <option>E-Wallet</option>
                            </select>
                            <ChevronDown size={14} />
                        </label>
                        <button
                            className="pos-preview-receipt"
                            disabled={!items.length}
                            onClick={() => setReceipt(true)}
                        >
                            {receipt ? (
                                <>
                                    <Check size={15} />
                                    Contoh struk siap
                                </>
                            ) : (
                                'Buat contoh struk'
                            )}
                        </button>
                        {receipt && (
                            <p role="status">
                                Contoh {count} produk, {money(total)}, {payment}. Tidak
                                ada transaksi tersimpan.
                            </p>
                        )}
                    </div>
                </div>
            </div>
            <p className="pos-preview-note">
                Coba pilih menu. Ini simulasi, bukan transaksi kedai.
            </p>
        </div>
    );
}
