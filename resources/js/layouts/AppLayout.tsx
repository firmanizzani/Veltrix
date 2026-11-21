import { Link, router, usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    ShoppingBag,
    ReceiptText,
    Coffee,
    Boxes,
    BookOpen,
    Truck,
    ClipboardCheck,
    ArrowLeftRight,
    Users,
    CalendarDays,
    Wallet,
    Banknote,
    ChartNoAxesCombined,
    Building2,
    UserRoundCog,
    History,
    Settings2,
    Bell,
    Sparkles,
    LogOut,
    Menu,
    X,
    BadgePercent,
    ChevronRight,
    PackagePlus,
    CircleHelp,
    CheckCheck,
} from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { Toaster, toast } from 'sonner';
import { labels, type Shared } from '../lib/utils';
import { Button } from '../components/ui/button';

type NavItem = [string, string, typeof Coffee, string[]];
const all = ['owner', 'manager', 'cashier'];
const ops = ['owner', 'manager'];
const owner = ['owner'];
const groups: { title: string; items: NavItem[] }[] = [
    {
        title: 'Penjualan',
        items: [
            ['Ringkasan', '/dashboard', LayoutDashboard, ops],
            ['Point of Sale', '/pos', ShoppingBag, all],
            ['Transaksi', '/manage/orders', ReceiptText, all],
            ['Sesi kasir', '/manage/shifts', History, all],
            ['Pengembalian dana', '/manage/refunds', ArrowLeftRight, all],
        ],
    },
    {
        title: 'Inventori',
        items: [
            ['Produk & menu', '/manage/products', Coffee, ops],
            ['Kategori menu', '/manage/categories', Boxes, owner],
            ['Harga & ketersediaan', '/manage/branch-prices', BadgePercent, ops],
            ['Bahan & resep', '/manage/recipes', BookOpen, ops],
            ['Bahan baku', '/manage/ingredients', Boxes, ops],
            ['Persediaan cabang', '/manage/inventory', ClipboardCheck, ops],
            ['Riwayat stok', '/manage/movements', History, ops],
            ['Supplier', '/manage/suppliers', Truck, ops],
            ['Pembelian bahan', '/manage/purchases', PackagePlus, ops],
            ['Transfer stok', '/manage/transfers', ArrowLeftRight, ops],
        ],
    },
    {
        title: 'Operasional',
        items: [
            ['Karyawan', '/manage/employees', Users, ops],
            ['Jadwal kerja', '/manage/schedules', CalendarDays, ops],
            ['Notifikasi', '/alerts', Bell, ops],
        ],
    },
    {
        title: 'Keuangan',
        items: [
            ['Pengeluaran', '/manage/expenses', Wallet, ops],
            ['Payroll', '/manage/payroll', Banknote, owner],
            ['Laporan bisnis', '/reports', ChartNoAxesCombined, ops],
            ['Persetujuan harga', '/manage/price-requests', CheckCheck, ops],
        ],
    },
    {
        title: 'Intelligence',
        items: [['AI & insight bisnis', '/intelligence', Sparkles, owner]],
    },
    {
        title: 'Administrasi',
        items: [
            ['Cabang', '/manage/branches', Building2, owner],
            ['Pengguna', '/manage/users', UserRoundCog, owner],
            ['Audit log', '/manage/audit', History, owner],
        ],
    },
];

