<?php

namespace App\Services;

use App\DTOs\Health\HealthData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckService
{
    public function basic(): HealthData
    {
        return HealthData::make(
            healthy: true,
            message: 'OK',
            details: [
                'app'             => config('app.name'),
                'env'             => app()->environment(),
                'response_time_ms' => $this->responseTime(),
            ],
            statusCode: 200
        );
    }

    public function full(): HealthData
    {
        $start = microtime(true);

        $services = [
            'database'       => $this->checkDatabase(),
            'redis'          => $this->checkRedis(),
            'object_storage' => $this->checkObjectStorage(),
        ];

        $isHealthy = !in_array(false, $services, true);

        return HealthData::make(
            healthy: $isHealthy,
            message: $isHealthy ? 'System is operational' : 'System degradation detected',
            details: [
                'app'             => config('app.name'),
                'env'             => app()->environment(),
                'app_version'     => config('app.version', '1.0.0'), // app()->version() = Laravel version, bukan app version
                'services'        => $services,
                'response_time_ms' => round((microtime(true) - $start) * 1000, 2),
            ],
            statusCode: $isHealthy ? 200 : 503
        );
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('select 1'); // lebih reliable dari getPdo()
            return true;
        } catch (\Throwable $e) {
            Log::error('DB Health Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Throwable $e) {
            Log::error('Redis Health Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function checkObjectStorage(): bool
    {
        try {
            Storage::disk('s3')->put('health-check.txt', 'ok');
            Storage::disk('s3')->delete('health-check.txt');
            return true;
        } catch (\Throwable $e) {
            Log::error('Object Storage Health Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    private function responseTime(): float
    {
        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        return round((microtime(true) - $start) * 1000, 2);
    }
}
