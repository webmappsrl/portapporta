<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        $db = config("database.connections.{$connection}.database");
        if ($db !== 'pap_test') {
            throw new \RuntimeException(
                "Test aborted: connected to database '{$db}', expected 'pap_test'.\n" .
                "Run these commands to set up the test database:\n" .
                "  docker exec postgres_portapporta createdb -U root pap_test\n" .
                "  docker exec postgres_portapporta psql -U root -d pap_test -c \"CREATE EXTENSION IF NOT EXISTS postgis;\"\n" .
                "  docker exec php_portapporta php artisan config:clear\n" .
                "  docker exec php_portapporta php artisan migrate --env=testing"
            );
        }
    }
}
