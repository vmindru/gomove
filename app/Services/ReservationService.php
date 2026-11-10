<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function reserve(object $resource, array $data, int $actor, bool $block = false): int
    {
        try {
            return DB::transaction(function () use ($resource, $data, $actor, $block) {
                $facility = DB::table('gm_facilities')->where('id', $resource->facility_id)->lockForUpdate()->first();
                $r = DB::table('gm_resources')->where('id', $resource->id)->lockForUpdate()->first();
                $org = DB::table('gm_organizations')->where('id', $facility->organization_id)->lockForUpdate()->first();
                if (! $block && $facility->status !== 'published') {
                    $this->fail('Toto sportoviště zatím nepřijímá rezervace.');
                }
                if ($r->is_whole && ! $facility->whole_field_enabled) {
                    $this->fail('Rezervace celého hřiště není povolena.');
                }
                $local = $data['date'].' '.$data['time'];
                $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', $local, 'Europe/Prague');
                // Demo opening hours are daytime only, so ambiguous DST inputs cannot fall in valid opening hours.
                if ($start->format('Y-m-d H:i') !== $local) {
                    $this->fail('Tento místní čas neexistuje. Vyberte jiný čas.');
                }
                $minutes = (int) $data['minutes'];
                $end = $start->addMinutes($minutes);
                if ($start->minute % 15 || $minutes % 15 || $minutes < 15 || $minutes > 1440) {
                    $this->fail('Délka i začátek rezervace musí odpovídat 15minutovým intervalům (nejvýše 1440 minut).');
                }
                if ($start->lte(CarbonImmutable::now()) || $start->gt(CarbonImmutable::now('Europe/Prague')->addDays(90))) {
                    $this->fail('Vyberte budoucí termín v následujících 90 dnech.');
                }
                if ($start->format('H:i') < $r->opens || $end->format('Y-m-d') !== $start->format('Y-m-d') || $end->format('H:i') > $r->closes) {
                    $this->fail('Termín je mimo otevírací dobu.');
                }
                $openingMinute = (int) substr($r->opens, 0, 2) * 60 + (int) substr($r->opens, 3);
                $startMinute = $start->hour * 60 + $start->minute;
                if (! $block && ($minutes % $r->slot_minutes || ($startMinute - $openingMinute) % $r->slot_minutes)) {
                    $this->fail('Vyberte celé sloty sportoviště o délce '.$r->slot_minutes.' minut.');
                }
                $utcStart = $start->utc()->format('Y-m-d H:i:sP');
                $utcEnd = $end->utc()->format('Y-m-d H:i:sP');
                if (! $block) {
                    $existing = DB::table('gm_bookings')->where('submission_key', $data['submission_key'])->first();
                    if ($existing) {
                        if ($existing->user_id !== $actor || $existing->resource_id !== $r->id || CarbonImmutable::parse($existing->starts_at)->ne($start) || CarbonImmutable::parse($existing->ends_at)->ne($end) || ($existing->message ?? '') !== ($data['message'] ?? '')) {
                            $this->fail('Tento formulář už byl použit. Obnovte stránku.');
                        }

                        return $existing->id;
                    }
                }
                $parts = $r->is_whole
                   ? DB::table('gm_resources')->where('facility_id', $r->facility_id)->where('is_whole', false)->get()
                   : collect([$r]);
                if ($parts->isEmpty()) {
                    $this->fail('Celé hřiště zatím nemá nastavené části.');
                }
                foreach ($parts as $part) {
                    if ($start->format('H:i') < $part->opens || $end->format('H:i') > $part->closes) {
                        $this->fail('Termín je mimo otevírací dobu některé části hřiště.');
                    }
                }
                if (DB::table('gm_booking_parts')->whereIn('resource_id', $parts->pluck('id'))->whereIn('status', ['pending', 'confirmed', 'blocked'])->where('starts_at', '<', $utcEnd)->where('ends_at', '>', $utcStart)->exists()) {
                    $this->fail('Tento čas už není volný. Vyberte jiný termín.');
                }
                $price = $block || $r->pricing_model === 'agreement' ? null : ($r->pricing_model === 'per_slot' ? $r->rate_minor * intdiv($minutes, $r->slot_minutes) : (int) ceil($r->rate_minor * $minutes / 60));
                $id = DB::table('gm_bookings')->insertGetId([
                    'resource_id' => $r->id, 'user_id' => $block ? null : $actor,
                    'starts_at' => $utcStart, 'ends_at' => $utcEnd,
                    'status' => $block ? 'blocked' : ($org->auto_approve ? 'confirmed' : 'pending'),
                    'kind' => $block ? $data['kind'] : 'booking', 'message' => $data['message'] ?? null,
                    'pricing_model' => $r->pricing_model, 'price_minor' => $price, 'rate_minor' => $r->rate_minor,
                    'slot_minutes' => $r->slot_minutes, 'submission_id' => $data['submission_id'] ?? null, 'submission_key' => $block ? null : $data['submission_key'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->audit($actor, $block ? 'calendar.blocked' : 'booking.created', $id);

                return $id;
            }, 3);
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23P01', '23505'])) {
                $this->fail('Tento čas nebo požadavek už byl zaznamenán. Obnovte dostupnost.');
            }
            throw $e;
        }
    }

    public function reserveSlots(object $resource, array $data, int $actor): array
    {
        try {
            return DB::transaction(function () use ($resource, $data, $actor) {
                DB::table('gm_facilities')->where('id', $resource->facility_id)->lockForUpdate()->first();
                $r = DB::table('gm_resources')->where('id', $resource->id)->lockForUpdate()->first();
                $slots = $data['slots'];
                sort($slots);
                $hash = hash('sha256', json_encode([$actor, $r->id, $data['date'], $slots, (int) $data['slot_minutes'], $data['message'] ?? '']));
                $existing = DB::table('gm_submissions')->where('id', $data['submission_key'])->first();
                if ($existing) {
                    if ($existing->user_id !== $actor || $existing->resource_id !== $r->id || ! hash_equals($existing->payload_hash, $hash)) {
                        $this->fail('Tento formulář už byl použit pro jiný výběr. Obnovte stránku.');
                    }

                    return DB::table('gm_bookings')->where('submission_id', $existing->id)->orderBy('id')->pluck('id')->all();
                }
                if ((int) $r->slot_minutes !== (int) $data['slot_minutes']) {
                    $this->fail('Organizace změnila délku slotu. Obnovte stránku a vyberte termíny znovu.');
                }
                $opening = (int) substr($r->opens, 0, 2) * 60 + (int) substr($r->opens, 3);
                $closing = (int) substr($r->closes, 0, 2) * 60 + (int) substr($r->closes, 3);
                $groups = [];
                foreach ($slots as $time) {
                    $minute = (int) substr($time, 0, 2) * 60 + (int) substr($time, 3);
                    if ($minute < $opening || $minute + $r->slot_minutes > $closing || ($minute - $opening) % $r->slot_minutes) {
                        $this->fail('Výběr neodpovídá slotům sportoviště. Obnovte dostupnost.');
                    }
                    $last = count($groups) - 1;
                    if ($last >= 0 && $groups[$last]['end'] === $minute) {
                        $groups[$last]['end'] += $r->slot_minutes;
                    } else {
                        $groups[] = ['start' => $minute, 'end' => $minute + $r->slot_minutes];
                    }
                }
                DB::table('gm_submissions')->insert(['id' => $data['submission_key'], 'user_id' => $actor, 'resource_id' => $r->id, 'payload_hash' => $hash, 'created_at' => now()]);
                $ids = [];
                foreach ($groups as $group) {
                    $time = sprintf('%02d:%02d', intdiv($group['start'], 60), $group['start'] % 60);
                    $ids[] = $this->reserve($r, [
                        'date' => $data['date'], 'time' => $time, 'minutes' => $group['end'] - $group['start'],
                        'message' => $data['message'] ?? null, 'submission_id' => $data['submission_key'],
                        'submission_key' => (string) Str::uuid(),
                    ], $actor);
                }

                return $ids;
            }, 3);
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23P01', '23505'])) {
                $this->fail('Termín nebo požadavek už byl zaznamenán. Obnovte dostupnost.');
            }
            throw $e;
        }
    }

    public function audit(int $actor, string $action, int $id): void
    {
        DB::table('gm_audit')->insert(['user_id' => $actor, 'action' => $action, 'entity_id' => $id, 'created_at' => now()]);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['reservation' => $message]);
    }
}
