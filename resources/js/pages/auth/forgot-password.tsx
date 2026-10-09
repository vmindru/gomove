import { Errors, Layout } from '@/components/gomove/ui';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
export default function ForgotPassword({ status }: { status?: string }) {
    const form = useForm({ email: '' });
    const { demo } = usePage<{ demo: boolean; [key: string]: unknown }>().props;
    return (
        <Layout>
            <Head title="Obnova hesla" />
            <div className="gm-auth">
                <div className="gm-auth-intro">
                    <p className="gm-eyebrow">ZPÁTKY DO HRY</p>
                    <h1>
                        Nové heslo.
                        <br />
                        Stejný účet.
                    </h1>
                    <p>Zadejte svůj e-mail a požádejte o odkaz k obnově hesla.</p>
                </div>
                <form
                    className="gm-auth-form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post('/forgot-password');
                    }}
                >
                    <h2>Obnova hesla</h2>
                    <Errors errors={form.errors} />
                    {status && <p role="status">{status}</p>}
                    <label className="gm-label">
                        E-mail
                        <input type="email" required value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                    </label>
                    <button className="gm-btn gm-btn-dark gm-full" disabled={form.processing}>
                        Požádat o obnovu
                    </button>
                    {demo && <p>V tomto lokálním demu se e-maily pouze zapisují do logu, neodesílají se.</p>}
                    <p>
                        <Link href="/login">Zpět na přihlášení</Link>
                    </p>
                </form>
            </div>
        </Layout>
    );
}
