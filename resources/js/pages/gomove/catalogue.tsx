import { FacilityCard, Layout, SportArt, type Facility } from '@/components/gomove/ui';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUpRight, Check, Clock3, MapPin } from 'lucide-react';

export default function Catalogue({ facilities, restricted, sport }: { facilities: Facility[]; restricted: boolean; sport: string }) {
    return (
        <Layout>
            <Head title="Prostor pro váš pohyb" />
            <section className="gm-hero">
                <div className="gm-hero-copy">
                    <p className="gm-eyebrow">
                        <span /> VÁŠ ČAS. VAŠE HRA.
                    </p>
                    <h1>
                        Méně hledání.
                        <br />
                        Více <span>pohybu.</span>
                    </h1>
                    <p className="gm-hero-description">
                        Kurt na odpoledne, hřiště pro celý tým nebo hala na společný trénink. Najděte si místo a rezervujte svůj čas.
                    </p>
                    <a href="#sportoviste" className="gm-btn gm-btn-dark gm-btn-large">
                        Najít sportoviště <ArrowUpRight size={20} />
                    </a>
                    <div className="gm-hero-trust">
                        <span>
                            <Check size={16} /> Přehledná dostupnost
                        </span>
                        <span>
                            <Check size={16} /> Přímo s organizací
                        </span>
                    </div>
                </div>
                <div className="gm-hero-visual">
                    <SportArt style="court" />
                    <div className="gm-floating-card">
                        <span className="gm-floating-icon">
                            <Clock3 size={23} />
                        </span>
                        <div>
                            <strong>Čas na vaši další hru</strong>
                            <small>Vyberte místo. Domluvte termín.</small>
                        </div>
                        <span className="gm-green-dot" />
                    </div>
                    <div className="gm-hero-caption">
                        DOBRÁ HRA ZAČÍNÁ DOBRÝM MÍSTEM <ArrowDown size={16} />
                    </div>
                </div>
            </section>
            <section id="sportoviste" className="gm-directory">
                <div className="gm-section-heading">
                    <div>
                        <p className="gm-eyebrow">NAJDĚTE SVÉ MÍSTO</p>
                        <h2>Kam vyrazíte dnes?</h2>
                    </div>
                    <span className="gm-location">
                        <MapPin size={16} /> Ukázkové město · demo
                    </span>
                </div>
                <div className="gm-filter-row">
                    <div className="gm-filters" aria-label="Filtr sportu">
                        {[
                            ['', 'Všechna sportoviště'],
                            ['Tenis', 'Tenis'],
                            ['Fotbal', 'Fotbal'],
                            ['Víceúčelové', 'Haly a další'],
                        ].map(([value, label]) => (
                            <button
                                key={value}
                                className={sport === value ? 'active' : ''}
                                aria-pressed={sport === value}
                                onClick={() => router.get('/', value ? { sport: value } : {}, { preserveScroll: true })}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                    <span>
                        {facilities.length} {facilities.length === 1 ? 'sportoviště' : 'sportoviště'}
                    </span>
                </div>
                {restricted ? (
                    <div className="gm-empty">
                        <h3>Sportoviště jsou přístupná po přihlášení</h3>
                        <Link className="gm-btn gm-btn-dark" href="/login">
                            Přihlásit se
                        </Link>
                    </div>
                ) : facilities.length ? (
                    <div className="gm-cards">
                        {facilities.map((f) => (
                            <FacilityCard key={f.id} facility={f} />
                        ))}
                    </div>
                ) : (
                    <div className="gm-empty">
                        <h3>Zatím tu není žádné sportoviště</h3>
                        <p>Zkuste jiný sport nebo se vraťte později.</p>
                    </div>
                )}
            </section>
            <section className="gm-how">
                <div>
                    <span>01</span>
                    <h3>Najděte své místo</h3>
                    <p>Vyberte sport a prostor, který vám sedí.</p>
                </div>
                <div>
                    <span>02</span>
                    <h3>Zvolte volný termín</h3>
                    <p>Dostupnost uvidíte ještě před rezervací.</p>
                </div>
                <div>
                    <span>03</span>
                    <h3>Vyrazte si zahrát</h3>
                    <p>Stav žádosti máte vždy ve svém přehledu.</p>
                </div>
            </section>
        </Layout>
    );
}
