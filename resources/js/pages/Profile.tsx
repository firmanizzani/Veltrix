import { Head, useForm, usePage } from '@inertiajs/react';
import { UserRound, LockKeyhole } from 'lucide-react';
import { Button } from '../components/ui/button';
import { Errors, PageTitle } from '../components/common';
import { labels, type Shared } from '../lib/utils';
export default function Profile() {
    const {
        auth: { user },
    } = usePage<Shared>().props;
    const profile = useForm({ name: user.name, email: user.email, current_password: '' });
    const password = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    return (
        <>
            <Head title="Profil saya" />
            <PageTitle
                title="Profil saya"
                description="Kelola identitas akun dan keamanan akses Anda."
            />
            <div className="settings-section">
                <div>
                    <UserRound size={22} />
                    <h2>Informasi akun</h2>
                    <p>
                        {labels[user.role]}
                        <br />
                        {user.branch?.name ?? 'Seluruh cabang'}
                    </p>
                    <p className="muted">
                        Peran dan penugasan cabang dikelola oleh owner.
                    </p>
                </div>
                <form
                    className="settings-form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        profile.put('/profile', {
                            preserveScroll: true,
                            onSuccess: () => profile.reset('current_password'),
                        });
                    }}
                >
                    <Errors errors={profile.errors} />
                    <label className="field">
                        Nama lengkap
                        <input
                            required
                            value={profile.data.name}
                            onChange={(e) => profile.setData('name', e.target.value)}
                            autoComplete="name"
                        />
                    </label>
                    <label className="field">
                        Email
                        <input
                            type="email"
                            required
                            value={profile.data.email}
                            onChange={(e) => profile.setData('email', e.target.value)}
                            autoComplete="email"
                        />
                    </label>
                    <label className="field">
                        Password saat ini
                        <input
                            type="password"
                            required
                            autoComplete="current-password"
                            value={profile.data.current_password}
                            onChange={(e) =>
                                profile.setData('current_password', e.target.value)
                            }
                        />
                    </label>
                    <Button disabled={profile.processing}>
                        {profile.processing ? 'Menyimpan…' : 'Simpan profil'}
                    </Button>
                </form>
            </div>
            <div className="settings-section">
                <div>
                    <LockKeyhole size={22} />
                    <h2>Ubah password</h2>
                    <p>Gunakan minimal 12 karakter dengan kombinasi huruf dan angka.</p>
                    <p className="muted">
                        Perubahan password akan mencabut sesi di perangkat lain.
                    </p>
                </div>
                <form
                    className="settings-form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        password.put('/profile/password', {
                            preserveScroll: true,
                            onSuccess: () => password.reset(),
                        });
                    }}
                >
                    <Errors errors={password.errors} />
                    {[
                        ['current_password', 'Password saat ini'],
                        ['password', 'Password baru'],
                        ['password_confirmation', 'Konfirmasi password baru'],
                    ].map(([key, label]) => (
                        <label
                            className="field"
                            key={key}
                        >
                            {label}
                            <input
                                type="password"
                                required
                                autoComplete={
                                    key === 'current_password'
                                        ? 'current-password'
                                        : 'new-password'
                                }
                                value={password.data[key as keyof typeof password.data]}
                                onChange={(e) =>
                                    password.setData(
                                        key as keyof typeof password.data,
                                        e.target.value,
                                    )
                                }
                            />
                        </label>
                    ))}
                    <Button disabled={password.processing}>
                        {password.processing ? 'Menyimpan…' : 'Perbarui password'}
                    </Button>
                </form>
            </div>
        </>
    );
}
