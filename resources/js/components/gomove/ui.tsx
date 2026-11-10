import { Link, router, usePage } from '@inertiajs/react';
import { ArrowUpRight, CalendarDays, Check, MapPin, MoveUpRight } from 'lucide-react';
import { type ReactNode } from 'react';

export interface Resource {
    id: number;
    facility_id: number;
    is_whole: boolean;
    name: string;
    pricing_model: 'hourly' | 'per_slot' | 'agreement';
    rate_minor: number | null;
    slot_minutes: number;
    opens: string;
    closes: string;
    updated_at?: string;
}
export interface Facility {
    id: number;
    name: string;
    address: string;
    description: string;
    sport: string;
    style: string;
    amenities: string | null;
    conditions: string | null;
    organization_id: number;
    whole_field_enabled: boolean;
    organization_name: string;
    auto_approve: boolean;
    status: string;
    resources?: Resource[];
}
export interface Booking {
    id: number;
    resource_id: number;
    starts_at: string;
    ends_at: string;
    status: string;
    kind: string;
    message: string | null;
    price_minor: number | null;
    facility_name?: string;
    resource_name: string;
    requester_name?: string;
    requester_email?: string;
}
interface GoMoveShared {
    auth: { user: { id: number; name: string } | null; manager: boolean; admin: boolean };
    demo: boolean;
    flash: { success?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}
export const money = (minor: number) =>
    new Intl.NumberFormat('cs-CZ', { style: 'currency', currency: 'CZK', maximumFractionDigits: 2 }).format(minor / 100);
export const rate = (r: Resource) =>
    r.pricing_model === 'agreement'
        ? 'Cena dohodou'
        : `${money(r.rate_minor ?? 0)} / ${r.pricing_model === 'hourly' ? 'hod.' : `${r.slot_minutes} min`}`;
export const time = (date: string) =>
    new Intl.DateTimeFormat('cs-CZ', { timeZone: 'Europe/Prague', hour: '2-digit', minute: '2-digit' }).format(new Date(date));
export const day = (date: string) =>
    new Intl.DateTimeFormat('cs-CZ', { timeZone: 'Europe/Prague', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(date));
export const tomorrow = () => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return new Intl.DateTimeFormat('sv-SE', { timeZone: 'Europe/Prague' }).format(d);
};
export const statuses: Record<string, string> = {
    pending: 'Čeká na schválení',
    confirmed: 'Potvrzeno',
    declined: 'Zamítnuto',
    cancelled: 'Zrušeno',
    blocked: 'Blokováno',
    published: 'Zveřejněno',
    submitted: 'Ke schválení',
    draft: 'Koncept',
};
export function Badge({ status }: { status: string }) {
    return <span className={`gm-badge gm-badge-${status}`}>{statuses[status] ?? status}</span>;
}
export function Errors({ errors }: { errors: Record<string, string | undefined> }) {
    const values = Object.values(errors).filter(Boolean);
    return values.length ? (
        <div className="gm-error" role="alert">
            {values.map((e, i) => (
                <p key={i}>{e}</p>
            ))}
        </div>
    ) : null;
}
export function Layout({ children }: { children: ReactNode }) {
    const { auth, demo, flash, errors } = usePage<GoMoveShared>().props;
    return (
        <div className="gm-root">
            {demo && (
                <div className="gm-demo">
                    <span /> Prezentační demo · všechna sportoviště a organizace jsou smyšlené
                </div>
            )}
            <header className="gm-header">
                <Link href="/" className="gm-logo" aria-label="GoMove úvod">
                    <span className="gm-mark">
                        <MoveUpRight size={22} strokeWidth={3} />
                    </span>
                    go<span>move</span>
                    <small>PROSTOR PRO POHYB</small>
                </Link>
                <nav aria-label="Hlavní navigace">
                    <Link href="/">Sportoviště</Link>
                    {auth.user && <Link href="/dashboard">Moje rezervace</Link>}
                    {auth.manager && <Link href="/manage">Správa klubu</Link>}
                    {auth.admin && <Link href="/admin">Administrace</Link>}
                </nav>
                <div className="gm-account">
                    {auth.user ? (
                        <>
                            <span className="gm-user-name">{auth.user.name.split(' —')[0]}</span>
                            <button onClick={() => router.post('/logout')} className="gm-btn gm-btn-outline">
                                Odhlásit
                            </button>
                        </>
                    ) : (
                        <>
                            <Link href="/login">Přihlásit se</Link>
                            <Link className="gm-btn gm-btn-dark" href="/register">
                                Vytvořit účet <ArrowUpRight size={16} />
                            </Link>
                        </>
                    )}
                </div>
            </header>
            <main className="gm-main">
                {flash.success && (
                    <div className="gm-success" role="status">
                        <Check size={18} />
                        {flash.success}
                    </div>
                )}
                <Errors errors={errors} />
                {children}
            </main>
            <footer className="gm-footer">
                <Link href="/" className="gm-footer-logo">
                    gomove<span>↗</span>
                </Link>
                <p>Najděte své místo. Udělejte čas na pohyb.</p>
                <span>Český lokální projekt · CZK · Europe/Prague</span>
            </footer>
        </div>
    );
}
export function SportArt({ style, className = '' }: { style: string; className?: string }) {
    const field = style === 'field';
    const hall = style === 'hall';
    return (
        <div className={`gm-art gm-art-${style} ${className}`} aria-hidden="true">
            <span className="gm-art-tag">{field ? 'VENKOVNÍ HŘIŠTĚ' : hall ? 'KRYTÁ HALA' : 'TENISOVÉ KURTY'}</span>
            <svg viewBox="0 0 460 300" role="presentation">
                <defs>
                    <pattern id={`grid-${style}`} width="28" height="28" patternUnits="userSpaceOnUse">
                        <path d="M 28 0 L 0 0 0 28" fill="none" stroke="currentColor" strokeOpacity=".08" />
                    </pattern>
                </defs>
                <rect width="460" height="300" fill={`url(#grid-${style})`} />
                <g transform="translate(230 158) rotate(-15) translate(-155 -90)" fill="none" stroke="currentColor" strokeWidth="2.5">
                    <rect x="0" y="0" width="310" height="180" rx="2" />
                    <path d="M155 0v180" />
                    {field ? (
                        <>
                            <circle cx="155" cy="90" r="35" />
                            <path d="M0 40h50v100H0M310 40h-50v100h50M0 65h20v50H0M310 65h-20v50h20" />
                            <circle cx="155" cy="90" r="3" fill="currentColor" />
                        </>
                    ) : (
                        <>
                            <path d="M0 25h310M0 155h310M78 25v130M232 25v130M78 90h154" />
                            <path d="M155 -10v200" strokeDasharray="4 4" strokeWidth="5" />
                        </>
                    )}
                </g>
                <circle cx="368" cy="237" r="13" fill={field ? '#e5f18c' : hall ? '#ebc986' : '#d7ef5b'} />
                <path d="M359 231q10 7 17 1M358 241q10-7 20-2" stroke="#244831" fill="none" />
            </svg>
            <span className="gm-art-number">
                {field ? '02' : hall ? '03' : '01'} <MoveUpRight size={28} />
            </span>
        </div>
    );
}
export function FacilityCard({ facility }: { facility: Facility }) {
    const resources = facility.resources ?? [];
    const r = resources[0];
    return (
        <Link href={`/facilities/${facility.id}`} className="gm-card">
            <SportArt style={facility.style} />
            <div className="gm-card-body">
                <div className="gm-card-top">
                    <span className="gm-category">{facility.sport}</span>
                    <span>
                        {resources.length} {resources.length === 1 ? 'prostor' : 'prostory'}
                    </span>
                </div>
                <h3>{facility.name}</h3>
                <p className="gm-address">
                    <MapPin size={14} />
                    {facility.address}
                </p>
                <div className="gm-card-bottom">
                    <strong>{r ? rate(r) : 'Cena dohodou'}</strong>
                    <span className="gm-circle-arrow">
                        <ArrowUpRight size={21} />
                    </span>
                </div>
            </div>
        </Link>
    );
}
export function PageTitle({ eyebrow, title, text }: { eyebrow: string; title: string; text: string }) {
    return (
        <div className="gm-page-title">
            <p className="gm-eyebrow">{eyebrow}</p>
            <h1>{title}</h1>
            <p>{text}</p>
        </div>
    );
}
export function BookingList({ bookings, manager = false }: { bookings: Booking[]; manager?: boolean }) {
    return bookings.length ? (
        <div className="gm-booking-list">
            {bookings.map((b) => (
                <article className="gm-booking-row" key={b.id}>
                    <div className="gm-date-icon">
                        <CalendarDays size={24} />
                    </div>
                    <div className="gm-booking-info">
                        <h3>{b.facility_name ?? b.resource_name}</h3>
                        <p>
                            {b.facility_name ? `${b.resource_name} · ` : ''}
                            {day(b.starts_at)} · {time(b.starts_at)}–{time(b.ends_at)}
                        </p>
                        {manager && (
                            <p>
                                {b.requester_name ?? { training: 'Trénink', closure: 'Uzavírka', external: 'Externí rezervace' }[b.kind] ?? 'Blokace'}
                                {b.requester_email ? ` · ${b.requester_email}` : ''}
                            </p>
                        )}
                        {b.message && <p className="gm-note">{b.message}</p>}
                    </div>
                    <div className="gm-booking-state">
                        <Badge status={b.status} />
                        <strong>{b.price_minor === null ? 'Cena dohodou' : money(b.price_minor)}</strong>
                    </div>
                    <div className="gm-row-actions">
                        {new Date(b.starts_at) > new Date() && (
                            <>
                                {manager && b.status === 'pending' && (
                                    <>
                                        <button
                                            className="gm-btn gm-btn-dark"
                                            onClick={() => router.post(`/bookings/${b.id}/decision`, { action: 'confirm' })}
                                        >
                                            Schválit
                                        </button>
                                        <button
                                            className="gm-btn gm-btn-outline"
                                            onClick={() => router.post(`/bookings/${b.id}/decision`, { action: 'decline' })}
                                        >
                                            Zamítnout
                                        </button>
                                    </>
                                )}
                                {['pending', 'confirmed', 'blocked'].includes(b.status) && (
                                    <button
                                        className="gm-text-button"
                                        onClick={() => {
                                            if (window.confirm('Opravdu zrušit tento termín?'))
                                                router.post(`/bookings/${b.id}/decision`, { action: 'cancel' });
                                        }}
                                    >
                                        Zrušit
                                    </button>
                                )}
                            </>
                        )}
                    </div>
                </article>
            ))}
        </div>
    ) : (
        <div className="gm-empty">
            <CalendarDays size={35} />
            <h3>Zatím žádné rezervace</h3>
            <p>Vyberte sportoviště a najděte si čas na pohyb.</p>
            <Link href="/" className="gm-btn gm-btn-dark">
                Prohlédnout sportoviště <ArrowUpRight size={16} />
            </Link>
        </div>
    );
}
