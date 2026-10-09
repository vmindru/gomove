<?php

use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// An integration rehearsal in the isolated test database; never the development database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'pgsql' || config('database.connections.pgsql.database') !== 'gomove_test_mvp') {
    throw new RuntimeException('Concurrency rehearsal only runs in gomove_test_mvp.');
}
$db = DB::class;
$mode = $argv[1] ?? '';
$fixture = '/tmp/gomove-mvp-race.json';
if ($mode === 'setup') {
    $customer = $db::table('gm_customers')->insertGetId(['name' => 'Concurrency test', 'slug' => 'race-'.Str::uuid()]);
    $user = User::factory()->create();
    $org = $db::table('gm_organizations')->insertGetId(['customer_id' => $customer, 'name' => 'Concurrency test', 'email' => 'race@example.test']);
    $facility = $db::table('gm_facilities')->insertGetId(['organization_id' => $org, 'name' => 'Concurrency test', 'description' => 'Test', 'address' => 'Test', 'sport' => 'Tenis', 'status' => 'published']);
    $resource = $db::table('gm_resources')->insertGetId(['facility_id' => $facility, 'name' => 'Race court', 'pricing_model' => 'agreement', 'opens' => '08:00', 'closes' => '22:00']);
    file_put_contents($fixture, json_encode(['resource' => $resource, 'user' => $user->id, 'barrier' => microtime(true) + 2]));
    exit;
}
$data = json_decode(file_get_contents($fixture), true, flags: JSON_THROW_ON_ERROR);
if ($mode === 'attempt') {
    while (microtime(true) < $data['barrier']) {
        usleep(1000);
    }
    $resource = $db::table('gm_resources')->where('id', $data['resource'])->first();
    try {
        $app->make(ReservationService::class)->reserve($resource, ['date' => now('Europe/Prague')->addDay()->format('Y-m-d'), 'time' => '10:00', 'minutes' => 60, 'submission_key' => (string) Str::uuid()], $data['user']);
        echo "reserved\n";
    } catch (ValidationException $e) {
        echo "conflict\n";
    }
    exit;
}
if ($mode === 'verify') {
    $count = $db::table('gm_bookings')->where('resource_id', $data['resource'])->whereIn('status', ['pending', 'confirmed'])->count();
    $results = [trim(file_get_contents('/tmp/gomove-mvp-race-a.log')), trim(file_get_contents('/tmp/gomove-mvp-race-b.log'))];
    sort($results);
    if ($count !== 1 || $results !== ['conflict', 'reserved']) {
        throw new RuntimeException('Concurrent booking invariant failed: '.json_encode($results));
    }
    echo "Concurrent independent processes: exactly one reservation, one rejected overlap.\n";
    exit;
}
throw new RuntimeException('Unknown rehearsal mode.');
