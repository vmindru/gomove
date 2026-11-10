<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve the effective rate of older per-slot demo settings when subdividing them.
        foreach (DB::table('gm_resources')->whereNotIn('slot_minutes', [15, 30, 60])->get() as $resource) {
            $minutes = $resource->slot_minutes % 60 === 0 ? 60 : ($resource->slot_minutes % 30 === 0 ? 30 : 15);
            $rate = $resource->pricing_model === 'per_slot' && $resource->rate_minor !== null
                ? (int) ceil($resource->rate_minor * $minutes / $resource->slot_minutes) : $resource->rate_minor;
            DB::table('gm_resources')->where('id', $resource->id)->update(['slot_minutes' => $minutes, 'rate_minor' => $rate]);
        }
        Schema::create('gm_submissions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('resource_id')->constrained('gm_resources');
            $t->string('payload_hash', 64);
            $t->timestamp('created_at');
        });
        Schema::table('gm_bookings', function (Blueprint $t) {
            $t->uuid('submission_id')->nullable();
            $t->foreign('submission_id')->references('id')->on('gm_submissions');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE gm_resources ADD CONSTRAINT gm_slot_options CHECK (slot_minutes IN (15, 30, 60))');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE gm_resources DROP CONSTRAINT IF EXISTS gm_slot_options');
        }
        Schema::table('gm_bookings', function (Blueprint $t) {
            $t->dropForeign(['submission_id']);
            $t->dropColumn('submission_id');
        });
        Schema::dropIfExists('gm_submissions');
    }
};
