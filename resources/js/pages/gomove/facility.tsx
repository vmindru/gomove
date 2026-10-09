import { Errors, Layout, SportArt, money, rate, time, type Facility, type Resource } from '@/components/gomove/ui';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight, CalendarDays, Check, MapPin, ShieldCheck } from 'lucide-react';
import { useState, type FormEvent } from 'react';

interface Occupied {
    resource_id: number;
    starts_at: string;
    ends_at: string;
}
interface FacilityProps {
    facility: Facility;
    resources: Resource[];
    occupied: Occupied[];
    date: string;
}
export default function FacilityPage(props: FacilityProps) {
    return <FacilityContent key={`${props.facility.id}-${props.date}`} {...props} />;
}
function FacilityContent({ facility, resources, occupied, date }: FacilityProps) {
    const { auth } = usePage<{ auth: { user: unknown }; [key: string]: unknown }>().props;
    const [selected, setSelected] = useState(resources[0]?.id);
    const resource = resources.find((r) => r.id === selected) ?? resources[0];
    const form = useForm({ date, time: '', minutes: resource?.slot_minutes ?? 60, message: '', submission_key: crypto.randomUUID() });
    if (!resource)
        return (
            <Layout>
                <p>Pro toto sportoviště zatím nejsou připravené prostory.</p>
            </Layout>
        );
    const startMinute = Number(resource.opens.slice(0, 2)) * 60 + Number(resource.opens.slice(3));
    const endMinute = Number(resource.closes.slice(0, 2)) * 60 + Number(resource.closes.slice(3));
    const slots = Array.from({ length: Math.max(0, Math.floor((endMinute - startMinute) / 15)) }, (_, i) => startMinute + i * 15).filter(
        (m) => m + form.data.minutes <= endMinute,
    );
    const busy = occupied.filter((o) => o.resource_id === resource.id);
    const localMinutes = (instant: string) => {
        const t = time(instant);
        return Number(t.slice(0, 2)) * 60 + Number(t.slice(3));
    };
    const unavailable = (minute: number) =>
        busy.some((o) => minute < localMinutes(o.ends_at) && minute + form.data.minutes > localMinutes(o.starts_at));
    const formatMinute = (m: number) =>
        `${Math.floor(m / 60)
            .toString()
            .padStart(2, '0')}:${(m % 60).toString().padStart(2, '0')}`;
    const price =
        resource.pricing_model === 'agreement'
            ? null
            : resource.pricing_model === 'per_slot'
              ? resource.rate_minor
              : Math.ceil(((resource.rate_minor ?? 0) * form.data.minutes) / 60);
    function submit(e: FormEvent) {
        e.preventDefault();
        form.post(`/resources/${resource.id}/book`);
    }
    return (
        <Layout>
            <Head title={facility.name} />
            <Link href="/" className="gm-back">
                <ArrowLeft size={16} /> Všechna sportoviště
            </Link>
            <div className="gm-detail-title">
                <div>
                    <p className="gm-eyebrow">{facility.sport} · UKÁZKOVÉ SPORTOVIŠTĚ</p>
                    <h1>{facility.name}</h1>
                    <p className="gm-address">
                        <MapPin size={16} />
                        {facility.address}
                    </p>
                </div>
                <span className="gm-pill">
                    <ShieldCheck size={16} /> Schválená nabídka
                </span>
            </div>
            <div className="gm-detail-layout">
                <div className="gm-detail-main">
                    <SportArt style={facility.style} />
                    <section className="gm-detail-section">
                        <h2>Prostor pro vaši hru</h2>
                        <p>{facility.description}</p>
                        <div className="gm-amenities">
                            {facility.amenities?.split('·').map((a) => (
                                <span key={a}>
                                    <Check size={15} />
                                    {a.trim()}
                                </span>
                            ))}
                        </div>
                    </section>
                    <section className="gm-detail-section">
                        <div className="gm-section-heading">
                            <h2>Dostupnost a termíny</h2>
                            <span className="gm-small">Časy v Europe/Prague</span>
                        </div>
                        <label className="gm-label">
                            Vyberte prostor
                            <div className="gm-resource-tabs">
                                {resources.map((r) => (
                                    <button
                                        key={r.id}
                                        type="button"
                                        aria-pressed={resource.id === r.id}
                                        className={resource.id === r.id ? 'active' : ''}
                                        onClick={() => {
                                            setSelected(r.id);
                                            form.setData((data) => ({
                                                ...data,
                                                time: '',
                                                minutes: r.slot_minutes,
                                                submission_key: crypto.randomUUID(),
                                            }));
                                        }}
                                    >
                                        {r.name}
                                    </button>
                                ))}
                            </div>
                        </label>
                        <div className="gm-two-fields">
                            <label className="gm-label">
                                Datum
                                <input
                                    type="date"
                                    value={date}
                                    onChange={(e) => {
                                        if (e.target.value)
                                            router.get(`/facilities/${facility.id}`, { date: e.target.value }, { preserveScroll: true });
                                    }}
                                />
                            </label>
                            <label className="gm-label">
                                Délka rezervace
                                <select
                                    value={form.data.minutes}
                                    disabled={resource.pricing_model === 'per_slot'}
                                    onChange={(e) => form.setData((data) => ({ ...data, minutes: Number(e.target.value), time: '' }))}
                                >
                                    {Array.from({ length: Math.floor((endMinute - startMinute) / 15) }, (_, i) => (i + 1) * 15).map((m) => (
                                        <option key={m} value={m}>
                                            {m} minut
                                        </option>
                                    ))}
                                </select>
                            </label>
                        </div>
                        <div className="gm-calendar-legend">
                            <span>
                                <i /> Volný začátek
                            </span>
                            <span>
                                <i className="busy" /> Obsazeno
                            </span>
                            <span>
                                Otevřeno {resource.opens}–{resource.closes}
                            </span>
                        </div>
                        {busy.length > 0 && (
                            <p className="gm-unavailable">Obsazené časy: {busy.map((o) => `${time(o.starts_at)}–${time(o.ends_at)}`).join(', ')}</p>
                        )}
                        <div className="gm-time-grid">
                            {slots.map((m) => (
                                <button
                                    key={m}
                                    disabled={unavailable(m)}
                                    aria-pressed={form.data.time === formatMinute(m)}
                                    className={form.data.time === formatMinute(m) ? 'selected' : ''}
                                    onClick={() => form.setData('time', formatMinute(m))}
                                >
                                    {formatMinute(m)}
                                </button>
                            ))}
                        </div>
                    </section>
                    <section className="gm-detail-section">
                        <h2>Co je dobré vědět</h2>
                        <p>{facility.conditions}</p>
                        <p className="gm-small">Provozovatel: {facility.organization_name}. Úhrada probíhá přímo s organizací, bez online platby.</p>
                    </section>
                </div>
                <aside className="gm-reservation-panel">
                    <p className="gm-eyebrow">VAŠE DALŠÍ HRA</p>
                    <h2>Rezervujte si čas</h2>
                    <p>{resource.name}</p>
                    <div className="gm-price">
                        <strong>{rate(resource)}</strong>
                    </div>
                    <div className="gm-approval">
                        <ShieldCheck size={20} />
                        <div>
                            <strong>{facility.auto_approve ? 'Okamžité potvrzení' : 'Schválení organizací'}</strong>
                            <p>{facility.auto_approve ? 'Volný termín potvrdíme ihned.' : 'Žádost drží termín do rozhodnutí správce.'}</p>
                        </div>
                    </div>
                    <form onSubmit={submit}>
                        <Errors errors={form.errors} />
                        <div className="gm-selection">
                            <CalendarDays size={18} />
                            <span>
                                {new Intl.DateTimeFormat('cs-CZ', { dateStyle: 'medium' }).format(new Date(date + 'T12:00:00'))}
                                <br />
                                <strong>{form.data.time ? `${form.data.time} · ${form.data.minutes} minut` : 'Vyberte volný čas v kalendáři'}</strong>
                            </span>
                        </div>
                        <label className="gm-label">
                            Zpráva organizaci <span>(nepovinné)</span>
                            <textarea
                                rows={3}
                                maxLength={1000}
                                value={form.data.message}
                                onChange={(e) => form.setData('message', e.target.value)}
                                placeholder="Například informace o vašem týmu…"
                            />
                        </label>
                        <div className="gm-total">
                            <span>{price === null ? 'Cena' : 'Celkem za rezervaci'}</span>
                            <strong>{price === null ? 'Dohodou' : money(price)}</strong>
                        </div>
                        {auth.user ? (
                            <button className="gm-btn gm-btn-dark gm-full" disabled={!form.data.time || form.processing} type="submit">
                                {form.processing ? 'Odesíláme…' : facility.auto_approve ? 'Rezervovat termín' : 'Odeslat žádost'}
                                <ArrowUpRight size={18} />
                            </button>
                        ) : (
                            <Link className="gm-btn gm-btn-dark gm-full" href={`/login?facility=${facility.id}&date=${date}`}>
                                Přihlásit se a rezervovat <ArrowUpRight size={18} />
                            </Link>
                        )}
                        <p className="gm-panel-footnote">Platíte přímo organizaci. Své rezervace a jejich stav najdete ve svém účtu.</p>
                    </form>
                </aside>
            </div>
        </Layout>
    );
}
