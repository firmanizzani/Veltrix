import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Check, ChevronDown, Menu, Minus, Plus, X } from 'lucide-react';
import { useEffect, useRef, useState, type KeyboardEvent } from 'react';
import { money } from '../lib/utils';
import '../../css/landing.css';

const roles = [
    {
        name: 'Owner',
        title: 'Lihat bisnis secara utuh.',
        description:
            'Pahami penjualan dan biaya lintas cabang, tinjau persetujuan, ' +
            'lalu ambil keputusan dari catatan yang sama.',
        tasks: [
            'Dashboard seluruh cabang',
            'Laporan bisnis dan pengeluaran',
            'Pengguna, cabang, dan persetujuan',
        ],
    },
    {
        name: 'Manager',
        title: 'Jaga ritme setiap hari.',
        description:
            'Pastikan bahan tersedia, pembelian diterima, dan pekerjaan tim ' +
            'berjalan di cabang yang Anda kelola.',
        tasks: [
            'Persediaan dan pembelian bahan',
            'Karyawan dan jadwal kerja',
            'Tindak lanjut operasional cabang',
        ],
    },
    {
        name: 'Kasir',
        title: 'Fokus melayani pelanggan.',
        description:
            'Pilih menu, catat pembayaran, dan tutup shift dengan perhitungan kas ' +
            'yang jelas. Pekerjaan di balik bar tetap mengalir.',
        tasks: [
            'Katalog menu dan pesanan',
            'Pencatatan pembayaran dan struk',
            'Buka dan tutup sesi kasir',
        ],
    },
];

const questions = [
    {
        question: 'Apa saja yang bisa dikelola di VELTRIX?',
        answer:
            'Penjualan dan sesi kasir, produk dan resep, persediaan, supplier ' +
            'dan pembelian, karyawan dan jadwal, pengeluaran, serta laporan bisnis. ' +
            'Setiap bagian mengikuti akses peran dan cabang.',
    },
    {
        question: 'Apakah setiap akun melihat semua cabang?',
        answer:
            'Owner dapat memantau seluruh cabang. Manager dan kasir bekerja sesuai ' +
            'cabang serta kewenangan yang diberikan pada akun mereka.',
    },
    {
        question: 'Bagaimana cara mendapatkan akun?',
        answer:
            'Akun dibuat oleh owner atau administrator bisnis. Jika Anda bagian dari ' +
            'tim, minta akun kepada pengelola lalu masuk melalui halaman login. ' +
            'Tidak ada pendaftaran publik.',
    },
    {
        question: 'Apakah pembayaran diproses otomatis?',
        answer:
            'VELTRIX mencatat pembayaran tunai, QRIS, kartu, dan e-wallet. ' +
            'Konfirmasi pembayaran tetap dilakukan oleh kasir; halaman ini ' +
            'tidak terhubung ke layanan pemrosesan pembayaran otomatis.',
    },
];

function Brand() {
    return (
        <Link
            href="/"
            className="landing-brand"
            aria-label="VELTRIX, beranda"
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
            VELTRIX
        </Link>
    );
}

