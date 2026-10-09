import { Badge, Layout, PageTitle, type Facility } from '@/components/gomove/ui';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
export default function Admin({ facilities, publicBrowsing }: { facilities: Facility[]; publicBrowsing: boolean }) {
    const [checked, setChecked] = useState(publicBrowsing);
    const [busy, setBusy] = useState(false);
    return (
        <Layout>
            <Head title="Administrace" />
            <PageTitle eyebrow="SPRÁVA PLATFORMY" title="Důvěra začíná kontrolou." text="Schvalte první nabídky a nastavte přístup k prohlížení." />
            <div className="gm-setting">
                <div>
                    <strong>Prohlížení bez přihlášení</strong>
                    <p>
                        {publicBrowsing
                            ? 'Sportoviště i dostupnost si může prohlédnout každý.'
                            : 'Sportoviště jsou dostupná pouze přihlášeným uživatelům.'}
                    </p>
                </div>
                <label className="gm-toggle">
                    <input
                        type="checkbox"
                        checked={checked}
                        disabled={busy}
                        onChange={(e) => {
                            const next = e.target.checked;
                            setChecked(next);
                            setBusy(true);
                            router.put(
                                '/browsing',
                                { public_browsing: next },
                                { onError: () => setChecked(publicBrowsing), onFinish: () => setBusy(false) },
                            );
                        }}
                    />
                    Veřejné prohlížení
                </label>
            </div>
            <h2 className="gm-subheading">Nabídky sportovišť</h2>
            <div className="gm-admin-list">
                {facilities.map((f) => (
                    <article key={f.id} className="gm-admin-row">
                        <div>
                            <p className="gm-eyebrow">{f.organization_name}</p>
                            <h3>{f.name}</h3>
                            <p>{f.address}</p>
                            <p>{f.description}</p>
                        </div>
                        <div>
                            <Badge status={f.status} />
                            {f.status !== 'published' && (
                                <button className="gm-btn gm-btn-dark" onClick={() => router.post(`/facilities/${f.id}/publish`)}>
                                    Schválit a zveřejnit
                                </button>
                            )}
                        </div>
                    </article>
                ))}
            </div>
        </Layout>
    );
}
