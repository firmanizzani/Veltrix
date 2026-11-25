import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowUp, Plus, RefreshCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Errors, PageTitle } from '../components/common';
import { Button } from '../components/ui/button';
import { money } from '../lib/utils';
import '../../css/intelligence.css';

type Evidence = {
    metric: string;
    period: { start: string; end: string };
    branch: string;
    data: Record<string, unknown> | Record<string, unknown>[];
    notes: string;
};
type Message = {
    id: number;
    role: 'user' | 'assistant';
    content: string | null;
    status: 'pending' | 'completed' | 'failed';
    evidence: Evidence[] | null;
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
const evidenceLabels: Record<string, string> = {
    gross: 'Penjualan kotor',
    net: 'Penjualan bersih',
    refunds: 'Refund',
    transactions: 'Transaksi',
    average: 'Rata-rata transaksi',
    units: 'Produk terjual',
    cogs: 'Biaya bahan',
    gross_profit: 'Estimasi laba kotor',
    expenses: 'Pengeluaran disetujui',
    payroll: 'Payroll',
    profit: 'Estimasi laba operasional',
    active_branches: 'Cabang aktif',
    title: 'Temuan',
    summary: 'Ringkasan',
    recommendation: 'Saran pemeriksaan',
    name: 'Nama',
    quantity: 'Jumlah',
    amount: 'Nilai',
    branch: 'Cabang',
    ingredient: 'Bahan',
    unit: 'Satuan',
    value: 'Nilai stok',
    date: 'Tanggal',
    current_units: 'Jumlah periode ini',
    previous_units: 'Jumlah pembanding',
    minimum: 'Stok minimum',
    daily_usage: 'Pemakaian rata-rata per hari',
    estimated_days: 'Estimasi cakupan (hari)',
    observed_at: 'Waktu pemeriksaan',
    assumption: 'Asumsi',
    change_percent: 'Perubahan (%)',
};
const currencyFields = new Set([
    'gross',
    'net',
    'refunds',
    'average',
    'cogs',
    'gross_profit',
    'expenses',
    'payroll',
    'profit',
    'amount',
    'value',
]);
function EvidenceRows({ data }: { data: Record<string, unknown> }) {
    return (
        <dl className="intelligence-evidence-rows">
            {Object.entries(data)
                .filter(
                    ([key, value]) =>
                        key in evidenceLabels &&
                        (typeof value === 'number' || typeof value === 'string'),
                )
                .map(([key, value]) => (
                    <div key={key}>
                        <dt>{evidenceLabels[key]}</dt>
                        <dd>
                            {currencyFields.has(key)
                                ? money(Number(value))
                                : String(value)}
                        </dd>
                    </div>
                ))}
        </dl>
    );
}

function AnswerText({ content }: { content: string }) {
    function inline(text: string) {
        return text.split(/(\*\*[^*]+\*\*|`[^`]+`|\*[^*]+\*)/g).map((part, index) => {
            if (part.startsWith('**') && part.endsWith('**')) {
                return <strong key={index}>{part.slice(2, -2)}</strong>;
            }
            if (part.startsWith('`') && part.endsWith('`')) {
                return <code key={index}>{part.slice(1, -1)}</code>;
            }
            if (part.startsWith('*') && part.endsWith('*')) {
                return <em key={index}>{part.slice(1, -1)}</em>;
            }
            return part;
        });
    }
    return (
        <div className="intelligence-answer">
            {content.split(/\n\s*\n/).map((paragraph, index) => {
                const lines = paragraph.split('\n');
                if (lines.every((line) => /^\s*[-*]\s/.test(line))) {
                    return (
                        <ul key={index}>
                            {lines.map((line, row) => (
                                <li key={row}>
                                    {inline(line.replace(/^\s*[-*]\s/, ''))}
                                </li>
                            ))}
                        </ul>
                    );
                }
                return <p key={index}>{inline(paragraph)}</p>;
            })}
        </div>
    );
}
function MessageEvidence({ sources }: { sources: Evidence[] }) {
    return (
        <details className="intelligence-evidence">
            <summary>Lihat data pendukung ({sources.length})</summary>
            {sources.map((source, index) => (
                <section key={index}>
                    <h3>{source.branch}</h3>
                    <p>
                        {source.period.start} — {source.period.end} · IDR
                    </p>
                    {Array.isArray(source.data) ? (
                        source.data.slice(0, 8).map((row, rowIndex) => (
                            <EvidenceRows
                                key={rowIndex}
                                data={row}
                            />
                        ))
                    ) : (
                        <EvidenceRows data={source.data} />
                    )}
                    <p>{source.notes}</p>
                </section>
            ))}
        </details>
    );
}

export default function Intelligence({
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
        branch_id: conversation?.branch_id ?? ('' as number | ''),
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
            branch_id: conversation?.branch_id ?? '',
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
            <Head title="AI & insight bisnis" />
            <PageTitle
                title="AI & insight bisnis"
                description="Tanyakan angka bisnis Anda, lalu telusuri data di balik jawabannya."
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
                <button
                    aria-pressed={activeTab === 'insights'}
                    onClick={() => setActiveTab('insights')}
                >
                    Insight otomatis
                </button>
                <span>{available ? 'Gemini terkonfigurasi' : 'Gemini belum aktif'}</span>
            </div>
            {activeTab === 'assistant' ? (
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
                                    disabled={!!conversation}
                                    onChange={(event) =>
                                        form.setData(
                                            'branch_id',
                                            event.target.value
                                                ? Number(event.target.value)
                                                : '',
                                        )
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
                            <label>
                                Dari
                                <input
                                    type="date"
                                    value={form.data.start}
                                    max={form.data.end}
                                    disabled={!!conversation}
                                    onChange={(event) =>
                                        form.setData('start', event.target.value)
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
                                    disabled={!!conversation}
                                    onChange={(event) =>
                                        form.setData('end', event.target.value)
                                    }
                                />
                            </label>
                        </header>
                        {!available && (
                            <p
                                className="intelligence-notice"
                                role="status"
                            >
                                Chat belum aktif. Pengelola perlu mengonfigurasi koneksi
                                Gemini. Insight otomatis dan laporan bisnis tetap
                                tersedia.
                            </p>
                        )}
                        <div
                            className="intelligence-messages"
                            aria-busy={pending}
                        >
                            {!conversation?.messages.length && (
                                <div className="intelligence-start">
                                    <h2>
                                        Apa yang ingin Anda pahami dari kedai hari ini?
                                    </h2>
                                    <p>
                                        Pilih cabang dan periode, lalu tanyakan penjualan,
                                        menu, biaya, atau persediaan.
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
