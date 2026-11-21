import { Head, Link } from '@inertiajs/react';
import { Button } from '../components/ui/button';

export default function Error({ status }: { status: number }) {
    const messages: Record<number, [string, string]> = {
        403: [
            'Akses tidak diizinkan',
            'Halaman atau tindakan ini berada di luar akses akun Anda.',
        ],
        404: [
            'Halaman tidak ditemukan',
            'Catatan mungkin tidak tersedia atau berada di cabang lain.',
        ],
        419: ['Sesi telah berakhir', 'Masuk kembali untuk melanjutkan pekerjaan.'],
        422: [
            'Tindakan tidak dapat diproses',
            'Status catatan sudah berubah. Muat ulang halaman dan periksa data terbaru.',
        ],
        500: [
            'Terjadi gangguan',
            'Permintaan belum dapat diselesaikan. Coba kembali beberapa saat lagi.',
        ],
        503: [
            'Layanan sedang disiapkan',
            'Silakan kembali setelah pemeliharaan selesai.',
        ],
    };
    const [title, description] = messages[status] ?? messages[500];
    return (
        <div className="error-page">
            <Head title={title} />
            <span>VELTRIX · {status}</span>
            <h1>{title}</h1>
            <p>{description}</p>
            <Button
                asChild
                variant="outline"
            >
                <Link href={status === 419 ? '/login' : '/dashboard'}>
                    Kembali ke ruang kerja
                </Link>
            </Button>
        </div>
    );
}
