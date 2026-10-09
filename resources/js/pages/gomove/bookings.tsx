import { BookingList, Layout, PageTitle, type Booking } from '@/components/gomove/ui';
import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
export default function Bookings({ bookings }: { bookings: Booking[] }) {
    return (
        <Layout>
            <Head title="Moje rezervace" />
            <div className="gm-heading-row">
                <PageTitle eyebrow="VÁŠ SPORTOVNÍ PLÁN" title="Moje rezervace" text="Všechny vaše termíny a aktuální stav na jednom místě." />
                <Link className="gm-btn gm-btn-dark" href="/">
                    Najít další termín <ArrowUpRight size={18} />
                </Link>
            </div>
            <div className="gm-stats">
                <div>
                    <strong>{bookings.filter((b) => b.status === 'confirmed').length}</strong>
                    <span>Potvrzené rezervace</span>
                </div>
                <div>
                    <strong>{bookings.filter((b) => b.status === 'pending').length}</strong>
                    <span>Čeká na rozhodnutí</span>
                </div>
                <div>
                    <strong>{bookings.length}</strong>
                    <span>Rezervací celkem</span>
                </div>
            </div>
            <BookingList bookings={bookings} />
        </Layout>
    );
}
