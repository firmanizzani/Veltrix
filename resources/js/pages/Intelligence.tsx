import { Head, Link } from '@inertiajs/react';
import { Sparkles, ShieldCheck, ChartNoAxesCombined, Clock3 } from 'lucide-react';
import { PageTitle } from '../components/common';
import { Button } from '../components/ui/button';
export default function Intelligence() {
    return (
        <>
            <Head title="AI & insight bisnis" />
            <PageTitle
                title="AI & insight bisnis"
                description="Asisten bisnis dan insight otomatis akan menggunakan hasil analisis data operasional."
            />
            <div className="integration-state">
                <Sparkles
                    size={36}
                    strokeWidth={1.2}
                />
                <h2>Gemini belum diaktifkan</h2>
                <p>
                    Integrasi AI ditunda sesuai konfigurasi proyek. Belum ada chat atau
                    rekomendasi yang dihasilkan AI.
                </p>
                <Button
                    variant="outline"
                    asChild
                >
                    <Link href="/reports">
                        <ChartNoAxesCombined size={16} />
                        Buka laporan bisnis
                    </Link>
                </Button>
            </div>
            <div className="integration-details">
                <div>
                    <ShieldCheck size={20} />
                    <h3>Data tetap terkontrol</h3>
                    <p>
                        Integrasi berikutnya memakai analytics tools dengan izin akses
                        owner. API key tidak dikirim ke browser.
                    </p>
                </div>
                <div>
                    <Clock3 size={20} />
                    <h3>Operasional tetap berjalan</h3>
                    <p>
                        POS, inventori, dan laporan berfungsi tanpa koneksi ke provider
                        AI.
                    </p>
                </div>
            </div>
        </>
    );
}
