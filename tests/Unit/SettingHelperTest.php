<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('gescolab_settings');
    }

    public function test_returns_value_from_database(): void
    {
        Cache::put('gescolab_settings', ['company_name' => 'TestCorp'], 3600);

        $this->assertSame('TestCorp', setting('company_name'));
    }

    public function test_database_value_takes_priority_over_config(): void
    {
        Cache::put('gescolab_settings', ['company_name' => 'DB Override'], 3600);

        $this->assertSame('DB Override', setting('company_name'));
    }

    public function test_falls_back_to_config_when_key_not_in_db(): void
    {
        Cache::put('gescolab_settings', [], 3600);

        // currency is defined in config/gescolab.php as 'FCFA'
        $this->assertSame('FCFA', setting('currency'));
    }

    public function test_falls_back_to_default_when_key_missing_everywhere(): void
    {
        Cache::put('gescolab_settings', [], 3600);

        $this->assertSame('fallback_value', setting('non_existent_key', 'fallback_value'));
    }

    public function test_returns_null_default_when_key_missing_and_no_default_given(): void
    {
        Cache::put('gescolab_settings', [], 3600);

        $this->assertNull(setting('non_existent_key'));
    }

    public function test_reads_from_db_and_populates_cache_when_cache_is_empty(): void
    {
        $dbResult = collect(['payroll_day' => '28']);

        $queryMock = \Mockery::mock();
        $queryMock->shouldReceive('pluck')->with('value', 'key')->andReturn($dbResult);

        DB::shouldReceive('table')->once()->with('settings')->andReturn($queryMock);

        $this->assertSame('28', setting('payroll_day'));

        // The result must now be in cache so a second call skips the DB
        $this->assertSame('28', setting('payroll_day'));
    }

    public function test_result_is_cached_and_db_not_queried_twice(): void
    {
        Cache::put('gescolab_settings', ['transport_allowance' => '35000'], 3600);

        DB::shouldReceive('table')->never();

        $this->assertSame('35000', setting('transport_allowance'));
        $this->assertSame('35000', setting('transport_allowance'));
    }

    public function test_config_numeric_defaults_are_returned_correctly(): void
    {
        Cache::put('gescolab_settings', [], 3600);

        // env() may coerce values to strings; assertEquals handles int/string equivalence
        $this->assertEquals(30, setting('annual_leave_days'));
        $this->assertEquals(6.3, setting('cnps_employee_rate'));
    }
}
