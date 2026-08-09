<?php

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\ParallelTesting;
use RuntimeException;

trait SerializesTestingDatabase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshDatabaseUsingLaravel;
    }

    public function refreshDatabase(): void
    {
        $this->assertSafeTestingDatabase();
        $this->lockSharedTestingDatabaseForNonParallelRuns();
        $this->refreshDatabaseUsingLaravel();
    }

    private function assertSafeTestingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($database === ':memory:') {
            return;
        }

        if (app()->environment('testing') !== true) {
            throw new RuntimeException('Refusing to run database-refreshing tests outside APP_ENV=testing.');
        }

        if (! is_string($database) || ! preg_match('/(^|_)test(ing)?(_|$)/i', $database)) {
            throw new RuntimeException(
                "Refusing to run database-refreshing tests against non-testing database [{$database}]."
            );
        }
    }

    private function lockSharedTestingDatabaseForNonParallelRuns(): void
    {
        if (ParallelTesting::token() !== false) {
            return;
        }

        $connection = config('database.default');

        if (config("database.connections.{$connection}.driver") !== 'pgsql') {
            return;
        }

        $config = config("database.connections.{$connection}");
        $database = $config['database'];

        if ($database === ':memory:') {
            return;
        }

        TestingDatabaseLock::acquire($config, sprintf('%s:%s:%s', base_path(), $connection, $database));
    }
}