export default function AppLayout({ children }: { children: ReactNode }) {
    const { auth, flash } = usePage<Shared>().props;
    const { user } = auth;
    const { url } = usePage();
    const [menu, setMenu] = useState(false);
    const [loading, setLoading] = useState(false);
    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
    }, [flash]);
    useEffect(() => {
        const a = router.on('start', () => setLoading(true));
        const b = router.on('finish', () => setLoading(false));
        const c = router.on('navigate', () => setMenu(false));
        return () => {
            a();
            b();
            c();
        };
    }, []);
    const current =
        groups
            .flatMap((g) => g.items)
            .find((item) => url.split('?')[0] === item[1])?.[0] ??
        (url.startsWith('/orders/') ? 'Detail transaksi' : 'Profil saya');
    return (
        <div className="app-shell">
            <a
                href="#main-content"
                className="skip-link"
            >
                Lewati ke konten
            </a>
            {menu && (
                <button
                    aria-label="Tutup navigasi"
                    className="nav-backdrop"
                    onClick={() => setMenu(false)}
                />
            )}
            <aside
                className={`sidebar ${menu ? 'sidebar-open' : ''}`}
                aria-label="Navigasi utama"
            >
                <Link
                    href={user.role === 'cashier' ? '/pos' : '/dashboard'}
                    className="brand"
                >
                    <svg
                        viewBox="0 0 32 32"
                        aria-hidden="true"
                    >
                        <path
                            d="M2 5h8l6 12L22 5h8L18 28h-4z"
                            fill="currentColor"
                        />
                    </svg>
                    <span>VELTRIX</span>
                </Link>
                <div className="workspace">
                    <Coffee size={18} />
                    <div>
                        <strong>Veltrix Coffee</strong>
                        <span>{user.branch?.city ?? 'Seluruh cabang'}</span>
                    </div>
                </div>
                <nav>
                    {groups.map((group) => {
                        const items = group.items.filter((item) =>
                            item[3].includes(user.role),
                        );
                        return items.length ? (
                            <div
                                className="nav-group"
                                key={group.title}
                            >
                                <p>{group.title}</p>
                                {items.map(([title, href, Icon]) => (
                                    <Link
                                        key={href}
                                        href={href}
                                        className={`nav-item ${url.split('?')[0] === href ? 'nav-active' : ''}`}
                                        aria-current={
                                            url.split('?')[0] === href
                                                ? 'page'
                                                : undefined
                                        }
                                    >
                                        <Icon
                                            size={17}
                                            strokeWidth={1.6}
                                        />
                                        <span>{title}</span>
                                    </Link>
                                ))}
                            </div>
                        ) : null;
                    })}
                </nav>
                <div className="sidebar-bottom">
                    <Link
                        href="/profile"
                        className="user-link"
                    >
                        <span className="avatar">
                            {user.name
                                .split(' ')
                                .slice(0, 2)
                                .map((n) => n[0])
                                .join('')}
                        </span>
                        <span>
                            <strong>{user.name}</strong>
                            <small>{labels[user.role]}</small>
                        </span>
                        <Settings2 size={16} />
                    </Link>
                    <button
                        className="logout"
                        onClick={() => router.post('/logout')}
                    >
                        <LogOut size={15} />
                        Keluar
                    </button>
                </div>
            </aside>
            <div className="app-main">
                <header className="topbar">
                    <div className="breadcrumbs">
                        <button
                            className="mobile-menu"
                            aria-label="Buka navigasi"
                            aria-expanded={menu}
                            onClick={() => setMenu(!menu)}
                        >
                            {menu ? <X size={20} /> : <Menu size={20} />}
                        </button>
                        <span>Ruang kerja</span>
                        <ChevronRight size={13} />
                        <strong>{current}</strong>
                    </div>
                    <div className="topbar-right">
                        <span className="role-context">
                            {user.branch?.name ?? 'Seluruh cabang'}
                        </span>
                        {user.role !== 'cashier' && (
                            <Link
                                href="/alerts"
                                aria-label="Buka notifikasi"
                                className="button button-ghost button-icon"
                            >
                                <Bell size={18} />
                            </Link>
                        )}
                        <Link
                            href="/profile"
                            aria-label="Buka profil"
                            className="top-avatar"
                        >
                            {user.name[0]}
                        </Link>
                    </div>
                </header>
                <main
                    id="main-content"
                    className="main-sheet"
                    aria-busy={loading}
                >
                    {loading && (
                        <div
                            className="navigation-progress"
                            role="progressbar"
                            aria-label="Memuat halaman"
                        />
                    )}
                    {children}
                </main>
            </div>
            <Toaster
                position="top-right"
                richColors
                closeButton
            />
        </div>
    );
}
