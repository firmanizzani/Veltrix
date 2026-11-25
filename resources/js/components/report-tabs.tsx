import { Link } from '@inertiajs/react';
import '../../css/product-sales-report.css';

export function ReportTabs({
    active,
    filters,
}: {
    active: 'summary' | 'products';
    filters: { start: string; end: string; branch_id: string | number };
}) {
    const query = new URLSearchParams({
        start: filters.start,
        end: filters.end,
        branch_id: String(filters.branch_id),
    }).toString();
    return (
        <nav
            className="report-tabs"
            aria-label="Jenis laporan"
        >
            <Link
                href={`/reports?${query}`}
                aria-current={active === 'summary' ? 'page' : undefined}
            >
                Ringkasan
            </Link>
            <Link
                href={`/reports/products?${query}`}
                aria-current={active === 'products' ? 'page' : undefined}
            >
                Penjualan per menu
            </Link>
        </nav>
    );
}
