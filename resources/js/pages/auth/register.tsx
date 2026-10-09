import { Errors, Layout } from '@/components/gomove/ui';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
export default function Register() {
    const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
    return (
        <Layout>
            <Head title="Vytvořit účet" />
            <div className="gm-auth">
                <div className="gm-auth-intro">
                    <p className="gm-eyebrow">PROSTOR PRO VÁŠ POHYB</p>
                    <h1>
                        Jeden účet.
                        <br />
                        Spousta možností.
                    </h1>
                    <p>Rezervujte si sportoviště a sledujte své žádosti na jednom místě.</p>
                </div>
                <form
                    className="gm-auth-form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
                    }}
                >
                    <h2>Vytvořit účet</h2>
                    <Errors errors={form.errors} />
                    <label className="gm-label">
                        Jméno
                        <input autoComplete="name" required value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                    </label>
                    <label className="gm-label">
                        E-mail
                        <input
                            type="email"
                            autoComplete="email"
                            required
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                        />
                    </label>
                    <label className="gm-label">
                        Heslo (alespoň 8 znaků)
                        <input
                            type="password"
                            minLength={8}
                            autoComplete="new-password"
                            required
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                    </label>
                    <label className="gm-label">
                        Heslo znovu
                        <input
                            type="password"
                            autoComplete="new-password"
                            required
                            value={form.data.password_confirmation}
                            onChange={(e) => form.setData('password_confirmation', e.target.value)}
                        />
                    </label>
                    <button className="gm-btn gm-btn-dark gm-full" disabled={form.processing}>
                        Vytvořit účet <ArrowUpRight size={18} />
                    </button>
                    <p>
                        Už máte účet? <Link href="/login">Přihlásit se</Link>
                    </p>
                </form>
            </div>
        </Layout>
    );
}
