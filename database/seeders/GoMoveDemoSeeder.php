<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GoMoveDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || ! config('gomove.demo')) {
            throw new \RuntimeException('Demo seeding requires local/testing and GOMOVE_DEMO=true.');
        }
        $password = env('GOMOVE_DEMO_PASSWORD');
        if (! $password || strlen($password) < 12) {
            throw new \RuntimeException('Set a demo password of at least 12 characters.');
        }
        DB::transaction(function () use ($password) {
            $customer = DB::table('gm_customers')->where('slug', 'demo')->value('id');
            if (! $customer) {
                $customer = DB::table('gm_customers')->insertGetId(['slug' => 'demo', 'name' => 'Ukázkové město', 'public_browsing' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
            $users = [];
            foreach (['visitor' => 'Petra Nová — návštěvník', 'manager' => 'Martin Veselý — správce', 'admin' => 'Dana Malá — administrátor'] as $role => $name) {
                $u = User::firstOrCreate(['email' => $role.'@demo.gomove.test'], ['name' => $name, 'password' => Hash::make($password)]);
                if ($role === 'admin') {
                    $u->gm_admin = true;
                    $u->save();
                }
                $users[$role] = $u->id;
            }
            $org = DB::table('gm_organizations')->where('customer_id', $customer)->where('name', 'Sportovní klub Horizont (demo)')->value('id');
            if (! $org) {
                $org = DB::table('gm_organizations')->insertGetId(['customer_id' => $customer, 'name' => 'Sportovní klub Horizont (demo)', 'email' => 'klub@demo.gomove.test', 'auto_approve' => false, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('gm_memberships')->updateOrInsert(['organization_id' => $org, 'user_id' => $users['manager']]);
            foreach ([
                ['Tenisové kurty Na Výsluní', 'Tenis', 'court', 'Sportovní 12, Ukázkové město', 'Dva samostatné kurty, čerstvý vzduch a čas jen pro vaši hru. Přijďte si zahrát s přáteli nebo potrénovat podání.', 'Šatny · Sprchy · Parkování · Venkovní', 'hourly', 28000, 60, ['Kurt 1', 'Kurt 2']],
                ['Hřiště U Parku', 'Fotbal', 'field', 'Parková 8, Ukázkové město', 'Prostor pro týmovou hru i pohodový zápas. Každou polovinu hřiště lze rezervovat samostatně.', 'Umělý povrch · Osvětlení · Šatny', 'per_slot', 45000, 90, ['Polovina A', 'Polovina B']],
                ['Sportovní hala Horizont', 'Víceúčelové', 'hall', 'Javorová 3, Ukázkové město', 'Pod jednou střechou se vejde badminton, volejbal i vaše další sportovní setkání. Konkrétní podmínky domluvíte přímo s klubem.', 'Krytá hala · Bezbariérový vstup · Sprchy', 'agreement', null, 60, ['Hlavní sál']],
            ] as [$name, $sport, $style, $address, $description, $amenities, $model, $rate, $slot, $parts]) {
                if (DB::table('gm_facilities')->where('organization_id', $org)->where('name', $name)->exists()) {
                    continue;
                }
                $f = DB::table('gm_facilities')->insertGetId(['organization_id' => $org, 'name' => $name, 'sport' => $sport, 'style' => $style, 'address' => $address, 'description' => $description, 'amenities' => $amenities, 'conditions' => 'Vlastní sportovní vybavení. Platba přímo organizaci. Jde o smyšlené demo sportoviště.', 'status' => 'published', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                foreach ($parts as $part) {
                    DB::table('gm_resources')->insert(['facility_id' => $f, 'name' => $part, 'pricing_model' => $model, 'rate_minor' => $rate, 'slot_minutes' => $slot, 'opens' => '08:00', 'closes' => '22:00', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });
    }
}
