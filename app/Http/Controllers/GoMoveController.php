<?php

namespace App\Http\Controllers;

use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class GoMoveController extends Controller
{
    private function customer(): object
    {
        return DB::table('gm_customers')->where('slug', config('gomove.customer'))->firstOrFail();
    }

    private function browse(Request $request): void
    {
        if (! $this->customer()->public_browsing && ! $request->user()) {
            abort(403, 'Pro zobrazení sportovišť se přihlaste.');
        }
    }

    private function facilities()
    {
        return DB::table('gm_facilities as f')->join('gm_organizations as o', 'o.id', '=', 'f.organization_id')
            ->where('o.customer_id', $this->customer()->id)
            ->select('f.*', 'o.name as organization_name', 'o.auto_approve');
    }

    private function resource(int $id): object
    {
        return DB::table('gm_resources as r')->join('gm_facilities as f', 'f.id', '=', 'r.facility_id')
            ->join('gm_organizations as o', 'o.id', '=', 'f.organization_id')->where('o.customer_id', $this->customer()->id)
            ->where('r.id', $id)->select('r.*', 'f.organization_id', 'f.status as publication_status')->firstOrFail();
    }

    private function manager(Request $request, int $organization): void
    {
        abort_unless(DB::table('gm_memberships')->where('organization_id', $organization)->where('user_id', $request->user()->id)->exists(), 403);
    }

    public function index(Request $request)
    {
        $customer = $this->customer();
        $restricted = ! $customer->public_browsing && ! $request->user();
        $query = $this->facilities()->where('f.status', 'published')->orderBy('f.id');
        $sport = $request->query('sport', '');
        if (is_string($sport) && $sport !== '') {
            $query->where('f.sport', $sport);
        }
        $facilities = $restricted ? collect() : $query->get()->map(function ($f) {
            $resources = DB::table('gm_resources')->where('facility_id', $f->id)->get();

            return [...(array) $f, 'resources' => $resources];
        });

        return Inertia::render('gomove/catalogue', ['facilities' => $facilities, 'restricted' => $restricted, 'sport' => is_string($sport) ? $sport : '']);
    }

    public function show(Request $request, int $id)
    {
        $this->browse($request);
        $facility = $this->facilities()->where('f.id', $id)->where('f.status', 'published')->firstOrFail();
        $validated = $request->validate(['date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now('Europe/Prague')->addDays(90)->format('Y-m-d')]]);
        $date = $validated['date'] ?? now('Europe/Prague')->addDay()->format('Y-m-d');
        $resources = DB::table('gm_resources')->where('facility_id', $id)->orderBy('id')->get();
        $start = CarbonImmutable::parse($date, 'Europe/Prague')->startOfDay()->utc();
        $end = $start->setTimezone('Europe/Prague')->addDay()->utc();
        $occupied = DB::table('gm_bookings')->whereIn('resource_id', $resources->pluck('id'))->whereIn('status', ['pending', 'confirmed', 'blocked'])
            ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->get(['resource_id', 'starts_at', 'ends_at']);

        return Inertia::render('gomove/facility', compact('facility', 'resources', 'occupied', 'date'));
    }

    private function bookingData(Request $request, bool $block = false): array
    {
        return $request->validate([
            'date' => ['required', 'date_format:Y-m-d'], 'time' => ['required', 'date_format:H:i'],
            'minutes' => ['required', 'integer', 'min:15', 'max:1440', 'multiple_of:15'],
            'message' => ['nullable', 'string', 'max:1000'],
            ...($block ? ['kind' => ['required', Rule::in(['training', 'closure', 'external'])]] : ['submission_key' => ['required', 'uuid']]),
        ]);
    }

    public function book(Request $request, int $id, ReservationService $service)
    {
        $this->browse($request);
        $resource = $this->resource($id);
        abort_unless($resource->publication_status === 'published', 404);
        $service->reserve($resource, $this->bookingData($request), $request->user()->id);

        return redirect('/dashboard')->with('success', 'Rezervace byla zaznamenána. Stav najdete v přehledu.');
    }

    public function dashboard(Request $request)
    {
        $bookings = DB::table('gm_bookings as b')->join('gm_resources as r', 'r.id', '=', 'b.resource_id')
            ->join('gm_facilities as f', 'f.id', '=', 'r.facility_id')->join('gm_organizations as o', 'o.id', '=', 'f.organization_id')
            ->where('o.customer_id', $this->customer()->id)->where('b.user_id', $request->user()->id)
            ->select('b.*', 'r.name as resource_name', 'f.name as facility_name')->orderByDesc('b.id')->limit(100)->get();

        return Inertia::render('gomove/bookings', compact('bookings'));
    }

    public function manage(Request $request)
    {
        $organizations = DB::table('gm_organizations as o')->join('gm_memberships as m', 'm.organization_id', '=', 'o.id')
            ->where('o.customer_id', $this->customer()->id)->where('m.user_id', $request->user()->id)->select('o.*')->get();
        abort_if($organizations->isEmpty(), 403);
        $facilities = $this->facilities()->whereIn('f.organization_id', $organizations->pluck('id'))->get();
        $resources = DB::table('gm_resources')->whereIn('facility_id', $facilities->pluck('id'))->get();
        $bookings = DB::table('gm_bookings as b')->leftJoin('users as u', 'u.id', '=', 'b.user_id')->join('gm_resources as r', 'r.id', '=', 'b.resource_id')
            ->whereIn('b.resource_id', $resources->pluck('id'))->select('b.*', 'r.name as resource_name', 'u.name as requester_name', 'u.email as requester_email')
            ->orderByDesc('b.starts_at')->limit(100)->get();

        return Inertia::render('gomove/manage', compact('organizations', 'facilities', 'resources', 'bookings'));
    }

    public function decide(Request $request, int $id, ReservationService $service)
    {
        $action = $request->validate(['action' => ['required', Rule::in(['confirm', 'decline', 'cancel'])]])['action'];
        DB::transaction(function () use ($request, $id, $action, $service) {
            $initial = DB::table('gm_bookings')->where('id', $id)->firstOrFail();
            $resource = $this->resource($initial->resource_id);
            DB::table('gm_resources')->where('id', $resource->id)->lockForUpdate()->first();
            $booking = DB::table('gm_bookings')->where('id', $id)->lockForUpdate()->firstOrFail();
            if ($action !== 'cancel' || $booking->user_id !== $request->user()->id) {
                $this->manager($request, $resource->organization_id);
            }
            if (! in_array($booking->status, ['pending', 'confirmed', 'blocked']) || CarbonImmutable::parse($booking->starts_at)->isPast()) {
                throw ValidationException::withMessages(['decision' => 'Tuto rezervaci už nelze změnit.']);
            }
            if ($action !== 'cancel' && $booking->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Rozhodnout lze pouze čekající žádost.']);
            }
            $status = ['confirm' => 'confirmed', 'decline' => 'declined', 'cancel' => 'cancelled'][$action];
            DB::table('gm_bookings')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
            $service->audit($request->user()->id, 'booking.'.$status, $id);
        });

        return back()->with('success', 'Stav byl aktualizován.');
    }

    public function block(Request $request, int $id, ReservationService $service)
    {
        $r = $this->resource($id);
        $this->manager($request, $r->organization_id);
        $service->reserve($r, $this->bookingData($request, true), $request->user()->id, true);

        return back()->with('success', 'Čas je nyní zablokován.');
    }

    public function organization(Request $request, int $id)
    {
        abort_unless(DB::table('gm_organizations')->where('id', $id)->where('customer_id', $this->customer()->id)->exists(), 404);
        $this->manager($request, $id);
        $data = $request->validate(['auto_approve' => ['required', 'boolean']]);
        DB::table('gm_organizations')->where('id', $id)->update($data);

        return back()->with('success', 'Režim schvalování byl změněn. Existující žádosti zůstávají beze změny.');
    }

    public function saveResource(Request $request, int $id, ReservationService $service)
    {
        $r = $this->resource($id);
        $this->manager($request, $r->organization_id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('gm_resources')->where('facility_id', $r->facility_id)->ignore($id)],
            'pricing_model' => ['required', Rule::in(['hourly', 'per_slot', 'agreement'])],
            'rate_czk' => ['nullable', 'required_unless:pricing_model,agreement', 'numeric', 'min:0', 'max:100000', 'decimal:0,2'],
            'slot_minutes' => ['required', 'integer', 'min:15', 'max:1440', 'multiple_of:15'],
            'opens' => ['required', 'date_format:H:i', 'after_or_equal:06:00'],
            'closes' => ['required', 'date_format:H:i', 'after:opens', 'before_or_equal:23:00'],
        ]);
        if ((int) substr($data['opens'], 3) % 15 || (int) substr($data['closes'], 3) % 15) {
            throw ValidationException::withMessages(['opens' => 'Otevírací doba musí používat 15minutové kroky.']);
        }
        $openingMinutes = (int) substr($data['closes'], 0, 2) * 60 + (int) substr($data['closes'], 3) - ((int) substr($data['opens'], 0, 2) * 60 + (int) substr($data['opens'], 3));
        if ($data['slot_minutes'] > $openingMinutes) {
            throw ValidationException::withMessages(['slot_minutes' => 'Slot musí být kratší než denní otevírací doba nebo stejně dlouhý.']);
        }
        DB::transaction(function () use ($data, $id, $request, $service) {
            DB::table('gm_resources')->where('id', $id)->lockForUpdate()->first();
            foreach (DB::table('gm_bookings')->where('resource_id', $id)->whereIn('status', ['pending', 'confirmed', 'blocked'])->where('ends_at', '>', now()->utc())->get() as $b) {
                if (CarbonImmutable::parse($b->starts_at)->setTimezone('Europe/Prague')->format('H:i') < $data['opens'] || CarbonImmutable::parse($b->ends_at)->setTimezone('Europe/Prague')->format('H:i') > $data['closes']) {
                    throw ValidationException::withMessages(['opens' => 'Nová otevírací doba koliduje s existující rezervací.']);
                }
            }
            $rate = $data['pricing_model'] === 'agreement' ? null : (int) round((float) $data['rate_czk'] * 100);
            unset($data['rate_czk']);
            DB::table('gm_resources')->where('id', $id)->update([...$data, 'rate_minor' => $rate, 'updated_at' => now()]);
            $service->audit($request->user()->id, 'resource.updated', $id);
        });

        return back()->with('success', 'Nastavení prostoru bylo uloženo.');
    }

    public function createFacility(Request $request)
    {
        $data = $request->validate(['organization_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:100'], 'address' => ['required', 'string', 'max:200'], 'sport' => ['required', Rule::in(['Tenis', 'Fotbal', 'Víceúčelové'])], 'description' => ['required', 'string', 'max:2000']]);
        abort_unless(DB::table('gm_organizations')->where('id', $data['organization_id'])->where('customer_id', $this->customer()->id)->exists(), 404);
        $this->manager($request, (int) $data['organization_id']);
        DB::transaction(function () use ($data) {
            $id = DB::table('gm_facilities')->insertGetId([...$data, 'status' => 'submitted', 'style' => $data['sport'] === 'Fotbal' ? 'field' : ($data['sport'] === 'Tenis' ? 'court' : 'hall'), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('gm_resources')->insert(['facility_id' => $id, 'name' => 'Hlavní prostor', 'pricing_model' => 'agreement', 'slot_minutes' => 60, 'opens' => '08:00', 'closes' => '22:00', 'created_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', 'Sportoviště bylo odesláno ke schválení.');
    }

    public function addResource(Request $request, int $id)
    {
        $f = $this->facilities()->where('f.id', $id)->firstOrFail();
        $this->manager($request, $f->organization_id);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('gm_resources')->where('facility_id', $id)]]);
        DB::table('gm_resources')->insert([...$data, 'facility_id' => $id, 'pricing_model' => 'agreement', 'slot_minutes' => 60, 'opens' => '08:00', 'closes' => '22:00', 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Samostatný prostor byl přidán.');
    }

    public function editFacility(Request $request, int $id)
    {
        $f = $this->facilities()->where('f.id', $id)->firstOrFail();
        $this->manager($request, $f->organization_id);
        $data = $request->validate(['description' => ['required', 'string', 'max:2000'], 'amenities' => ['nullable', 'string', 'max:1000'], 'conditions' => ['nullable', 'string', 'max:2000']]);
        DB::table('gm_facilities')->where('id', $id)->update([...$data, 'updated_at' => now()]);

        return back()->with('success', 'Informace byly uloženy. Schválené sportoviště je zveřejňuje ihned.');
    }

    public function admin(Request $request)
    {
        abort_unless($request->user()->gm_admin, 403);

        return Inertia::render('gomove/admin', ['facilities' => $this->facilities()->orderBy('f.id')->get(), 'publicBrowsing' => (bool) $this->customer()->public_browsing]);
    }

    public function publish(Request $request, int $id, ReservationService $service)
    {
        abort_unless($request->user()->gm_admin, 403);
        $f = $this->facilities()->where('f.id', $id)->firstOrFail();
        DB::transaction(function () use ($f, $request, $service) {
            DB::table('gm_facilities')->where('id', $f->id)->update(['status' => 'published', 'approved_at' => $f->approved_at ?? now(), 'updated_at' => now()]);
            $service->audit($request->user()->id, 'facility.published', $f->id);
        });

        return back()->with('success', 'Sportoviště bylo schváleno a zveřejněno.');
    }

    public function browsing(Request $request)
    {
        abort_unless($request->user()->gm_admin, 403);
        $data = $request->validate(['public_browsing' => ['required', 'boolean']]);
        DB::table('gm_customers')->where('id', $this->customer()->id)->update($data);

        return back()->with('success', 'Přístup k prohlížení byl změněn.');
    }
}
