import { Link, usePage } from '@inertiajs/react';
import * as DialogPrimitive from '@radix-ui/react-dialog';
import { Search, Coffee, ChevronLeft, ChevronRight, Menu, Bell } from 'lucide-react';
import { date, labels, money, number, type Row, type Shared } from '../lib/utils';
import { Button } from './ui/button';
import { useState, useEffect, useRef } from 'react';

export function Status({ value }: { value: unknown }) {
    const text =
        typeof value === 'boolean'
            ? value
                ? 'Aktif'
                : 'Nonaktif'
            : (labels[String(value)] ?? String(value ?? '—'));
    const state =
        typeof value === 'boolean' ? (value ? 'approved' : 'closed') : String(value);
    return <span className={`status status-${state}`}>{text}</span>;
}
export function ErrorText({ message }: { message?: string }) {
    return message ? (
        <p
            className="field-error"
            role="alert"
        >
            {message}
        </p>
    ) : null;
}
export function Errors({ errors }: { errors: Record<string, string> }) {
    const summary = useRef<HTMLDivElement>(null);
    useEffect(() => {
        if (Object.keys(errors).length) {
            summary.current?.focus();
        }
    }, [errors]);
    return Object.keys(errors).length ? (
        <div
            className="error-summary"
            ref={summary}
            tabIndex={-1}
            role="alert"
        >
            {Object.entries(errors).map(([key, value]) => (
                <p key={key}>{value}</p>
            ))}
        </div>
    ) : null;
}
export function Empty({ children }: { children: React.ReactNode }) {
    return <div className="empty-state">{children}</div>;
}
export function PageTitle({
    title,
    description,
    action,
    className = '',
}: {
    title: string;
    description: string;
    action?: React.ReactNode;
    className?: string;
}) {
    const { auth } = usePage<Shared>().props;
    return (
        <header className={`page-title ${className}`}>
            <div className="page-heading">
                <DialogPrimitive.Trigger asChild>
                    <button
                        className="mobile-menu"
                        aria-label="Buka navigasi"
                    >
                        <Menu size={20} />
                    </button>
                </DialogPrimitive.Trigger>
                <div>
                    <h1>{title}</h1>
                    <p>{description}</p>
                </div>
            </div>
            <div className="page-header-actions">
                {action}
                {auth.user.role !== 'cashier' && (
                    <Link
                        href="/alerts"
                        aria-label="Buka notifikasi"
                        className="button button-ghost button-icon header-notifications"
                    >
                        <Bell size={18} />
                    </Link>
                )}
            </div>
        </header>
    );
}
export function SearchBox({
    value,
    onChange,
    placeholder = 'Cari…',
}: {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
}) {
    return (
        <div className="search-box">
            <Search size={17} />
            <input
                type="search"
                aria-label={placeholder}
                placeholder={placeholder}
                value={value}
                onChange={(e) => onChange(e.target.value)}
            />
        </div>
    );
}
export function ProductImage({
    product,
    large = false,
}: {
    product: Row;
    large?: boolean;
}) {
    const [failed, setFailed] = useState(false);
    return (
        <div className={`product-image ${large ? 'product-image-large' : ''}`}>
            {product.image_url && !failed ? (
                <img
                    src={product.image_url}
                    alt={product.name}
                    loading="lazy"
                    width={large ? 500 : 42}
                    height={large ? 300 : 42}
                    referrerPolicy="no-referrer"
                    onError={() => setFailed(true)}
                />
            ) : (
                <Coffee
                    strokeWidth={1.25}
                    size={large ? 36 : 20}
                />
            )}
        </div>
    );
}
export function getValue(row: Row, path: string): any {
    return path.split('.').reduce((value, key) => value?.[key], row);
}
const moneyFields = [
    'price',
    'base_price',
    'stock_value',
    'estimated_cost',
    'amount',
    'total_amount',
    'net_pay',
    'opening_cash',
    'expected_cash',
    'actual_cash',
    'unit_cost',
];
export function Cell({ row, path }: { row: Row; path: string }) {
    const value = getValue(row, path);
    if (value === null || value === undefined) {
        return <span className="muted">—</span>;
    }
    if (['is_active', 'is_available', 'status', 'approval_status'].includes(path)) {
        return <Status value={value} />;
    }
    if (moneyFields.includes(path)) {
        return <span className="num">{money(value)}</span>;
    }
    if (path.endsWith('_at') || ['period', 'work_date', 'expense_date'].includes(path)) {
        return date(value);
    }
    if (typeof value === 'number' || ['quantity', 'minimum', 'balance'].includes(path)) {
        return <span className="num">{number(value)}</span>;
    }
    return labels[value] ?? String(value);
}
export function Pagination({ records }: { records: Row }) {
    return (
        <div className="pagination">
            <span>
                {records.from ?? 0}–{records.to ?? 0} dari {number(records.total)}
            </span>
            <div>
                <Button
                    asChild
                    variant="outline"
                    size="sm"
                    disabled={!records.prev_page_url}
                >
                    {records.prev_page_url ? (
                        <Link
                            href={records.prev_page_url}
                            preserveScroll
                        >
                            <ChevronLeft size={16} />
                            Sebelumnya
                        </Link>
                    ) : (
                        <span>
                            <ChevronLeft size={16} />
                            Sebelumnya
                        </span>
                    )}
                </Button>
                <Button
                    asChild
                    variant="outline"
                    size="sm"
                    disabled={!records.next_page_url}
                >
                    {records.next_page_url ? (
                        <Link
                            href={records.next_page_url}
                            preserveScroll
                        >
                            Berikutnya
                            <ChevronRight size={16} />
                        </Link>
                    ) : (
                        <span>
                            Berikutnya
                            <ChevronRight size={16} />
                        </span>
                    )}
                </Button>
            </div>
        </div>
    );
}
