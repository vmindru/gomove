<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        if (config('database.default') !== 'pgsql' || ! str_starts_with((string) config('database.connections.pgsql.database'), 'gomove_test_')) {
            throw new \RuntimeException('Tests require an isolated PostgreSQL database named gomove_test_*. Use scripts/test.sh.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (Schema::hasTable('gm_customers')) {
            DB::table('gm_customers')->updateOrInsert(['slug' => 'demo'], ['name' => 'Fictional test customer', 'public_browsing' => true]);
        }
    }
}