export default function Landing() {
    const { auth } = usePage<{ auth: { user: { role: string } | null } }>().props;
    const destination = auth.user
        ? auth.user.role === 'cashier'
            ? '/pos'
            : '/dashboard'
        : '/login';
    const action = auth.user ? 'Buka ruang kerja' : 'Masuk ke VELTRIX';
    const [navigationOpen, setNavigationOpen] = useState(false);
    const [role, setRole] = useState(0);
    const [quantity, setQuantity] = useState(1);
    const page = useRef<HTMLDivElement>(null);
    const menuButton = useRef<HTMLButtonElement>(null);
    const roleButtons = useRef<(HTMLButtonElement | null)[]>([]);
    const currentRole = roles[role];

    useEffect(() => {
        const target = window.location.hash.slice(1);
        if (!target) {
            return;
        }
        const frame = requestAnimationFrame(() => {
            document.getElementById(target)?.scrollIntoView({ block: 'start' });
        });
        return () => cancelAnimationFrame(frame);
    }, []);

    useEffect(() => {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('landing-in-view');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12 },
        );
        page.current
            ?.querySelectorAll('[data-reveal]')
            .forEach((element) => observer.observe(element));
        return () => observer.disconnect();
    }, []);

    function changeRole(event: KeyboardEvent<HTMLButtonElement>, index: number) {
        let next = index;
        if (event.key === 'ArrowRight') {
            next = (index + 1) % roles.length;
        } else if (event.key === 'ArrowLeft') {
            next = (index + roles.length - 1) % roles.length;
        } else if (event.key === 'Home') {
            next = 0;
        } else if (event.key === 'End') {
            next = roles.length - 1;
        } else {
            return;
        }
        event.preventDefault();
        setRole(next);
        roleButtons.current[next]?.focus();
    }

    return (
        <div
            className="landing"
            ref={page}
        >
            <Head title="VELTRIX">
                <meta
                    name="description"
                    content={
                        'VELTRIX menghubungkan penjualan, persediaan bahan, tim, ' +
                        'dan laporan bisnis kedai kopi dalam satu ruang kerja.'
                    }
                />
            </Head>
            <a
                className="landing-skip"
                href="#landing-content"
            >
                Lewati ke konten
            </a>
            <header
                className="landing-header"
                onKeyDown={(event) => {
                    if (event.key === 'Escape' && navigationOpen) {
                        setNavigationOpen(false);
                        menuButton.current?.focus();
                    }
                }}
            >
                <Brand />
                <nav
                    className="landing-navigation"
                    aria-label="Navigasi halaman publik"
                >
                    <a href="#alur">Cara kerja</a>
                    <a href="#peran">Untuk tim Anda</a>
                    <a href="#pertanyaan">Pertanyaan umum</a>
                </nav>
                <Link
                    className="landing-login"
                    href={destination}
                >
                    {auth.user ? 'Ruang kerja' : 'Masuk'}
                    <ArrowUpRight size={17} />
                </Link>
                <button
                    ref={menuButton}
                    className="landing-menu-toggle"
                    aria-label={navigationOpen ? 'Tutup menu' : 'Buka menu'}
                    aria-expanded={navigationOpen}
                    aria-controls="landing-mobile-menu"
                    onClick={() => setNavigationOpen(!navigationOpen)}
                >
                    {navigationOpen ? <X size={22} /> : <Menu size={22} />}
                </button>
                <nav
                    id="landing-mobile-menu"
                    className="landing-mobile-navigation"
                    hidden={!navigationOpen}
                    aria-label="Menu halaman publik"
                >
                    {[
                        ['Cara kerja', '#alur'],
                        ['Untuk tim Anda', '#peran'],
                        ['Pertanyaan umum', '#pertanyaan'],
                    ].map(([label, href]) => (
                        <a
                            key={href}
                            href={href}
                            onClick={() => {
                                setNavigationOpen(false);
                                menuButton.current?.focus();
                            }}
                        >
                            {label}
                        </a>
                    ))}
                </nav>
            </header>

            <main id="landing-content">
                <section
                    className="landing-hero"
                    aria-labelledby="landing-title"
                >
                    <h1 id="landing-title">
                        <span className="landing-title-line">
                            Racikan{' '}
                            <img
                                className="landing-title-image"
                                src="/images/menu/espresso.jpg"
                                alt=""
                                width={160}
                                height={100}
                            />{' '}
                            beragam.
                        </span>
                        <span className="landing-title-line">Kendali seirama.</span>
                    </h1>
                    <div className="landing-hero-intro">
                        <p>
                            Di depan bar, kopi diracik. Di baliknya, VELTRIX menghubungkan
                            penjualan, bahan, dan tim agar kedai berjalan seirama.
                        </p>
                        <div className="landing-hero-action">
                            <Link
                                className="landing-cta"
                                href={destination}
                            >
                                {action}
                                <ArrowUpRight size={20} />
                            </Link>
                            <span className="landing-access-note">
                                Untuk owner, manager, dan kasir.
                            </span>
                        </div>
                    </div>
                    <div className="landing-preview">
                        <figure className="landing-photograph">
                            <img
                                src="/images/landing/coffee-craft.jpg"
                                alt="Barista menuangkan latte art ke cangkir di depan mesin espresso"
                                width={1122}
                                height={1402}
                                fetchPriority="high"
                            />
                            <figcaption>
                                Di balik setiap cangkir, ada alur yang saling terhubung.
                            </figcaption>
                        </figure>
                        <div className="landing-preview-console">
                            <div
                                className="landing-order-demo"
                                data-ui
                                data-reveal
                            >
                                <div className="landing-demo-heading">
                                    <strong>Di meja kasir</strong>
                                    <span>Contoh alur pesanan</span>
                                </div>
                                <div className="landing-demo-product">
                                    <img
                                        src="/images/menu/kopi-susu-gula-aren.jpg"
                                        alt="Kopi susu gula aren"
                                        width={80}
                                        height={80}
                                        loading="lazy"
                                    />
                                    <div>
                                        <strong>Kopi Susu Gula Aren</strong>
                                        <span>{money(22000)}</span>
                                    </div>
                                    <div className="landing-quantity">
                                        <button
                                            aria-label="Kurangi jumlah kopi"
                                            disabled={quantity === 1}
                                            onClick={() =>
                                                setQuantity((current) =>
                                                    Math.max(1, current - 1),
                                                )
                                            }
                                        >
                                            <Minus size={15} />
                                        </button>
                                        <output aria-label="Jumlah kopi">
                                            {quantity}
                                        </output>
                                        <button
                                            aria-label="Tambah jumlah kopi"
                                            disabled={quantity === 5}
                                            onClick={() =>
                                                setQuantity((current) =>
                                                    Math.min(5, current + 1),
                                                )
                                            }
                                        >
                                            <Plus size={15} />
                                        </button>
                                    </div>
                                </div>
                                <div
                                    className="landing-demo-total"
                                    aria-live="polite"
                                >
                                    <span>Total pesanan</span>
                                    <strong>{money(quantity * 22000)}</strong>
                                </div>
                                <div className="landing-demo-stock">
                                    <Check size={18} />
                                    <div>
                                        <strong>Resep ikut menghitung bahan</strong>
                                        <span>
                                            Contoh resep: {quantity * 18} g kopi dan{' '}
                                            {quantity * 120} ml susu untuk {quantity}{' '}
                                            gelas.
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <p className="landing-demo-note">
                                Coba tombol + dan −. Simulasi ini tidak membuat transaksi
                                atau mengubah stok kedai.
                            </p>
                        </div>
                    </div>
                </section>
                <section
                    id="alur"
                    className="landing-flow landing-section"
                >
                    <div className="landing-flow-intro">
                        <h2 data-reveal>
                            Dari bar,
                            <br />
                            sampai laporan.
                        </h2>
                        <p data-reveal>
                            Satu pesanan tidak berhenti di struk. Bahan ikut terhitung,
                            persediaan terpantau, dan penjualan bisa ditelusuri.
                        </p>
                    </div>
                    <div className="landing-process-list">
                        <div data-reveal>
                            <h3>Pesanan tercatat</h3>
                            <p>
                                Kasir memilih menu dan mencatat pembayaran. Struk dan
                                riwayat transaksi tersimpan.
                            </p>
                        </div>
                        <div data-reveal>
                            <h3>Bahan terpantau</h3>
                            <p>
                                Persediaan mengikuti resep. Tim bisa meninjau stok minimum
                                dan kebutuhan pembelian.
                            </p>
                        </div>
                        <div data-reveal>
                            <h3>Angka bisa ditelusuri</h3>
                            <p>
                                Penjualan, biaya bahan, dan pengeluaran masuk ke laporan
                                untuk ditinjau pengelola.
                            </p>
                        </div>
                    </div>
                </section>
                <section
                    id="peran"
                    className="landing-roles landing-section"
                >
                    <div className="landing-role-workspace">
                        {' '}
                        <h2 data-reveal>
                            Satu kedai.
                            <br />
                            Banyak tangan baik.
                        </h2>
                        <p data-reveal>
                            Setiap orang punya fokusnya. Ruang kerja mengikuti peran dan
                            cabang, supaya tim melihat yang mereka perlukan.
                        </p>
                        <div
                            className="landing-role-tabs"
                            role="tablist"
                            aria-label="Pilih peran tim"
                        >
                            {roles.map((entry, index) => (
                                <button
                                    key={entry.name}
                                    id={`role-tab-${index}`}
                                    ref={(element) => {
                                        roleButtons.current[index] = element;
                                    }}
                                    role="tab"
                                    aria-selected={role === index}
                                    aria-controls="landing-role-content"
                                    tabIndex={role === index ? 0 : -1}
                                    onClick={() => setRole(index)}
                                    onKeyDown={(event) => changeRole(event, index)}
                                >
                                    {entry.name}
                                </button>
                            ))}
                        </div>
                        <div
                            className="landing-role-content"
                            id="landing-role-content"
                            role="tabpanel"
                            aria-labelledby={`role-tab-${role}`}
                            tabIndex={0}
                        >
                            <h3>{currentRole.title}</h3>
                            <p>{currentRole.description}</p>
                            <ul>
                                {currentRole.tasks.map((task) => (
                                    <li key={task}>
                                        <Check size={18} />
                                        {task}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                    <div className="landing-role-images">
                        {' '}
                        <div
                            className="landing-pair-photographs"
                            data-reveal
                        >
                            <img
                                src="/images/menu/espresso.jpg"
                                alt="Secangkir espresso"
                                width={400}
                                height={400}
                                loading="lazy"
                            />
                            <img
                                src="/images/menu/butter-croissant.jpg"
                                alt="Croissant berlapis untuk menemani kopi"
                                width={400}
                                height={400}
                                loading="lazy"
                            />
                        </div>
                        <p className="landing-photo-note">
                            Dari bar kopi sampai dapur, tetap dalam satu alur.
                        </p>
                    </div>
                </section>
                <section
                    className="landing-full-width"
                    aria-labelledby="landing-business-title"
                >
                    <h2
                        id="landing-business-title"
                        data-reveal
                    >
                        Kedai tumbuh.
                        <br />
                        Alur tetap utuh.
                    </h2>
                    <div data-reveal>
                        <p>
                            Menu boleh punya karakter berbeda. Cara Anda menjaga kedai
                            tetap bisa terhubung—dari resep dan supplier, hingga jadwal
                            tim dan laporan setiap cabang.
                        </p>
                        <Link
                            href={destination}
                            className="landing-cta landing-cta-light"
                        >
                            {action}
                            <ArrowUpRight size={20} />
                        </Link>
                    </div>
                </section>

                <section
                    id="pertanyaan"
                    className="landing-faq"
                >
                    <div>
                        <h2 data-reveal>Sebelum mulai.</h2>
                        <p data-reveal>
                            Beberapa hal yang perlu Anda tahu tentang ruang kerja VELTRIX.
                        </p>
                    </div>
                    <div className="landing-faq-list">
                        {questions.map((entry) => (
                            <details key={entry.question}>
                                <summary>
                                    {entry.question}
                                    <ChevronDown size={20} />
                                </summary>
                                <p>{entry.answer}</p>
                            </details>
                        ))}
                    </div>
                </section>
            </main>

            <footer className="landing-footer">
                <div>
                    <Brand />
                    <p>
                        Kopi punya cerita.
                        <br />
                        Bisnis punya arah.
                    </p>
                </div>
                <div className="landing-footer-links">
                    <a href="#alur">Cara kerja</a>
                    <a href="#peran">Peran tim</a>
                    <Link href={destination}>
                        {auth.user ? 'Ruang kerja' : 'Masuk akun'}
                    </Link>
                </div>
                <span>© {new Date().getFullYear()} VELTRIX</span>
            </footer>
        </div>
    );
}
