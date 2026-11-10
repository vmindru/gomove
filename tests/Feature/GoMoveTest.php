<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GoMoveTest extends TestCase
{
    use RefreshDatabase;

    private User $visitor;

    private User $manager;

    private int $org;

    private int $facility;

    private int $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->visitor = User::factory()->create();
        $this->manager = User::factory()->create();
        $this->org = DB::table('gm_organizations')->insertGetId(['customer_id' => DB::table('gm_customers')->value('id'), 'name' => 'Fictional club', 'email' => 'club@example.test']);
        DB::table('gm_memberships')->insert(['organization_id' => $this->org, 'user_id' => $this->manager->id]);
        $this->facility = DB::table('gm_facilities')->insertGetId(['organization_id' => $this->org, 'name' => 'Fictional court', 'address' => 'Demo address', 'description' => 'Demo', 'sport' => 'Tenis', 'status' => 'published', 'approved_at' => now()]);
        $this->resource = DB::table('gm_resources')->insertGetId(['facility_id' => $this->facility, 'name' => 'Half A', 'pricing_model' => 'hourly', 'rate_minor' => 28000, 'slot_minutes' => 15, 'opens' => '08:00', 'closes' => '22:00']);
    }

    private function payload(string $time = '10:00', int $minutes = 60): array
    {
        return ['date' => now('Europe/Prague')->addDay()->format('Y-m-d'), 'time' => $time, 'minutes' => $minutes, 'message' => 'Private requester note', 'submission_key' => (string) Str::uuid()];
    }

    public function test_manual_requests_block_overlap_but_allow_adjacent_and_independent_parts(): void
    {
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gm_bookings', ['status' => 'pending', 'price_minor' => 28000]);
        $this->post('/resources/'.$this->resource.'/book', $this->payload('10:30'))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$this->resource.'/book', $this->payload('11:00'))->assertSessionHasNoErrors();
        $r2 = DB::table('gm_resources')->insertGetId(['facility_id' => $this->facility, 'name' => 'Half B', 'pricing_model' => 'agreement', 'slot_minutes' => 60, 'opens' => '08:00', 'closes' => '22:00']);
        $this->post('/resources/'.$r2.'/book', $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('gm_bookings', 3);
    }

    public function test_automatic_approval_and_slot_price(): void
    {
        DB::table('gm_organizations')->where('id', $this->org)->update(['auto_approve' => true]);
        DB::table('gm_resources')->where('id', $this->resource)->update(['pricing_model' => 'per_slot', 'slot_minutes' => 30, 'rate_minor' => 45000]);
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload('10:00', 45))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$this->resource.'/book', $this->payload('10:00', 60))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gm_bookings', ['status' => 'confirmed', 'price_minor' => 90000, 'slot_minutes' => 30]);
    }

    public function test_private_booking_ownership_and_manager_scope(): void
    {
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload());
        $id = DB::table('gm_bookings')->value('id');
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('bookings', 0));
        $this->post('/bookings/'.$id.'/decision', ['action' => 'cancel'])->assertForbidden();
        $this->get('/manage')->assertForbidden();
        $this->actingAs($this->manager)->post('/bookings/'.$id.'/decision', ['action' => 'confirm'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gm_bookings', ['id' => $id, 'status' => 'confirmed']);
    }

    public function test_cancellation_releases_time_and_duplicate_submission_is_idempotent(): void
    {
        $payload = $this->payload();
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $payload)->assertSessionHasNoErrors();
        $this->post('/resources/'.$this->resource.'/book', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('gm_bookings', 1);
        $id = DB::table('gm_bookings')->value('id');
        $this->post('/bookings/'.$id.'/decision', ['action' => 'cancel'])->assertSessionHasNoErrors();
        $this->post('/resources/'.$this->resource.'/book', $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('gm_bookings', 2);
    }

    public function test_public_calendar_contains_only_time_ranges(): void
    {
        $payload = $this->payload();
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $payload);
        $this->app['auth']->forgetGuards();
        $this->get('/facilities/'.$this->facility.'?date='.$payload['date'])->assertInertia(fn (Assert $p) => $p->has('occupied', 1)->has('occupied.0', 3)->missing('occupied.0.message')->missing('occupied.0.user_id')->missing('occupied.0.status'));
    }

    public function test_unpublished_and_other_customer_resources_cannot_be_read_or_booked(): void
    {
        DB::table('gm_facilities')->where('id', $this->facility)->update(['status' => 'submitted']);
        $this->get('/facilities/'.$this->facility)->assertNotFound();
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload())->assertNotFound();
        DB::table('gm_facilities')->where('id', $this->facility)->update(['status' => 'published']);
        $customer = DB::table('gm_customers')->insertGetId(['slug' => 'other', 'name' => 'Other tenant']);
        DB::table('gm_organizations')->where('id', $this->org)->update(['customer_id' => $customer]);
        $this->get('/facilities/'.$this->facility)->assertNotFound();
        $this->post('/resources/'.$this->resource.'/book', $this->payload())->assertNotFound();
    }

    public function test_browsing_switch_and_admin_authorization(): void
    {
        $this->actingAs($this->visitor)->put('/browsing', ['public_browsing' => false])->assertForbidden();
        $admin = User::factory()->create();
        $admin->gm_admin = true;
        $admin->save();
        $this->actingAs($admin)->put('/browsing', ['public_browsing' => false])->assertSessionHasNoErrors();
        $this->app['auth']->forgetGuards();
        $this->get('/facilities/'.$this->facility)->assertForbidden();
        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('restricted', true)->has('facilities', 0));
    }

    public function test_manager_blocks_conflict_and_schedule_changes_preserve_bookings(): void
    {
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload());
        $block = $this->payload('10:30');
        $block['kind'] = 'training';
        $this->actingAs($this->manager)->post('/resources/'.$this->resource.'/block', $block)->assertSessionHasErrors('reservation');
        $block['time'] = '15:00';
        $this->post('/resources/'.$this->resource.'/block', $block)->assertSessionHasNoErrors();
        $this->put('/resources/'.$this->resource, ['name' => 'Half A', 'pricing_model' => 'hourly', 'rate_czk' => 300, 'slot_minutes' => 30, 'opens' => '12:00', 'closes' => '22:00'])->assertSessionHasErrors('opens');
        $this->assertDatabaseHas('gm_resources', ['id' => $this->resource, 'opens' => '08:00']);
    }

    public function test_database_itself_rejects_overlaps(): void
    {
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload());
        $b = DB::table('gm_bookings')->first();
        $this->expectException(QueryException::class);
        DB::table('gm_bookings')->insert(['resource_id' => $this->resource, 'user_id' => $this->visitor->id, 'starts_at' => $b->starts_at, 'ends_at' => $b->ends_at, 'status' => 'confirmed']);
    }

    public function test_price_by_agreement_and_hourly_fraction(): void
    {
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload('10:00', 45))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gm_bookings', ['price_minor' => 21000]);
        DB::table('gm_resources')->where('id', $this->resource)->update(['pricing_model' => 'agreement', 'rate_minor' => null]);
        $this->post('/resources/'.$this->resource.'/book', $this->payload('12:00', 15))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gm_bookings', ['pricing_model' => 'agreement', 'price_minor' => null]);
    }

    public function test_fifteen_minute_steps_and_outside_hours_are_rejected(): void
    {
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload('10:10'))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$this->resource.'/book', $this->payload('07:00'))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$this->resource.'/book', $this->payload('21:30'))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$this->resource.'/book', $this->payload('10:00', 20))->assertSessionHasErrors('minutes');
    }

    public function test_organizations_configure_15_30_60_minute_slots_and_requests_can_span_multiple_slots(): void
    {
        $this->actingAs($this->manager)->put('/resources/'.$this->resource, ['name' => 'Half A', 'pricing_model' => 'per_slot', 'rate_czk' => 900, 'slot_minutes' => 60, 'opens' => '08:00', 'closes' => '22:00'])->assertSessionHasNoErrors();
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload('10:00', 300))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gm_bookings', ['slot_minutes' => 60, 'price_minor' => 450000]);
    }

    public function test_first_publication_requires_admin_and_later_manager_edits_are_visible(): void
    {
        $this->actingAs($this->manager)->post('/facilities', ['organization_id' => $this->org, 'name' => 'New fictional hall', 'address' => 'Demo', 'sport' => 'Víceúčelové', 'description' => 'Before review'])->assertSessionHasNoErrors();
        $id = DB::table('gm_facilities')->where('name', 'New fictional hall')->value('id');
        $this->get('/facilities/'.$id)->assertNotFound();
        $this->post('/facilities/'.$id.'/publish')->assertForbidden();
        $admin = User::factory()->create();
        $admin->gm_admin = true;
        $admin->save();
        $this->actingAs($admin)->post('/facilities/'.$id.'/publish')->assertSessionHasNoErrors();
        $this->actingAs($this->manager)->put('/facilities/'.$id, ['description' => 'Updated after review', 'amenities' => 'Showers', 'conditions' => 'Direct payment'])->assertSessionHasNoErrors();
        $this->get('/facilities/'.$id)->assertInertia(fn (Assert $p) => $p->where('facility.description', 'Updated after review')->where('facility.status', 'published'));
    }

    public function test_editing_a_resource_does_not_reorder_manager_or_public_resource_lists(): void
    {
        $second = DB::table('gm_resources')->insertGetId(['facility_id' => $this->facility, 'name' => 'Half B', 'pricing_model' => 'agreement', 'slot_minutes' => 60, 'opens' => '08:00', 'closes' => '22:00']);
        $this->actingAs($this->manager)->put('/resources/'.$this->resource, ['name' => 'Half A', 'pricing_model' => 'hourly', 'rate_czk' => 280, 'slot_minutes' => 60, 'opens' => '08:00', 'closes' => '22:00'])->assertSessionHasNoErrors();
        $this->get('/manage')->assertInertia(fn (Assert $p) => $p->where('resources.0.id', $this->resource)->where('resources.1.id', $second));
        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('facilities.0.resources.0.id', $this->resource)->where('facilities.0.resources.1.id', $second));
    }

    private function slotPayload(array $slots): array
    {
        return ['date' => now('Europe/Prague')->addDay()->format('Y-m-d'), 'slots' => $slots, 'slot_minutes' => 15, 'message' => 'Multiple slots', 'submission_key' => (string) Str::uuid()];
    }

    public function test_multiple_slots_group_neighbors_and_price_separated_intervals(): void
    {
        $payload = $this->slotPayload(['10:30', '10:00', '10:15', '10:45', '14:00']);
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('gm_bookings', 2);
        $this->assertDatabaseHas('gm_bookings', ['price_minor' => 28000, 'submission_id' => $payload['submission_key']]);
        $this->assertDatabaseHas('gm_bookings', ['price_minor' => 7000, 'submission_id' => $payload['submission_key']]);
        $this->post('/resources/'.$this->resource.'/book', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('gm_bookings', 2);
        $payload['slots'] = ['10:00', '10:15'];
        $this->post('/resources/'.$this->resource.'/book', $payload)->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('gm_bookings', 2);
    }

    public function test_conflicting_multi_slot_submission_rolls_back_every_new_interval(): void
    {
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload('12:00'))->assertSessionHasNoErrors();
        $payload = $this->slotPayload(['10:00', '10:15', '12:00']);
        $this->post('/resources/'.$this->resource.'/book', $payload)->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('gm_bookings', 1);
        $this->assertDatabaseCount('gm_submissions', 0);
    }

    public function test_multi_slot_submission_rejects_stale_config_off_grid_and_duplicates(): void
    {
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->slotPayload(['10:05']))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$this->resource.'/book', $this->slotPayload(['10:00', '10:00']))->assertSessionHasErrors('slots.0');
        DB::table('gm_resources')->where('id', $this->resource)->update(['slot_minutes' => 30]);
        $this->post('/resources/'.$this->resource.'/book', $this->slotPayload(['10:00']))->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('gm_bookings', 0);
    }

    public function test_field_slot_configuration_accepts_only_the_three_options(): void
    {
        $base = ['name' => 'Half A', 'pricing_model' => 'hourly', 'rate_czk' => 280, 'opens' => '08:00', 'closes' => '22:00'];
        $this->actingAs($this->manager)->put('/resources/'.$this->resource, [...$base, 'slot_minutes' => 45])->assertSessionHasErrors('slot_minutes');
        foreach ([15, 30, 60] as $minutes) {
            $this->put('/resources/'.$this->resource, [...$base, 'slot_minutes' => $minutes])->assertSessionHasNoErrors();
            $this->assertDatabaseHas('gm_resources', ['id' => $this->resource, 'slot_minutes' => $minutes]);
        }
    }

    public function test_per_slot_price_multiplies_by_selected_slots(): void
    {
        DB::table('gm_resources')->where('id', $this->resource)->update(['pricing_model' => 'per_slot', 'slot_minutes' => 30, 'rate_minor' => 15000]);
        $payload = $this->slotPayload(['10:00', '10:30', '14:00']);
        $payload['slot_minutes'] = 30;
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('gm_bookings', 2);
        $this->assertDatabaseHas('gm_bookings', ['price_minor' => 30000]);
        $this->assertDatabaseHas('gm_bookings', ['price_minor' => 15000]);
    }

    private function enableWholeField(): int
    {
        $this->actingAs($this->manager)->put('/facilities/'.$this->facility.'/whole-field', ['enabled' => true])->assertSessionHasNoErrors();

        return DB::table('gm_resources')->where('facility_id', $this->facility)->where('is_whole', true)->value('id');
    }

    private function secondPart(): int
    {
        return DB::table('gm_resources')->insertGetId(['facility_id' => $this->facility, 'name' => 'Half B', 'pricing_model' => 'agreement', 'slot_minutes' => 30, 'opens' => '09:00', 'closes' => '20:00']);
    }

    public function test_whole_field_is_opt_in_and_manager_scoped(): void
    {
        $this->actingAs($this->visitor)->put('/facilities/'.$this->facility.'/whole-field', ['enabled' => true])->assertForbidden();
        $this->get('/facilities/'.$this->facility)->assertInertia(fn (Assert $p) => $p->where('facility.whole_field_enabled', false)->has('resources', 1));
        $whole = $this->enableWholeField();
        $this->get('/facilities/'.$this->facility)->assertInertia(fn (Assert $p) => $p->where('facility.whole_field_enabled', true)->has('resources', 2));
        $this->put('/facilities/'.$this->facility.'/whole-field', ['enabled' => false])->assertSessionHasNoErrors();
        $this->get('/facilities/'.$this->facility)->assertInertia(fn (Assert $p) => $p->has('resources', 1));
        $this->actingAs($this->visitor)->post('/resources/'.$whole.'/book', $this->payload())->assertSessionHasErrors('reservation');
        $outsider = User::factory()->create();
        $this->actingAs($outsider)->put('/facilities/'.$this->facility.'/whole-field', ['enabled' => true])->assertForbidden();
        $this->assertDatabaseCount('gm_resources', 2);
    }

    public function test_whole_field_requires_every_part_free_and_blocks_all_parts(): void
    {
        $second = $this->secondPart();
        $whole = $this->enableWholeField();
        $this->actingAs($this->visitor)->post('/resources/'.$second.'/book', $this->payload())->assertSessionHasNoErrors();
        $this->post('/resources/'.$whole.'/book', $this->payload('10:15', 30))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$whole.'/book', $this->payload('11:00'))->assertSessionHasNoErrors();
        $this->post('/resources/'.$this->resource.'/book', $this->payload('11:15', 15))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$second.'/book', $this->payload('11:30', 30))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$second.'/book', $this->payload('12:00', 30))->assertSessionHasNoErrors();
        $this->get('/facilities/'.$this->facility.'?date='.$this->payload()['date'])->assertInertia(fn (Assert $p) => $p->has('occupied', 5)->has('occupied.0', 3));
        $booking = DB::table('gm_bookings')->where('resource_id', $whole)->value('id');
        $this->actingAs($this->manager)->post('/bookings/'.$booking.'/decision', ['action' => 'confirm'])->assertSessionHasNoErrors();
        $this->put('/resources/'.$second, ['name' => 'Half B', 'pricing_model' => 'agreement', 'slot_minutes' => 30, 'opens' => '12:00', 'closes' => '20:00'])->assertSessionHasErrors('opens');
        $this->put('/facilities/'.$this->facility.'/whole-field', ['enabled' => false])->assertSessionHasNoErrors();
        $this->actingAs($this->visitor)->post('/resources/'.$second.'/book', $this->payload('11:00', 30))->assertSessionHasErrors('reservation');
        $this->post('/bookings/'.$booking.'/decision', ['action' => 'cancel'])->assertSessionHasNoErrors();
        $this->post('/resources/'.$second.'/book', $this->payload('11:00', 30))->assertSessionHasNoErrors();
    }

    public function test_whole_field_slot_batch_is_atomic_and_has_its_own_price(): void
    {
        $second = $this->secondPart();
        $whole = $this->enableWholeField();
        $this->put('/resources/'.$whole, ['name' => 'Celé hřiště', 'pricing_model' => 'per_slot', 'rate_czk' => 600, 'slot_minutes' => 30, 'opens' => '09:00', 'closes' => '20:00'])->assertSessionHasNoErrors();
        $this->actingAs($this->visitor)->post('/resources/'.$second.'/book', $this->payload('14:00', 30))->assertSessionHasNoErrors();
        $payload = [...$this->slotPayload(['10:00', '10:30', '14:00']), 'slot_minutes' => 30];
        $this->post('/resources/'.$whole.'/book', $payload)->assertSessionHasErrors('reservation');
        $this->assertDatabaseCount('gm_bookings', 1);
        $this->assertDatabaseCount('gm_submissions', 0);
        $payload['slots'] = ['10:00', '10:30'];
        $this->post('/resources/'.$whole.'/book', $payload)->assertSessionHasNoErrors();
        $this->post('/resources/'.$whole.'/book', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('gm_bookings', 2);
        $this->assertDatabaseHas('gm_bookings', ['resource_id' => $whole, 'price_minor' => 120000]);
        $this->assertDatabaseCount('gm_booking_parts', 4);
    }

    public function test_whole_field_checks_all_part_hours_and_protects_part_addition(): void
    {
        $second = $this->secondPart();
        $whole = $this->enableWholeField();
        DB::table('gm_resources')->where('id', $second)->update(['opens' => '10:00']);
        $this->actingAs($this->visitor)->post('/resources/'.$whole.'/book', $this->payload('09:00', 30))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$whole.'/book', $this->payload('11:00'))->assertSessionHasNoErrors();
        $this->actingAs($this->manager)->post('/facilities/'.$this->facility.'/resources', ['name' => 'Half C'])->assertSessionHasErrors('name');
        $booking = DB::table('gm_bookings')->where('resource_id', $whole)->value('id');
        $this->post('/bookings/'.$booking.'/decision', ['action' => 'decline'])->assertSessionHasNoErrors();
        $this->post('/facilities/'.$this->facility.'/resources', ['name' => 'Half C'])->assertSessionHasNoErrors();
        $this->actingAs($this->visitor)->post('/resources/'.$second.'/book', $this->payload('11:00', 30))->assertSessionHasNoErrors();
    }

    public function test_database_rejects_whole_field_overlap_without_service(): void
    {
        $this->secondPart();
        $whole = $this->enableWholeField();
        $this->actingAs($this->visitor)->post('/resources/'.$whole.'/book', $this->payload())->assertSessionHasNoErrors();
        $booking = DB::table('gm_bookings')->first();
        $this->expectException(QueryException::class);
        DB::table('gm_bookings')->insert(['resource_id' => $this->resource, 'user_id' => $this->visitor->id, 'starts_at' => $booking->starts_at, 'ends_at' => $booking->ends_at, 'status' => 'blocked']);
    }

    public function test_manager_blocks_share_whole_field_occupancy(): void
    {
        $second = $this->secondPart();
        $whole = $this->enableWholeField();
        $this->post('/resources/'.$second.'/block', [...$this->payload('14:00', 30), 'kind' => 'training'])->assertSessionHasNoErrors();
        $this->actingAs($this->visitor)->post('/resources/'.$whole.'/book', $this->payload('14:00', 30))->assertSessionHasErrors('reservation');
        $this->actingAs($this->manager)->post('/resources/'.$whole.'/block', [...$this->payload('15:00', 30), 'kind' => 'closure'])->assertSessionHasNoErrors();
        $this->actingAs($this->visitor)->post('/resources/'.$second.'/book', $this->payload('15:00', 30))->assertSessionHasErrors('reservation');
        $booking = DB::table('gm_bookings')->where('resource_id', $whole)->value('id');
        $this->actingAs($this->manager)->post('/bookings/'.$booking.'/decision', ['action' => 'cancel'])->assertSessionHasNoErrors();
        $this->actingAs($this->visitor)->post('/resources/'.$second.'/book', $this->payload('15:00', 30))->assertSessionHasNoErrors();
    }
}
