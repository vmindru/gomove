import { Badge, BookingList, Errors, Layout, PageTitle, tomorrow, type Booking, type Facility, type Resource } from '@/components/gomove/ui';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowUpRight, Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';
interface Organization {
    id: number;
    name: string;
    auto_approve: boolean;
}
function ApprovalToggle({ organization }: { organization: Organization }) {
    const [checked, setChecked] = useState(organization.auto_approve);
    const [busy, setBusy] = useState(false);
    return (
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
                        `/organizations/${organization.id}`,
                        { auto_approve: next },
                        {
                            onError: () => setChecked(organization.auto_approve),
                            onFinish: () => setBusy(false),
                        },
                    );
                }}
            />
            Automatické schvalování
        </label>
    );
}
function ResourceEditor({ resource }: { resource: Resource }) {
    const form = useForm({
        name: resource.name,
        pricing_model: resource.pricing_model,
        rate_czk: resource.rate_minor === null ? '' : String(resource.rate_minor / 100),
        slot_minutes: resource.slot_minutes,
        opens: resource.opens,
        closes: resource.closes,
    });
    return (
        <form
            className="gm-resource-editor"
            onSubmit={(e: FormEvent) => {
                e.preventDefault();
                form.put(`/resources/${resource.id}`);
            }}
        >
            <Errors errors={form.errors} />
            <label className="gm-label">
                Název prostoru
                <input required value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
            </label>
            <div className="gm-two-fields">
                <label className="gm-label">
                    Cenový model
                    <select
                        value={form.data.pricing_model}
                        onChange={(e) => form.setData('pricing_model', e.target.value as Resource['pricing_model'])}
                    >
                        <option value="hourly">Hodinová sazba</option>
                        <option value="per_slot">Cena za slot</option>
                        <option value="agreement">Cena dohodou</option>
                    </select>
                </label>
                <label className="gm-label">
                    Sazba v CZK
                    <input
                        type="number"
                        min="0"
                        max="100000"
                        step="0.01"
                        disabled={form.data.pricing_model === 'agreement'}
                        required={form.data.pricing_model !== 'agreement'}
                        value={form.data.rate_czk}
                        onChange={(e) => form.setData('rate_czk', e.target.value)}
                    />
                </label>
            </div>
            <label className="gm-label">
                Délka slotu (15minutové kroky)
                <input
                    type="number"
                    min="15"
                    max="1440"
                    step="15"
                    required
                    value={form.data.slot_minutes}
                    onChange={(e) => form.setData('slot_minutes', Number(e.target.value))}
                />
            </label>
            <div className="gm-two-fields">
                <label className="gm-label">
                    Denně otevřeno od
                    <input type="time" min="06:00" value={form.data.opens} onChange={(e) => form.setData('opens', e.target.value)} required />
                </label>
                <label className="gm-label">
                    Denně otevřeno do
                    <input type="time" max="23:00" value={form.data.closes} onChange={(e) => form.setData('closes', e.target.value)} required />
                </label>
            </div>
            <button className="gm-btn gm-btn-dark" disabled={form.processing}>
                Uložit prostor
            </button>
        </form>
    );
}
function FacilityEditor({ facility, resources }: { facility: Facility; resources: Resource[] }) {
    const form = useForm({ description: facility.description, amenities: facility.amenities ?? '', conditions: facility.conditions ?? '' });
    const part = useForm({ name: '' });
    return (
        <details className="gm-manage-facility">
            <summary>
                <div>
                    <h3>{facility.name}</h3>
                    <p>{facility.address}</p>
                </div>
                <Badge status={facility.status} />
            </summary>
            <div className="gm-manage-facility-body">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(`/facilities/${facility.id}`);
                    }}
                >
                    <label className="gm-label">
                        Popis
                        <textarea rows={3} required value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                    </label>
                    <label className="gm-label">
                        Vybavení (oddělte znakem ·)
                        <input value={form.data.amenities} onChange={(e) => form.setData('amenities', e.target.value)} />
                    </label>
                    <label className="gm-label">
                        Podmínky a platební instrukce
                        <textarea rows={2} value={form.data.conditions} onChange={(e) => form.setData('conditions', e.target.value)} />
                    </label>
                    <button className="gm-btn gm-btn-outline" disabled={form.processing}>
                        Uložit informace
                    </button>
                </form>
                <h4>Samostatně rezervovatelné prostory</h4>
                <div className="gm-resource-editors">
                    {resources
                        .filter((r) => r.facility_id === facility.id)
                        .map((r) => (
                            <ResourceEditor key={`${r.id}-${r.updated_at ?? ''}`} resource={r} />
                        ))}
                </div>
                <form
                    className="gm-inline-form"
                    onSubmit={(e) => {
                        e.preventDefault();
                        part.post(`/facilities/${facility.id}/resources`, { onSuccess: () => part.reset() });
                    }}
                >
                    <label className="gm-label">
                        Nový samostatný prostor
                        <input
                            required
                            placeholder="Např. Kurt 3 nebo Polovina C"
                            value={part.data.name}
                            onChange={(e) => part.setData('name', e.target.value)}
                        />
                    </label>
                    <button className="gm-btn gm-btn-outline" disabled={part.processing}>
                        <Plus size={16} /> Přidat prostor
                    </button>
                </form>
                {facility.status === 'published' && (
                    <Link className="gm-back" href={`/facilities/${facility.id}`}>
                        Zobrazit veřejnou nabídku <ArrowUpRight size={16} />
                    </Link>
                )}
            </div>
        </details>
    );
}
function BlockForm({ resources }: { resources: Resource[] }) {
    const form = useForm({ resource_id: resources[0]?.id ?? 0, date: tomorrow(), time: '16:00', minutes: 60, kind: 'training', message: '' });
    return (
        <form
            className="gm-form-panel"
            onSubmit={(e) => {
                e.preventDefault();
                form.post(`/resources/${form.data.resource_id}/block`);
            }}
        >
            <h3>Zablokovat termín</h3>
            <p>Trénink, uzavírka nebo rezervace mimo GoMove. Veřejnost uvidí jen obsazený čas.</p>
            <Errors errors={form.errors} />
            <label className="gm-label">
                Prostor
                <select value={form.data.resource_id} onChange={(e) => form.setData('resource_id', Number(e.target.value))}>
                    {resources.map((r) => (
                        <option value={r.id} key={r.id}>
                            {r.name} (#{r.facility_id})
                        </option>
                    ))}
                </select>
            </label>
            <div className="gm-two-fields">
                <label className="gm-label">
                    Datum
                    <input type="date" required value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} />
                </label>
                <label className="gm-label">
                    Začátek
                    <input type="time" required step={900} value={form.data.time} onChange={(e) => form.setData('time', e.target.value)} />
                </label>
            </div>
            <div className="gm-two-fields">
                <label className="gm-label">
                    Délka v minutách
                    <input
                        type="number"
                        required
                        min={15}
                        max={1440}
                        step={15}
                        value={form.data.minutes}
                        onChange={(e) => form.setData('minutes', Number(e.target.value))}
                    />
                </label>
                <label className="gm-label">
                    Důvod
                    <select value={form.data.kind} onChange={(e) => form.setData('kind', e.target.value)}>
                        <option value="training">Trénink</option>
                        <option value="closure">Uzavírka</option>
                        <option value="external">Externí rezervace</option>
                    </select>
                </label>
            </div>
            <label className="gm-label">
                Interní poznámka
                <input maxLength={1000} value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} />
            </label>
            <button className="gm-btn gm-btn-dark" disabled={form.processing || !resources.length}>
                Zablokovat čas
            </button>
        </form>
    );
}
function NewFacility({ organizations }: { organizations: Organization[] }) {
    const form = useForm({ organization_id: organizations[0]?.id ?? 0, name: '', address: '', sport: 'Tenis', description: '' });
    return (
        <form
            className="gm-form-panel"
            onSubmit={(e) => {
                e.preventDefault();
                form.post('/facilities', { onSuccess: () => form.reset('name', 'address', 'description') });
            }}
        >
            <h3>Nové sportoviště</h3>
            <p>První zveřejnění schvaluje administrátor. Další úpravy publikujete sami.</p>
            <label className="gm-label">
                Organizace
                <select value={form.data.organization_id} onChange={(e) => form.setData('organization_id', Number(e.target.value))}>
                    {organizations.map((o) => (
                        <option key={o.id} value={o.id}>
                            {o.name}
                        </option>
                    ))}
                </select>
            </label>
            <label className="gm-label">
                Název
                <input required maxLength={100} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
            </label>
            <label className="gm-label">
                Adresa
                <input required maxLength={200} value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} />
            </label>
            <label className="gm-label">
                Sport
                <select value={form.data.sport} onChange={(e) => form.setData('sport', e.target.value)}>
                    <option>Tenis</option>
                    <option>Fotbal</option>
                    <option>Víceúčelové</option>
                </select>
            </label>
            <label className="gm-label">
                Popis
                <textarea rows={2} required value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
            </label>
            <button className="gm-btn gm-btn-dark" disabled={form.processing}>
                Odeslat ke schválení <ArrowUpRight size={16} />
            </button>
        </form>
    );
}
export default function Manage({
    organizations,
    facilities,
    resources,
    bookings,
}: {
    organizations: Organization[];
    facilities: Facility[];
    resources: Resource[];
    bookings: Booking[];
}) {
    const [tab, setTab] = useState('requests');
    const pending = bookings.filter((b) => b.status === 'pending');
    return (
        <Layout>
            <Head title="Správa klubu" />
            <PageTitle eyebrow="PRO ORGANIZACE" title="Vaše hřiště. Váš přehled." text="Spravujte nabídku, dostupnost i žádosti na jednom místě." />
            <div className="gm-stats">
                <div>
                    <strong>{pending.length}</strong>
                    <span>Žádostí k rozhodnutí</span>
                </div>
                <div>
                    <strong>{resources.length}</strong>
                    <span>Samostatných prostorů</span>
                </div>
                <div>
                    <strong>{facilities.length}</strong>
                    <span>Sportovišť ve správě</span>
                </div>
            </div>
            <div className="gm-manage-settings">
                {organizations.map((o) => (
                    <div className="gm-setting" key={o.id}>
                        <div>
                            <strong>{o.name}</strong>
                            <p>{o.auto_approve ? 'Nové rezervace se potvrzují automaticky.' : 'Nové žádosti čekají na vaše schválení.'}</p>
                        </div>
                        <ApprovalToggle key={`${o.id}-${o.auto_approve}`} organization={o} />
                    </div>
                ))}
            </div>
            <div className="gm-filters gm-manager-tabs">
                {[
                    ['requests', `Žádosti (${pending.length})`],
                    ['calendar', 'Kalendář a blokace'],
                    ['facilities', 'Sportoviště a ceny'],
                ].map(([value, label]) => (
                    <button aria-pressed={tab === value} className={tab === value ? 'active' : ''} key={value} onClick={() => setTab(value)}>
                        {label}
                    </button>
                ))}
            </div>
            {tab === 'requests' && (
                <>
                    <h2 className="gm-subheading">Čekající žádosti</h2>
                    <BookingList bookings={pending} manager />
                    <h2 className="gm-subheading">Historie a další termíny</h2>
                    <BookingList bookings={bookings.filter((b) => b.status !== 'pending')} manager />
                </>
            )}
            {tab === 'calendar' && (
                <>
                    <BlockForm resources={resources} />
                    <h2 className="gm-subheading">Rezervace a blokované termíny</h2>
                    <BookingList bookings={bookings.filter((b) => ['pending', 'confirmed', 'blocked'].includes(b.status))} manager />
                </>
            )}
            {tab === 'facilities' && (
                <>
                    <div className="gm-manage-facilities">
                        {facilities.map((f) => (
                            <FacilityEditor key={f.id} facility={f} resources={resources} />
                        ))}
                    </div>
                    <NewFacility organizations={organizations} />
                </>
            )}
        </Layout>
    );
}
