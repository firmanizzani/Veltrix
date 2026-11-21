import { Head, useForm } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { Button } from '../components/ui/button';
import { Errors, PageTitle } from '../components/common';
import type { Row } from '../lib/utils';

export default function Settings({ settings }: { settings: Row }) {
    const form = useForm({
        business_name: settings.business_name,
        contact_email: settings.contact_email,
        receipt_footer: settings.receipt_footer,
    });
    return (
        <>
            <Head title="Pengaturan bisnis" />
            <PageTitle
                title="Pengaturan bisnis"
                description="Identitas bisnis dan informasi pada struk kedai."
            />
            <div className="settings-section">
                <div>
                    <Building2 size={22} />
                    <h2>Identitas & struk</h2>
                    <p>
                        Pengaturan berlaku di semua cabang. Mata uang menggunakan Rupiah
                        dan periode bisnis mengikuti WIB.
                    </p>
                    <h3>Pratinjau footer struk</h3>
                    <p className="receipt-preview">{form.data.receipt_footer}</p>
                </div>
                <form
                    className="settings-form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put('/settings', { preserveScroll: true });
                    }}
                >
                    <Errors errors={form.errors} />
                    <label className="field">
                        Nama bisnis
                        <input
                            required
                            maxLength={100}
                            value={form.data.business_name}
                            onChange={(e) =>
                                form.setData('business_name', e.target.value)
                            }
                        />
                    </label>
                    <label className="field">
                        Email kontak
                        <input
                            type="email"
                            value={form.data.contact_email}
                            onChange={(e) =>
                                form.setData('contact_email', e.target.value)
                            }
                        />
                    </label>
                    <label className="field">
                        Pesan pada struk
                        <textarea
                            rows={4}
                            required
                            maxLength={300}
                            value={form.data.receipt_footer}
                            onChange={(e) =>
                                form.setData('receipt_footer', e.target.value)
                            }
                        />
                    </label>
                    <Button disabled={form.processing}>
                        {form.processing ? 'Menyimpan…' : 'Simpan pengaturan'}
                    </Button>
                </form>
            </div>
        </>
    );
}
