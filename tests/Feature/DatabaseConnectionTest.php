<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tests_run_on_mysql_with_the_schema_migrated(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('families'));
    }

    /**
     * RefreshDatabase xoá sạch database trước mỗi test. phpunit.xml từng trỏ vào
     * family_muakey_dev nên chạy test là mất luôn dữ liệu đang làm việc.
     */
    public function test_tests_never_run_against_the_development_database(): void
    {
        $database = DB::connection()->getDatabaseName();

        $this->assertNotSame('family_muakey_dev', $database);
        $this->assertNotSame('family_muakey', $database);
        $this->assertStringEndsWith('_test', $database);
    }
}
