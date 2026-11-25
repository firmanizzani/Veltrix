import { Fragment, useEffect, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Download, RefreshCw } from 'lucide-react';
import { Button } from '../components/ui/button';
import { Empty, Errors, PageTitle } from '../components/common';
import { ReportTabs } from '../components/report-tabs';
import { money, number, type Shared } from '../lib/utils';

type Filters = {
    start: string;
    end: string;
    branch_id: string | number;
    category_id: string | number;
    search: string;
    sort: string;
};
type MenuRow = {
    id: number;
    name: string;
    sku: string;
    category: string;
    is_active: boolean;
    sold_quantity: number;
    refunded_quantity: number;
    quantity: number;
    gross_amount: number;
    refund_amount: number;
    amount: number;
};
type BranchRow = {
    branch_id: number;
    branch: string;
    sold_quantity: number;
    refunded_quantity: number;
    quantity: number;
    amount: number;
};
type Props = {
    filters: Filters;
    products: {
        data: MenuRow[];
        total: number;
        from: number | null;
        to: number | null;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    totals: {
        menus: number;
        sold_quantity: number;
        refunded_quantity: number;
        quantity: number;
        amount: number;
    };
    branches: { id: number; name: string }[];
    categories: { id: number; name: string }[];
    detail: {
        product: { id: number; name: string };
        rows: BranchRow[];
        truncated: boolean;
    } | null;
};
function queryString(values: Record<string, string | number | undefined>) {
    return new URLSearchParams(
        Object.entries(values)
            .filter(([, value]) => value !== undefined && value !== '')
            .map(([key, value]) => [key, String(value)]),
    ).toString();
}

export default function ProductSalesReport({
    filters,
    products,
    totals,
    branches,
    categories,
    detail,
}: Props) {
    const {
        auth: { user },
        errors,
    } = usePage<Shared>().props;
    const [draft, setDraft] = useState(filters);
    useEffect(() => setDraft(filters), [filters]);
    const today = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date());
    const appliedQuery = queryString(filters);
    return (
        <>
            <Head title="Laporan bisnis" />
            <PageTitle
                title="Laporan bisnis"
                description="Rincian seluruh menu, dari unit terjual hingga penjualan bersih."
                action={
                    <Button
                        variant="outline"
                        asChild
                    >
                        <a href={`/reports/products/export?${appliedQuery}`}>
                            <Download size={16} />
                            Ekspor CSV
                        </a>
                    </Button>
                }
            />
            <ReportTabs
                active="products"
                filters={filters}
            />
            <Errors errors={errors ?? {}} />
            <form
                className="menu-sales-filters"
                onSubmit={(event) => {
                    event.preventDefault();
                    router.get('/reports/products', draft, {
                        preserveState: true,
                        preserveScroll: true,
                    });
                }}
            >
                <label>
                    Dari
                    <input
                        type="date"
                        required
                        value={draft.start}
                        max={draft.end}
                        onInput={(event) => {
                            const value = event.currentTarget.value;
                            setDraft((current) => ({ ...current, start: value }));
                        }}
                    />
                </label>
                <label>
                    Sampai
                    <input
                        type="date"
                        required
                        value={draft.end}
                        min={draft.start}
                        max={today}
                        onInput={(event) => {
                            const value = event.currentTarget.value;
                            setDraft((current) => ({ ...current, end: value }));
                        }}
                    />
                </label>
                {user.role === 'owner' && (
                    <label>
                        Cabang
                        <select
                            value={draft.branch_id}
                            onChange={(event) =>
                                setDraft({ ...draft, branch_id: event.target.value })
                            }
                        >
                            <option value="">Seluruh cabang</option>
                            {branches.map((branch) => (
                                <option
                                    key={branch.id}
                                    value={branch.id}
                                >
                                    {branch.name}
                                </option>
                            ))}
                        </select>
                    </label>
                )}
                <label>
                    Kategori
                    <select
                        value={draft.category_id}
                        onChange={(event) =>
                            setDraft({ ...draft, category_id: event.target.value })
                        }
                    >
                        <option value="">Semua kategori</option>
                        {categories.map((category) => (
                            <option
                                key={category.id}
                                value={category.id}
                            >
                                {category.name}
                            </option>
                        ))}
                    </select>
                </label>
                <label className="menu-sales-search">
                    Cari menu atau SKU
                    <input
                        type="search"
                        maxLength={120}
                        placeholder="Contoh: Matcha Latte"
                        value={draft.search}
                        onChange={(event) =>
                            setDraft({ ...draft, search: event.target.value })
                        }
                    />
                </label>
                <label>
                    Urutkan
                    <select
                        value={draft.sort}
                        onChange={(event) =>
                            setDraft({ ...draft, sort: event.target.value })
                        }
                    >
                        <option value="quantity_desc">Unit bersih terbanyak</option>
                        <option value="amount_desc">Penjualan bersih tertinggi</option>
                        <option value="name_asc">Nama menu A–Z</option>
                    </select>
                </label>
                <div className="menu-sales-filter-actions">
                    <Button type="submit">Terapkan</Button>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() =>
                            router.get(
                                '/reports/products',
                                {
                                    start: filters.start,
                                    end: filters.end,
                                    branch_id: filters.branch_id,
                                },
                                { preserveScroll: true },
                            )
                        }
                    >
                        Reset
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        aria-label="Muat ulang penjualan menu"
                        onClick={() => router.reload()}
                    >
                        <RefreshCw size={16} />
                    </Button>
                </div>
            </form>
            <dl
                className="menu-sales-totals"
                aria-label="Total hasil filter"
            >
                <div>
                    <dt>Menu dalam hasil</dt>
                    <dd>{number(totals.menus)}</dd>
                </div>
                <div>
                    <dt>Unit terjual</dt>
                    <dd>{number(totals.sold_quantity)}</dd>
                </div>
                <div>
                    <dt>Unit refund</dt>
                    <dd>{number(totals.refunded_quantity)}</dd>
                </div>
                <div>
                    <dt>Unit bersih</dt>
                    <dd>{number(totals.quantity)}</dd>
                </div>
                <div>
                    <dt>Penjualan bersih</dt>
                    <dd>{money(totals.amount)}</dd>
                </div>
            </dl>
            <section
                className="menu-sales-results"
                aria-labelledby="menu-sales-heading"
            >
                <div className="menu-sales-heading">
                    <div>
                        <h2 id="menu-sales-heading">Penjualan per menu</h2>
                        <p>
                            {filters.start} — {filters.end} ·{' '}
                            {user.role === 'owner'
                                ? (branches.find(
                                      (branch) =>
                                          String(branch.id) === String(filters.branch_id),
                                  )?.name ?? 'Seluruh cabang')
                                : user.branch?.name}
                        </p>
                    </div>
                    <span>{number(products.total)} menu</span>
                </div>
                <p className="menu-sales-note">
                    Termasuk menu tanpa penjualan. Refund dihitung pada tanggal diproses.
                    Klik nama menu untuk rincian cabang.
                </p>
                <div className="table-scroll">
                    <table className="menu-sales-table">
                        <caption className="sr-only">
                            Seluruh menu dan penjualannya pada periode serta cabang
                            terpilih
                        </caption>
                        <thead>
                            <tr>
                                <th>Menu</th>
                                <th>Kategori</th>
                                <th className="num">Unit terjual</th>
                                <th className="num">Refund</th>
                                <th className="num">Unit bersih</th>
                                <th className="num">Penjualan bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.map((product) => (
                                <Fragment key={product.id}>
                                    <tr
                                        className={
                                            detail?.product.id === product.id
                                                ? 'menu-sales-selected'
                                                : undefined
                                        }
                                    >
                                        <td>
                                            <Link
                                                className="menu-sales-product-link"
                                                href={`/reports/products?${queryString({ ...filters, page: products.current_page, product_id: detail?.product.id === product.id ? undefined : product.id })}`}
                                                preserveScroll
                                                preserveState
                                                aria-expanded={
                                                    detail?.product.id === product.id
                                                }
                                                aria-controls={
                                                    detail?.product.id === product.id
                                                        ? `menu-branches-${product.id}`
                                                        : undefined
                                                }
                                            >
                                                {product.name}
                                                {detail?.product.id === product.id ? (
                                                    <ChevronUp size={15} />
                                                ) : (
                                                    <ChevronDown size={15} />
                                                )}
                                            </Link>
                                            <small>
                                                {product.sku}
                                                {!product.is_active ? ' · Nonaktif' : ''}
                                            </small>
                                        </td>
                                        <td>{product.category}</td>
                                        <td className="num">
                                            {number(product.sold_quantity)}
                                        </td>
                                        <td className="num">
                                            {number(product.refunded_quantity)}
                                        </td>
                                        <td className="num">
                                            {number(product.quantity)}
                                        </td>
                                        <td className="num">{money(product.amount)}</td>
                                    </tr>
                                    {detail?.product.id === product.id && (
                                        <tr className="menu-sales-detail">
                                            <td colSpan={6}>
                                                <div id={`menu-branches-${product.id}`}>
                                                    <h3>
                                                        Rincian {product.name} per cabang
                                                    </h3>
                                                    <table>
                                                        <thead>
                                                            <tr>
                                                                <th>Cabang</th>
                                                                <th className="num">
                                                                    Terjual
                                                                </th>
                                                                <th className="num">
                                                                    Refund
                                                                </th>
                                                                <th className="num">
                                                                    Bersih
                                                                </th>
                                                                <th className="num">
                                                                    Penjualan bersih
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {detail.rows.map((branch) => (
                                                                <tr
                                                                    key={branch.branch_id}
                                                                >
                                                                    <td>
                                                                        {branch.branch}
                                                                    </td>
                                                                    <td className="num">
                                                                        {number(
                                                                            branch.sold_quantity,
                                                                        )}
                                                                    </td>
                                                                    <td className="num">
                                                                        {number(
                                                                            branch.refunded_quantity,
                                                                        )}
                                                                    </td>
                                                                    <td className="num">
                                                                        {number(
                                                                            branch.quantity,
                                                                        )}
                                                                    </td>
                                                                    <td className="num">
                                                                        {money(
                                                                            branch.amount,
                                                                        )}
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                    {detail.truncated && (
                                                        <p>
                                                            Rincian dibatasi. Pilih satu
                                                            cabang untuk melihat hasil
                                                            lengkapnya.
                                                        </p>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </Fragment>
                            ))}
                        </tbody>
                    </table>
                    {!products.data.length && (
                        <Empty>
                            Tidak ada menu yang cocok dengan filter. Ubah pencarian atau
                            kategori.
                        </Empty>
                    )}
                </div>
                <footer className="menu-sales-pagination">
                    <span>
                        {products.from ?? 0}–{products.to ?? 0} dari{' '}
                        {number(products.total)} menu
                    </span>
                    <nav aria-label="Halaman penjualan menu">
                        {products.prev_page_url && (
                            <Link
                                href={products.prev_page_url}
                                preserveScroll
                            >
                                Sebelumnya
                            </Link>
                        )}
                        <span>
                            Halaman {products.current_page} dari {products.last_page}
                        </span>
                        {products.next_page_url && (
                            <Link
                                href={products.next_page_url}
                                preserveScroll
                            >
                                Berikutnya
                            </Link>
                        )}
                    </nav>
                </footer>
            </section>
        </>
    );
}
