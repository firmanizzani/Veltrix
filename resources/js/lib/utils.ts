import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}
export const money = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
export const number = (value: number | string | null | undefined) =>
    new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(
        Number(value ?? 0),
    );
export const date = (value: string | undefined) =>
    value
        ? new Intl.DateTimeFormat('id-ID', {
              dateStyle: 'medium',
              timeStyle: value.length > 10 ? 'short' : undefined,
              timeZone: 'Asia/Jakarta',
          }).format(new Date(value.length === 10 ? value + 'T12:00:00+07:00' : value))
        : '—';
export const labels: Record<string, string> = {
    owner: 'Owner',
    manager: 'Branch Manager',
    cashier: 'Kasir',
    cash: 'Tunai',
    qris: 'QRIS',
    card: 'Kartu',
    ewallet: 'E-Wallet',
    open: 'Terbuka',
    closed: 'Ditutup',
    completed: 'Selesai',
    refunded: 'Dikembalikan',
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    draft: 'Draf',
    paid: 'Dibayar',
    ordered: 'Dipesan',
    partial: 'Diterima sebagian',
    received: 'Diterima',
    shipped: 'Dikirim',
    cancelled: 'Dibatalkan',
    sales: 'Penjualan',
    adjustment: 'Penyesuaian',
    stocktake: 'Stok opname',
    receiving: 'Penerimaan',
    transfer_in: 'Transfer masuk',
    transfer_out: 'Transfer keluar',
};
export type Row = Record<string, any>;
export type User = {
    id: number;
    name: string;
    email: string;
    role: 'owner' | 'manager' | 'cashier';
    branch_id: number | null;
    branch?: Row;
};
export type Shared = {
    business: Record<string, string>;
    auth: { user: User };
    flash: { success?: string; status?: string };
    [key: string]: unknown;
};
