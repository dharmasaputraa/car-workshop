<?php

namespace Tests\Unit\Services;

use App\DTOs\Health\HealthData;
use App\Services\HealthCheckService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthCheckServiceTest extends TestCase
{
    protected HealthCheckService $healthService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->healthService = new HealthCheckService();
    }

    /*
    |--------------------------------------------------------------------------
    | BASIC HEALTH CHECK
    |--------------------------------------------------------------------------
    */

    public function test_basic_returns_healthy_status(): void
    {
        $result = $this->healthService->basic();

        $this->assertInstanceOf(HealthData::class, $result);
        $this->assertTrue($result->healthy);
        $this->assertEquals('OK', $result->message);
        $this->assertEquals(200, $result->statusCode);
    }

    public function test_basic_includes_required_details(): void
    {
        $result = $this->healthService->basic();

        $this->assertArrayHasKey('app', $result->details);
        $this->assertArrayHasKey('env', $result->details);
        $this->assertArrayHasKey('response_time_ms', $result->details);
        $this->assertIsString($result->details['app']);
        $this->assertIsString($result->details['env']);
        $this->assertIsFloat($result->details['response_time_ms']);
    }

    /*
    |--------------------------------------------------------------------------
    | FULL HEALTH CHECK
    |--------------------------------------------------------------------------
    */

    public function test_full_returns_healthy_when_all_services_up(): void
    {
        // Mock successful service checks
        DB::shouldReceive('select')->with('select 1')->andReturn([]);
        Redis::shouldReceive('connection->ping')->andReturn('PONG');
        Storage::shouldReceive('disk->put')->andReturn(true);
        Storage::shouldReceive('disk->delete')->andReturn(true);

        $result = $this->healthService->full();

        $this->assertInstanceOf(HealthData::class, $result);
        $this->assertTrue($result->healthy);
        $this->assertEquals('System is operational', $result->message);
        $this->assertEquals(200, $result->statusCode);
    }

    public function test_full_includes_all_service_checks_in_details(): void
    {
        DB::shouldReceive('select')->with('select 1')->andReturn([]);
        Redis::shouldReceive('connection->ping')->andReturn('PONG');
        Storage::shouldReceive('disk->put')->andReturn(true);
        Storage::shouldReceive('disk->delete')->andReturn(true);

        $result = $this->healthService->full();

        $this->assertArrayHasKey('app', $result->details);
        $this->assertArrayHasKey('env', $result->details);
        $this->assertArrayHasKey('app_version', $result->details);
        $this->assertArrayHasKey('services', $result->details);
        $this->assertArrayHasKey('response_time_ms', $result->details);

        $services = $result->details['services'];
        $this->assertArrayHasKey('database', $services);
        $this->assertArrayHasKey('redis', $services);
        $this->assertArrayHasKey('object_storage', $services);

        $this->assertTrue($services['database']);
        $this->assertTrue($services['redis']);
        $this->assertTrue($services['object_storage']);
    }

    public function test_full_returns_unhealthy_when_database_down(): void
    {
        // Mock database failure
        DB::shouldReceive('select')->with('select 1')->andThrow(new \Exception('Database connection failed'));
        Log::shouldReceive('error')->once();

        // Other services up
        Redis::shouldReceive('connection->ping')->andReturn('PONG');
        Storage::shouldReceive('disk->put')->andReturn(true);
        Storage::shouldReceive('disk->delete')->andReturn(true);

        $result = $this->healthService->full();

        $this->assertInstanceOf(HealthData::class, $result);
        $this->assertFalse($result->healthy);
        $this->assertEquals('System degradation detected', $result->message);
        $this->assertEquals(503, $result->statusCode);

        $this->assertFalse($result->details['services']['database']);
    }

    public function test_full_returns_unhealthy_when_redis_down(): void
    {
        // Mock redis failure
        Redis::shouldReceive('connection->ping')->andThrow(new \Exception('Redis connection failed'));
        Log::shouldReceive('error')->once();

        // Other services up
        DB::shouldReceive('select')->with('select 1')->andReturn([]);
        Storage::shouldReceive('disk->put')->andReturn(true);
        Storage::shouldReceive('disk->delete')->andReturn(true);

        $result = $this->healthService->full();

        $this->assertInstanceOf(HealthData::class, $result);
        $this->assertFalse($result->healthy);
        $this->assertEquals('System degradation detected', $result->message);
        $this->assertEquals(503, $result->statusCode);

        $this->assertFalse($result->details['services']['redis']);
    }

    public function test_full_returns_unhealthy_when_storage_down(): void
    {
        // Mock storage failure
        Storage::shouldReceive('disk->put')->andThrow(new \Exception('S3 connection failed'));
        Log::shouldReceive('error')->once();

        // Other services up
        DB::shouldReceive('select')->with('select 1')->andReturn([]);
        Redis::shouldReceive('connection->ping')->andReturn('PONG');

        $result = $this->healthService->full();

        $this->assertInstanceOf(HealthData::class, $result);
        $this->assertFalse($result->healthy);
        $this->assertEquals('System degradation detected', $result->message);
        $this->assertEquals(503, $result->statusCode);

        $this->assertFalse($result->details['services']['object_storage']);
    }
}
