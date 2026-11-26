import { EvidenceRows, MessageEvidence, type Evidence } from '../components/ai-evidence';
import { AnswerText } from '../components/ai-answer';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowUp, Plus, RefreshCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Errors, PageTitle } from '../components/common';
import { Button } from '../components/ui/button';
import '../../css/intelligence.css';

type Message = {
    id: number;
    role: 'user' | 'assistant';
    content: string | null;
    status: 'pending' | 'completed' | 'failed';
    evidence: Evidence[] | null;
    scope?: { start: string; end: string; branch: string } | null;
};
type Thread = {
    id: number;
    title: string;
    start: string;
    end: string;
    branch_id: number | null;
};
type Insight = {
    id: number;
    branch_id: number;
    title: string;
    summary: string;
    recommendation: string;
    priority: string;
    status: string;
    start: string;
    end: string;
    evidence: Record<string, unknown>;
};
type Props = {
    canManageInsights: boolean;
    available: boolean;
    dailyLimit: number;
    usedToday: number;
    branches: { id: number; name: string }[];
    threads: Thread[];
    conversation: (Thread & { messages: Message[] }) | null;
    insights: {
        data: Insight[];
        next_page_url: string | null;
        prev_page_url: string | null;
    };
    tab: 'assistant' | 'insights';
    filters: { branch_id: number | '' };
};
export default function Intelligence({
    canManageInsights,
    available,
    dailyLimit,
    usedToday,
    branches,
    threads,
    conversation,
    insights,
    tab,
    filters,
}: Props) {
    const today = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Jakarta',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date());
    const form = useForm({
        message: '',
        thread_id: conversation?.id ?? (null as number | null),
        start: conversation?.start ?? today.slice(0, 8) + '01',
        end: conversation?.end ?? today,
        branch_id: conversation?.branch_id ?? filters.branch_id,
    });
    const [activeTab, setActiveTab] = useState(tab);
    const composer = useRef<HTMLTextAreaElement>(null);
    const pending =
        conversation?.messages.some((message) => message.status === 'pending') ?? false;
    useEffect(() => setActiveTab(tab), [tab, conversation?.id]);
    useEffect(() => {
        form.setData({
            message: '',
            thread_id: conversation?.id ?? null,
            start: conversation?.start ?? today.slice(0, 8) + '01',
            end: conversation?.end ?? today,
            branch_id: conversation?.branch_id ?? filters.branch_id,
        });
    }, [conversation?.id]);
    useEffect(() => {
        if (!pending) {
            return;
        }
        const timer = window.setInterval(
            () =>
                router.reload({
                    only: ['conversation', 'usedToday', 'threads'],
                }),
            3000,
        );
        return () => window.clearInterval(timer);
    }, [pending]);
    const exhausted = usedToday >= dailyLimit;
    return (
        <div className="intelligence">
            <Head title={canManageInsights ? 'AI & insight bisnis' : 'Asisten AI'} />
            <PageTitle
                title={canManageInsights ? 'AI & insight bisnis' : 'Asisten AI Cabang'}
                description={
                    canManageInsights
                        ? 'Tanyakan angka bisnis Anda, lalu telusuri data di balik jawabannya.'
                        : 'Tanyakan penjualan, menu, dan persediaan cabang Anda.'
                }
            />
            <div
                className="intelligence-tabs"
                role="group"
                aria-label="Bagian intelligence"
            >
                <button
                    aria-pressed={activeTab === 'assistant'}
                    onClick={() => setActiveTab('assistant')}
                >
                    Asisten bisnis
                </button>
                {canManageInsights && (
                    <button
                        aria-pressed={activeTab === 'insights'}
                        onClick={() => setActiveTab('insights')}
                    >
                        Insight otomatis
                    </button>
                )}
                <span>{available ? 'Gemini terkonfigurasi' : 'Gemini belum aktif'}</span>
            </div>
            {!canManageInsights || activeTab === 'assistant' ? (
                <div className="intelligence-workspace">
                    <aside
                        className="intelligence-history"
                        aria-label="Riwayat percakapan"
                    >
                        <Button
                            variant="outline"
                            asChild
                        >
                            <Link href="/intelligence">
                                <Plus size={16} />
                                Percakapan baru
                            </Link>
                        </Button>
                        <h2>Riwayat terbaru</h2>
                        {threads.length ? (
                            threads.map((thread) => (
                                <Link
                                    key={thread.id}
                                    href={`/intelligence?thread=${thread.id}`}
                                    aria-current={
                                        conversation?.id === thread.id
                                            ? 'page'
                                            : undefined
                                    }
                                >
                                    {thread.title}
                                </Link>
                            ))
                        ) : (
                            <p>Belum ada percakapan.</p>
                        )}
                        <p>
                            {usedToday} dari {dailyLimit} pertanyaan hari ini.
                            <br />
                            Batas diperbarui pukul 00.00 WIB.
                        </p>
                    </aside>
                    <section
                        className="intelligence-chat"
                        aria-label="Percakapan dengan asisten bisnis"
                    >
                        <header className="intelligence-scope">
                            <label>
                                Cabang
                                <select
                                    value={form.data.branch_id}
                                    disabled={
                                        !canManageInsights || pending || form.processing
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'branch_id',
                                            event.target.value
                                                ? Number(event.target.value)
                                                : '',
                                        )
                                    }
                                >
                                    {canManageInsights && (
                                        <option value="">Seluruh cabang</option>
                                    )}
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
                            <label>
                                Dari
                                <input
                                    type="date"
                                    value={form.data.start}
                                    max={form.data.end}
                                    disabled={pending || form.processing}
                                    onInput={(event) =>
                                        form.setData('start', event.currentTarget.value)
                                    }
                                />
                            </label>
                            <label>
                                Sampai
                                <input
                                    type="date"
                                    value={form.data.end}
                                    min={form.data.start}
                                    max={today}
                                    disabled={pending || form.processing}
                                    onInput={(event) =>
                                        form.setData('end', event.currentTarget.value)
                                    }
                                />
                            </label>
                        </header>
                        <p className="intelligence-scope-note">
                            {canManageInsights
                                ? 'Tanggal dan cabang berlaku untuk pertanyaan berikutnya. Jawaban lama tetap memakai cakupan asalnya.'
                                : 'Analisis hanya menggunakan data cabang Anda. Periode yang dipilih berlaku untuk pertanyaan berikutnya.'}
                        </p>
                        {!available && (
                            <p
                                className="intelligence-notice"
                                role="status"
                            >
                                {canManageInsights
                                    ? 'Chat belum aktif. Pengelola perlu mengonfigurasi koneksi Gemini. Insight otomatis dan laporan bisnis tetap tersedia.'
                                    : 'Chat belum aktif. Hubungi owner untuk mengaktifkan layanan Gemini. Laporan cabang tetap tersedia.'}
                            </p>
                        )}
                        <div
                            className="intelligence-messages"
                            aria-busy={pending}
                        >
                            {!conversation?.messages.length && (
                                <div className="intelligence-start">
                                    <h2>
                                        {canManageInsights
                                            ? 'Apa yang ingin Anda pahami dari kedai hari ini?'
                                            : 'Apa yang perlu ditindaklanjuti di cabang Anda?'}
                                    </h2>
                                    <p>
                                        {canManageInsights
                                            ? 'Pilih cabang dan periode, lalu tanyakan penjualan, menu, biaya, atau persediaan.'
                                            : 'Pilih periode, lalu telusuri penjualan, perubahan menu, atau bahan yang perlu dibeli.'}
                                    </p>
                                    <div className="intelligence-suggestions">
                                        {[
                                            'Bagaimana penjualan pada periode ini?',
                                            'Menu apa yang paling banyak terjual?',
                                            'Bahan apa yang perlu diperiksa stoknya?',
                                        ].map((question) => (
                                            <button
                                                key={question}
                                                disabled={!available}
                                                onClick={() => {
                                                    form.setData('message', question);
                                                    composer.current?.focus();
                                                }}
                                            >
                                                {question}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                            {conversation?.messages.map((message, index) => (
                                <article
                                    key={message.id}
                                    className={`intelligence-message intelligence-message-${message.role}`}
                                >
                                    <h3>
                                        {message.role === 'user'
                                            ? 'Anda'
                                            : 'Asisten VELTRIX'}
                                    </h3>
                                    {message.role === 'user' && message.scope && (
                                        <p className="intelligence-message-scope">
                                            {message.scope.branch} · {message.scope.start}{' '}
                                            — {message.scope.end}
                                        </p>
                                    )}
                                    {message.status === 'pending' ? (
                                        <p role="status">
                                            Sedang membaca data dan menyusun jawaban…
                                        </p>
                                    ) : message.role === 'assistant' &&
                                      message.status === 'completed' ? (
                                        <AnswerText content={message.content ?? ''} />
                                    ) : (
                                        <p
                                            role={
                                                message.status === 'failed'
                                                    ? 'alert'
                                                    : undefined
                                            }
                                        >
                                            {message.content}
                                        </p>
                                    )}
                                    {!!message.evidence?.length && (
                                        <MessageEvidence sources={message.evidence} />
                                    )}
                                    {message.status === 'failed' && (
                                        <button
                                            className="intelligence-text-action"
                                            onClick={() => {
                                                form.setData(
                                                    'message',
                                                    conversation.messages[index - 1]
                                                        ?.content ?? '',
                                                );
                                                composer.current?.focus();
                                            }}
                                        >
                                            Gunakan kembali pertanyaan
                                        </button>
                                    )}
                                </article>
                            ))}
                        </div>
                        <form
                            className="intelligence-composer"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.post('/intelligence/messages', {
                                    preserveScroll: true,
                                    onSuccess: () => form.reset('message'),
                                });
                            }}
                        >
                            <Errors errors={form.errors} />
                            <label htmlFor="business-question">Pertanyaan Anda</label>
                            <div>
                                <textarea
                                    id="business-question"
                                    ref={composer}
                                    value={form.data.message}
                                    maxLength={2000}
                                    rows={3}
                                    disabled={!available || pending || exhausted}
                                    placeholder="Contoh: bandingkan penjualan minggu ini dan minggu lalu"
                                    onChange={(event) =>
                                        form.setData('message', event.target.value)
                                    }
                                    required
                                />
                                <Button
                                    type="submit"
                                    disabled={
                                        !available ||
                                        pending ||
                                        exhausted ||
                                        form.processing ||
                                        !form.data.message.trim()
                                    }
                                    aria-label="Kirim pertanyaan"
                                >
                                    <ArrowUp size={18} />
                                </Button>
                            </div>
                            <p>
                                {exhausted
                                    ? 'Batas pertanyaan harian tercapai.'
                                    : 'Jawaban menggunakan data bisnis dan dapat keliru. Periksa data pendukung sebelum mengambil keputusan.'}
                            </p>
                        </form>
                    </section>
                </div>
            ) : (
                <section
                    className="intelligence-insights"
                    aria-label="Insight bisnis otomatis"
                >
                    <header className="intelligence-insights-toolbar">
                        <div>
                            <h2>Temuan dari data operasional</h2>
                            <p>
                                Analisis aturan untuk tujuh hari lengkap terakhir,
                                dibandingkan tujuh hari sebelumnya. Tidak menggunakan
                                kuota Gemini.
                            </p>
                        </div>
                        <div>
                            <select
                                aria-label="Filter cabang insight"
                                value={filters.branch_id}
                                onChange={(event) =>
                                    router.get(
                                        '/intelligence',
                                        {
                                            tab: 'insights',
                                            branch_id: event.target.value,
                                        },
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                <option value="">Seluruh cabang</option>
                                {branches.map((branch) => (
                                    <option
                                        value={branch.id}
                                        key={branch.id}
                                    >
                                        {branch.name}
                                    </option>
                                ))}
                            </select>
                            <Button
                                variant="outline"
                                onClick={() => router.reload({ only: ['insights'] })}
                                aria-label="Muat ulang insight"
                            >
                                <RefreshCw size={16} />
                            </Button>
                            <Button
                                onClick={() =>
                                    router.post(
                                        '/intelligence/generate',
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Jalankan analisis
                            </Button>
                        </div>
                    </header>
                    {!insights.data.length && (
                        <div className="intelligence-insights-empty">
                            <h3>Belum ada temuan untuk ditampilkan</h3>
                            <p>
                                Jalankan analisis untuk memeriksa data. Tren penjualan
                                memerlukan setidaknya 10 transaksi pada masing-masing
                                periode pembanding; stok diperiksa berdasarkan kondisi
                                saat ini.
                            </p>
                        </div>
                    )}
                    {insights.data.map((insight) => (
                        <article
                            key={insight.id}
                            className="intelligence-insight"
                        >
                            <div>
                                <span className="intelligence-insight-priority">
                                    {insight.priority === 'high'
                                        ? 'Perlu perhatian'
                                        : 'Untuk ditinjau'}
                                </span>
                                <h3>{insight.title}</h3>
                                <p>
                                    {branches.find(
                                        (branch) => branch.id === insight.branch_id,
                                    )?.name ?? 'Seluruh cabang'}{' '}
                                    · {insight.start} — {insight.end}
                                </p>
                            </div>
                            <p>{insight.summary}</p>
                            <p>
                                <strong>Langkah berikutnya:</strong>{' '}
                                {insight.recommendation}
                            </p>
                            <details className="intelligence-evidence">
                                <summary>Data pendukung</summary>
                                <EvidenceRows data={insight.evidence} />
                                {Array.isArray(insight.evidence.branches) &&
                                    insight.evidence.branches.map((branch, index) => (
                                        <EvidenceRows
                                            key={index}
                                            data={branch as Record<string, unknown>}
                                        />
                                    ))}
                                {['current', 'previous'].map((key) =>
                                    insight.evidence[key] &&
                                    typeof insight.evidence[key] === 'object' ? (
                                        <section key={key}>
                                            <h4>
                                                {key === 'current'
                                                    ? 'Periode analisis'
                                                    : 'Periode pembanding'}
                                            </h4>
                                            <EvidenceRows
                                                data={
                                                    insight.evidence[key] as Record<
                                                        string,
                                                        unknown
                                                    >
                                                }
                                            />
                                        </section>
                                    ) : null,
                                )}
                                <p>
                                    Sumber: analisis aturan VELTRIX, bukan kesimpulan
                                    Gemini.
                                </p>
                            </details>
                            <footer>
                                <Link
                                    href={`/reports?start=${insight.start}&end=${insight.end}&branch_id=${insight.branch_id}`}
                                >
                                    Buka laporan terkait
                                </Link>
                                <Button
                                    variant="outline"
                                    disabled={!available || exhausted}
                                    onClick={() =>
                                        router.post(
                                            `/intelligence/insights/${insight.id}/explain`,
                                        )
                                    }
                                >
                                    Jelaskan dengan AI
                                </Button>
                                <label>
                                    Status
                                    <select
                                        aria-label={`Status ${insight.title}`}
                                        value={insight.status}
                                        onChange={(event) =>
                                            router.patch(
                                                `/intelligence/insights/${insight.id}`,
                                                { status: event.target.value },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <option value="new">Baru</option>
                                        <option value="read">Sudah ditinjau</option>
                                        <option value="dismissed">Diabaikan</option>
                                    </select>
                                </label>
                            </footer>
                        </article>
                    ))}
                    <nav
                        className="intelligence-pagination"
                        aria-label="Halaman insight"
                    >
                        {insights.prev_page_url && (
                            <Link href={insights.prev_page_url}>Sebelumnya</Link>
                        )}
                        {insights.next_page_url && (
                            <Link href={insights.next_page_url}>Berikutnya</Link>
                        )}
                    </nav>
                </section>
            )}
        </div>
    );
}
