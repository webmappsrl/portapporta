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
                "See docs/howto/test-locale.md. Container names come from APP_NAME, DB user from .env:\n" .
                "  docker exec postgres_PortAPPorta createdb -U <DB_USERNAME> pap_test\n" .
                "  docker exec postgres_PortAPPorta psql -U <DB_USERNAME> -d pap_test -c \"CREATE EXTENSION IF NOT EXISTS postgis;\"\n" .
                "  docker exec php_PortAPPorta php artisan optimize:clear\n" .
                "  docker exec php_PortAPPorta php artisan migrate --env=testing"
            );
        }
    }
}
