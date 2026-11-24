import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Coffee, Eye, EyeOff, LockKeyhole, ShieldCheck } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Button } from '../components/ui/button';
import { ErrorText, Errors } from '../components/common';
import type { Shared } from '../lib/utils';

export default function Auth({
    mode,
    email = '',
    token = '',
}: {
    mode: string;
    email?: string;
    token?: string;
}) {
    const form = useForm({
        email,
        password: '',
        password_confirmation: '',
        remember: false,
        token,
    });
    const [visible, setVisible] = useState(false);
    const { flash } = usePage<Shared>().props;
    const title =
        mode === 'login'
            ? 'Masuk ke ruang kerja'
            : mode === 'forgot'
              ? 'Lupa password?'
              : 'Buat password baru';
    function submit(e: FormEvent) {
        e.preventDefault();
        form.post(
            mode === 'login'
                ? '/login'
                : mode === 'forgot'
                  ? '/forgot-password'
                  : '/reset-password',
            { onFinish: () => form.reset('password', 'password_confirmation') },
        );
    }
    return (
        <div className="auth-page">
            <Head title={title} />
            <section className="auth-story">
                <Link
                    href="/"
                    className="brand"
                    aria-label="VELTRIX, kembali ke beranda"
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
                <div className="auth-story-main">
                    <Coffee
                        size={44}
                        strokeWidth={1.2}
                    />
                    <h2>
                        Setiap kedai.
                        <br />
                        Satu kendali.
                    </h2>
                    <p>
                        Dari pesanan pertama sampai laporan penutup hari, kelola
                        operasional Veltrix Coffee dalam satu ruang kerja.
                    </p>
                    <div className="branch-cities">
                        <span>Depok</span>
                        <span>Jakarta</span>
                        <span>Bogor</span>
                    </div>
                </div>
                <p className="auth-footnote">Operasional terhubung, keputusan terarah.</p>
            </section>
            <section className="auth-form-area">
                <div className="auth-form">
                    <Link
                        href="/"
                        className="text-link auth-home-link"
                    >
                        Kembali ke beranda
                    </Link>
                    <span className="auth-lock">
                        <LockKeyhole size={24} />
                    </span>
                    <h1>{title}</h1>
                    <p className="muted">
                        {mode === 'login'
                            ? 'Gunakan akun yang ditetapkan untuk peran dan cabang Anda.'
                            : mode === 'forgot'
                              ? 'Masukkan email akun Anda untuk meminta tautan pemulihan.'
                              : 'Gunakan minimal 12 karakter dengan huruf dan angka.'}
                    </p>
                    {flash.status && (
                        <p
                            className="notice"
                            role="status"
                        >
                            Permintaan diproses. Periksa email Anda untuk petunjuk
                            berikutnya.
                        </p>
                    )}
                    <form onSubmit={submit}>
                        <label className="field">
                            Email
                            <input
                                type="email"
                                name="email"
                                autoComplete="username"
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                required
                                autoFocus
                            />
                            <ErrorText message={form.errors.email} />
                        </label>
                        {mode !== 'forgot' && (
                            <label className="field">
                                Password
                                <div className="password-input">
                                    <input
                                        name="password"
                                        type={visible ? 'text' : 'password'}
                                        autoComplete={
                                            mode === 'login'
                                                ? 'current-password'
                                                : 'new-password'
                                        }
                                        value={form.data.password}
                                        onChange={(e) =>
                                            form.setData('password', e.target.value)
                                        }
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setVisible(!visible)}
                                        aria-label={
                                            visible
                                                ? 'Sembunyikan password'
                                                : 'Tampilkan password'
                                        }
                                    >
                                        {visible ? (
                                            <EyeOff size={18} />
                                        ) : (
                                            <Eye size={18} />
                                        )}
                                    </button>
                                </div>
                                <ErrorText message={form.errors.password} />
                            </label>
                        )}
                        {mode === 'reset' && (
                            <label className="field">
                                Konfirmasi password
                                <input
                                    type="password"
                                    autoComplete="new-password"
                                    value={form.data.password_confirmation}
                                    onChange={(e) =>
                                        form.setData(
                                            'password_confirmation',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </label>
                        )}
                        {mode === 'login' && (
                            <div className="auth-options">
                                <label className="checkbox-label">
                                    <input
                                        type="checkbox"
                                        checked={form.data.remember}
                                        onChange={(e) =>
                                            form.setData('remember', e.target.checked)
                                        }
                                    />
                                    Ingat saya
                                </label>
                                <Link href="/forgot-password">Lupa password?</Link>
                            </div>
                        )}
                        <Button
                            className="full-width"
                            disabled={form.processing}
                        >
                            {form.processing
                                ? 'Memproses…'
                                : mode === 'login'
                                  ? 'Masuk'
                                  : mode === 'forgot'
                                    ? 'Kirim tautan pemulihan'
                                    : 'Simpan password'}
                        </Button>
                        {mode !== 'login' && (
                            <Link
                                className="back-login"
                                href="/login"
                            >
                                Kembali ke halaman masuk
                            </Link>
                        )}
                    </form>
                    <div className="secure-note">
                        <ShieldCheck size={16} />
                        <span>Akses aman sesuai peran dan cabang.</span>
                    </div>
                </div>
            </section>
        </div>
    );
}
