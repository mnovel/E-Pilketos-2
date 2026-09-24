<?php

namespace Tests\Unit\Admin;

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class DashboardDriverTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================
    // HELPERS
    // =========================================================

    protected function getHourExpression(string $driver): string
    {
        $controller = new DashboardController();
        $method = new ReflectionMethod($controller, 'getHourExpression');
        $method->setAccessible(true);

        return $method->invoke($controller, $driver);
    }

    // =========================================================
    // SQLITE
    // =========================================================

    public function test_sqlite_uses_strftime()
    {
        $expr = $this->getHourExpression('sqlite');

        $this->assertSame("strftime('%H', created_at)", $expr);
    }

    // =========================================================
    // MYSQL / MARIADB
    // =========================================================

    public function test_mysql_uses_hour()
    {
        $expr = $this->getHourExpression('mysql');

        $this->assertSame('HOUR(created_at)', $expr);
    }

    public function test_mariadb_uses_hour()
    {
        $expr = $this->getHourExpression('mariadb');

        $this->assertSame('HOUR(created_at)', $expr);
    }

    // =========================================================
    // POSTGRESQL
    // =========================================================

    public function test_pgsql_uses_extract()
    {
        $expr = $this->getHourExpression('pgsql');

        $this->assertStringContainsString('EXTRACT', $expr);
        $this->assertStringContainsString('HOUR', $expr);
        $this->assertStringContainsString('created_at', $expr);
    }

    // =========================================================
    // SQL SERVER
    // =========================================================

    public function test_sqlsrv_uses_datepart()
    {
        $expr = $this->getHourExpression('sqlsrv');

        $this->assertStringContainsString('DATEPART', $expr);
        $this->assertStringContainsString('HOUR', $expr);
        $this->assertStringContainsString('created_at', $expr);
    }

    // =========================================================
    // UNKNOWN DRIVER — FALLBACK
    // =========================================================

    public function test_unknown_driver_falls_back_to_mysql()
    {
        $expr = $this->getHourExpression('oracle');

        $this->assertSame('HOUR(created_at)', $expr);
    }

    public function test_empty_driver_falls_back_to_mysql()
    {
        $expr = $this->getHourExpression('');

        $this->assertSame('HOUR(created_at)', $expr);
    }

    // =========================================================
    // VALIDATION
    // =========================================================

    public function test_all_known_drivers_reference_created_at()
    {
        $drivers = ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'];

        foreach ($drivers as $driver) {
            $expr = $this->getHourExpression($driver);

            $this->assertStringContainsString(
                'created_at',
                $expr,
                "Driver [{$driver}] expression harus reference created_at"
            );
        }
    }

    public function test_all_known_drivers_return_non_empty_string()
    {
        $drivers = ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv', 'oracle', ''];

        foreach ($drivers as $driver) {
            $expr = $this->getHourExpression($driver);

            $this->assertNotEmpty($expr, "Driver [{$driver}] harus return non-empty string");
        }
    }
}
