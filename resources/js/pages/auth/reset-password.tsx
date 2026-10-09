import { Errors, Layout } from '@/components/gomove/ui';
import { Head, useForm } from '@inertiajs/react';
export default function ResetPassword({ token, email }: { token: string; email: string }) {
    const form = useForm({ token, email, password: '', password_confirmation: '' });
    return (
        <Layout>
            <Head title="Nové heslo" />
            <div className="gm-auth">
                <div className="gm-auth-intro">
                    <p className="gm-eyebrow">ZPÁTKY DO HRY</p>
                    <h1>
                        Bezpečný návrat
                        <br />k vašim termínům.
                    </h1>
                </div>
                <form
                    className="gm-auth-form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
                    }}
                >
                    <h2>Nastavit nové heslo</h2>
                    <Errors errors={form.errors} />
                    <label className="gm-label">
                        E-mail
                        <input type="email" readOnly value={form.data.email} />
                    </label>
                    <label className="gm-label">
                        Nové heslo
                        <input
                            type="password"
                            minLength={8}
                            required
                            autoComplete="new-password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                    </label>
                    <label className="gm-label">
                        Heslo znovu
                        <input
                            type="password"
                            required
                            autoComplete="new-password"
                            value={form.data.password_confirmation}
                            onChange={(e) => form.setData('password_confirmation', e.target.value)}
                        />
                    </label>
                    <button className="gm-btn gm-btn-dark gm-full" disabled={form.processing}>
                        Uložit nové heslo
                    </button>
                </form>
            </div>
        </Layout>
    );
}
