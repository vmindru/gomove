<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('gm_admin')->default(false));
        Schema::create('gm_customers', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->boolean('public_browsing')->default(true);
            $t->timestamps();
        });
        Schema::create('gm_organizations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_id')->constrained('gm_customers');
            $t->string('name');
            $t->string('email');
            $t->boolean('auto_approve')->default(false);
            $t->timestamps();
        });
        Schema::create('gm_memberships', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('gm_organizations');
            $t->foreignId('user_id')->constrained();
            $t->unique(['organization_id', 'user_id']);
        });
        Schema::create('gm_facilities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organization_id')->constrained('gm_organizations');
            $t->string('name');
            $t->string('address');
            $t->text('description');
            $t->string('sport');
            $t->string('style')->default('court');
            $t->text('amenities')->nullable();
            $t->text('conditions')->nullable();
            $t->string('status')->default('draft');
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
        });
        Schema::create('gm_resources', function (Blueprint $t) {
            $t->id();
            $t->foreignId('facility_id')->constrained('gm_facilities');
            $t->string('name');
            $t->string('pricing_model')->default('hourly');
            $t->unsignedInteger('rate_minor')->nullable();
            $t->unsignedInteger('slot_minutes')->default(60);
            $t->string('opens', 5)->default('08:00');
            $t->string('closes', 5)->default('22:00');
            $t->unique(['facility_id', 'name']);
            $t->timestamps();
        });
        Schema::create('gm_bookings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('resource_id')->constrained('gm_resources');
            $t->foreignId('user_id')->nullable()->constrained();
            $t->timestampTz('starts_at');
            $t->timestampTz('ends_at');
            $t->string('status');
            $t->string('kind')->default('booking');
            $t->text('message')->nullable();
            $t->string('pricing_model')->nullable();
            $t->unsignedInteger('price_minor')->nullable();
            $t->unsignedInteger('rate_minor')->nullable();
            $t->unsignedInteger('slot_minutes')->nullable();
            $t->uuid('submission_key')->nullable()->unique();
            $t->timestamps();
            $t->index(['resource_id', 'starts_at']);
            $t->index(['user_id', 'status']);
        });
        Schema::create('gm_audit', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action');
            $t->unsignedBigInteger('entity_id');
            $t->timestamp('created_at');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
            DB::statement('ALTER TABLE gm_bookings ADD CONSTRAINT gm_booking_positive CHECK (ends_at > starts_at)');
            DB::statement('ALTER TABLE gm_resources ADD CONSTRAINT gm_slot_increment CHECK (slot_minutes > 0 AND slot_minutes % 15 = 0)');
            DB::statement("ALTER TABLE gm_bookings ADD CONSTRAINT gm_no_overlap EXCLUDE USING gist (resource_id WITH =, tstzrange(starts_at, ends_at, '[)') WITH &&) WHERE (status IN ('pending', 'confirmed', 'blocked'))");
        }
    }

    public function down(): void
    {
        foreach (['gm_audit', 'gm_bookings', 'gm_resources', 'gm_facilities', 'gm_memberships', 'gm_organizations', 'gm_customers'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('gm_admin'));
    }
};
