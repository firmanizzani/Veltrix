import { Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { useState } from 'react';
import { AnswerText } from './ai-answer';
import { MessageEvidence } from './ai-evidence';
import { landingAiExamples } from '../lib/landing-ai-examples';
import '../../css/intelligence.css';
import '../../css/landing-ai-preview.css';

export function LandingAssistantSection({
    destination,
    action,
}: {
    destination: string;
    action: string;
}) {
    const [topic, setTopic] = useState('sales');
    const example =
        landingAiExamples.find((entry) => entry.id === topic) ?? landingAiExamples[0];

    return (
        <section
            id="asisten-ai"
            className="landing-ai-section landing-section"
            aria-labelledby="landing-ai-title"
        >
            <div className="landing-ai-section-copy">
                <h2
                    id="landing-ai-title"
                    data-reveal
                >
                    Pahami kedai lewat AI.
                </h2>
                <p data-reveal>
                    Mulai dari pertanyaan sehari-hari. Asisten AI membantu Anda membaca
                    penjualan, menu, dan persediaan dari catatan yang sama.
                </p>
                <div
                    className="landing-ai-questions"
                    data-ui
                    role="group"
                    aria-label="Pilih contoh pertanyaan AI"
                >
                    {landingAiExamples.map((entry) => (
                        <button
                            key={entry.id}
                            type="button"
                            aria-pressed={topic === entry.id}
                            aria-controls="landing-ai-answer"
                            onClick={() => setTopic(entry.id)}
                        >
                            {entry.question}
                            <ArrowUpRight
                                size={18}
                                aria-hidden="true"
                            />
                        </button>
                    ))}
                </div>
                <p className="landing-ai-access">
                    Owner dapat meninjau seluruh cabang. Manager hanya melihat data
                    cabangnya.
                </p>
                <Link
                    className="landing-ai-link"
                    href={destination}
                >
                    {action}
                    <ArrowUpRight size={18} />
                </Link>
            </div>
            <figure
                className="landing-ai-example"
                data-ui
            >
                <div
                    className="landing-ai-preview"
                    id="landing-ai-answer"
                >
                    <header className="landing-ai-preview-header">
                        <span>Contoh percakapan</span>
                        <span>Data demo</span>
                    </header>
                    <div
                        className="intelligence-messages"
                        key={example.id}
                        aria-live="polite"
                        aria-atomic="true"
                    >
                        <article className="intelligence-message intelligence-message-user">
                            <h3>Anda</h3>
                            <p className="intelligence-message-scope">
                                {example.scope.branch} · {example.scope.start} —{' '}
                                {example.scope.end}
                            </p>
                            <p>{example.question}</p>
                        </article>
                        <article className="intelligence-message intelligence-message-assistant">
                            <h3>Asisten VELTRIX</h3>
                            <AnswerText content={example.content} />
                            <MessageEvidence sources={example.evidence} />
                        </article>
                    </div>
                </div>
                <figcaption>
                    Contoh jawaban AI sungguhan dari data demo. Jawaban di aplikasi
                    mengikuti data dan periode yang Anda pilih.
                </figcaption>
            </figure>
        </section>
    );
}

export function LandingProductPreview() {
    return (
        <figure className="landing-product-preview">
            <img
                src="/images/landing/workspace-ai-real-preview.jpg"
                alt="Ilustrasi VELTRIX: kasir dan percakapan Asisten AI berdampingan, dengan jawaban serta data pendukung dari data demo."
                width={1980}
                height={1263}
                fetchPriority="high"
                decoding="async"
            />
        </figure>
    );
}
