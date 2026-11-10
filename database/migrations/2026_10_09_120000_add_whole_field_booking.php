<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gm_facilities', fn (Blueprint $t) => $t->boolean('whole_field_enabled')->default(false));
        Schema::table('gm_resources', fn (Blueprint $t) => $t->boolean('is_whole')->default(false));
        Schema::create('gm_booking_parts', function (Blueprint $t) {
            $t->foreignId('booking_id')->constrained('gm_bookings')->cascadeOnDelete();
            $t->foreignId('resource_id')->constrained('gm_resources');
            $t->timestampTz('starts_at');
            $t->timestampTz('ends_at');
            $t->string('status');
            $t->primary(['booking_id', 'resource_id']);
        });
        DB::statement('INSERT INTO gm_booking_parts SELECT id, resource_id, starts_at, ends_at, status FROM gm_bookings');
        DB::statement('CREATE UNIQUE INDEX gm_one_whole_field ON gm_resources (facility_id) WHERE is_whole');
        DB::statement("ALTER TABLE gm_booking_parts ADD CONSTRAINT gm_parts_no_overlap EXCLUDE USING gist (resource_id WITH =, tstzrange(starts_at, ends_at, '[)') WITH &&) WHERE (status IN ('pending', 'confirmed', 'blocked'))");
        // Every database writer gets the same overlap protection and release-on-cancellation behavior.
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION gm_sync_booking_parts() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    DELETE FROM gm_booking_parts WHERE booking_id = NEW.id;
    INSERT INTO gm_booking_parts (booking_id, resource_id, starts_at, ends_at, status)
    SELECT NEW.id, part.id, NEW.starts_at, NEW.ends_at, NEW.status
    FROM gm_resources selected JOIN gm_resources part ON part.facility_id = selected.facility_id
    WHERE selected.id = NEW.resource_id AND
          (part.id = selected.id OR (selected.is_whole AND NOT part.is_whole));
    RETURN NEW;
END;
$$;
CREATE TRIGGER gm_booking_parts_sync AFTER INSERT OR UPDATE OF resource_id, starts_at, ends_at, status
ON gm_bookings FOR EACH ROW EXECUTE FUNCTION gm_sync_booking_parts();
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER gm_booking_parts_sync ON gm_bookings');
        DB::statement('DROP FUNCTION gm_sync_booking_parts()');
        DB::statement('DROP INDEX gm_one_whole_field');
        Schema::dropIfExists('gm_booking_parts');
        Schema::table('gm_resources', fn (Blueprint $t) => $t->dropColumn('is_whole'));
        Schema::table('gm_facilities', fn (Blueprint $t) => $t->dropColumn('whole_field_enabled'));
    }
};
