<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\HealthResource;
use App\Services\HealthCheckService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('System - Health')]
class HealthController extends Controller
{
    public function __construct(
        private HealthCheckService $healthService
    ) {}

    /**
     * Basic Health Check
     *
     * Check if the application is running. No authentication required.
     *
     * @unauthenticated
     */
    public function basic(): JsonResponse
    {
        $result = $this->healthService->basic();

        return (new HealthResource($result))
            ->response()
            ->setStatusCode($result->statusCode);
    }

    /**
     * Full Health Check
     *
     * Full check if the application is running.
     *
     */
    public function full(): JsonResponse
    {
        $result = $this->healthService->full();

        return (new HealthResource($result))
            ->response()
            ->setStatusCode($result->statusCode);
    }
}
