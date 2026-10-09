import { Errors, Layout } from '@/components/gomove/ui';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
export default function Login({ status }: { status?: string; canResetPassword: boolean }) {
    const form = useForm<{ email: string; password: string; remember: boolean }>({ email: '', password: '', remember: false });
    return (
        <Layout>
            <Head title="Přihlášení" />
            <div className="gm-auth">
                <div className="gm-auth-intro">
                    <p className="gm-eyebrow">VÍTEJTE ZPÁTKY</p>
                    <h1>
                        Vaše další hra
                        <br />
                        začíná tady.
                    </h1>
                    <p>Přihlaste se a mějte své termíny vždy po ruce.</p>
                    <Link href="/" className="gm-back">
                        Prohlédnout sportoviště <ArrowUpRight size={16} />
                    </Link>
                </div>
                <form
                    className="gm-auth-form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/login', { onFinish: () => form.reset('password') });
                    }}
                >
                    <h2>Přihlásit se</h2>
                    <Errors errors={form.errors} />
                    {status && <p role="status">{status}</p>}
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
                        Heslo
                        <input
                            type="password"
                            autoComplete="current-password"
                            required
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                    </label>
                    <div className="gm-auth-options">
                        <label>
                            <input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />{' '}
                            Zapamatovat si mě
                        </label>
                        <Link href="/forgot-password">Zapomenuté heslo</Link>
                    </div>
                    <button className="gm-btn gm-btn-dark gm-full" disabled={form.processing}>
                        Přihlásit se <ArrowUpRight size={18} />
                    </button>
                    <p>
                        Ještě nemáte účet? <Link href="/register">Vytvořit účet</Link>
                    </p>
                </form>
            </div>
        </Layout>
    );
}
