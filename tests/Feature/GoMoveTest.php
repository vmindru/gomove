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
        $this->resource = DB::table('gm_resources')->insertGetId(['facility_id' => $this->facility, 'name' => 'Half A', 'pricing_model' => 'hourly', 'rate_minor' => 28000, 'slot_minutes' => 60, 'opens' => '08:00', 'closes' => '22:00']);
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
        DB::table('gm_resources')->where('id', $this->resource)->update(['pricing_model' => 'per_slot', 'slot_minutes' => 75, 'rate_minor' => 45000]);
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload('10:00', 60))->assertSessionHasErrors('reservation');
        $this->post('/resources/'.$this->resource.'/book', $this->payload('10:00', 75))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gm_bookings', ['status' => 'confirmed', 'price_minor' => 45000, 'slot_minutes' => 75]);
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
        $this->put('/resources/'.$this->resource, ['name' => 'Half A', 'pricing_model' => 'hourly', 'rate_czk' => 300, 'slot_minutes' => 45, 'opens' => '12:00', 'closes' => '22:00'])->assertSessionHasErrors('opens');
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

    public function test_organizations_can_define_longer_slots_in_fifteen_minute_steps(): void
    {
        $this->actingAs($this->manager)->put('/resources/'.$this->resource, ['name' => 'Half A', 'pricing_model' => 'per_slot', 'rate_czk' => 900, 'slot_minutes' => 300, 'opens' => '08:00', 'closes' => '22:00'])->assertSessionHasNoErrors();
        $this->actingAs($this->visitor)->post('/resources/'.$this->resource.'/book', $this->payload('10:00', 300))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('gm_bookings', ['slot_minutes' => 300, 'price_minor' => 90000]);
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
}
