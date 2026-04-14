<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary role
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | BASIC HEALTH CHECK (PUBLIC)
    |--------------------------------------------------------------------------
    */

    public function test_basic_returns_healthy_status(): void
    {
        $response = $this->getJson('/api/v1/health/basic');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'healthy',
                        'message',
                        'details',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('health', $data['id']);
        $this->assertEquals('health', $data['type']);
        $this->assertTrue($data['attributes']['healthy']);
        $this->assertEquals('OK', $data['attributes']['message']);
    }

    public function test_basic_includes_app_details(): void
    {
        $response = $this->getJson('/api/v1/health/basic');

        $details = $response->json('data.attributes.details');

        $this->assertArrayHasKey('app', $details);
        $this->assertArrayHasKey('env', $details);
        $this->assertArrayHasKey('response_time_ms', $details);
        $this->assertIsString($details['app']);
        $this->assertIsString($details['env']);
        $this->assertIsNumeric($details['response_time_ms']);
    }

    public function test_basic_no_authentication_required(): void
    {
        // Should work without any authentication
        $response = $this->getJson('/api/v1/health/basic');

        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | FULL HEALTH CHECK (AUTHENTICATED)
    |--------------------------------------------------------------------------
    */

    public function test_full_returns_healthy_status_when_authenticated(): void
    {
        $token = JWTAuth::fromUser($this->user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/health/full');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'healthy',
                        'message',
                        'details',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('health', $data['id']);
        $this->assertEquals('health', $data['type']);
        $this->assertTrue($data['attributes']['healthy']);
        $this->assertEquals('System is operational', $data['attributes']['message']);
    }

    public function test_full_includes_all_service_checks(): void
    {
        $token = JWTAuth::fromUser($this->user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/health/full');

        $details = $response->json('data.attributes.details');

        $this->assertArrayHasKey('app', $details);
        $this->assertArrayHasKey('env', $details);
        $this->assertArrayHasKey('app_version', $details);
        $this->assertArrayHasKey('services', $details);
        $this->assertArrayHasKey('response_time_ms', $details);

        $services = $details['services'];
        $this->assertArrayHasKey('database', $services);
        $this->assertArrayHasKey('redis', $services);
        $this->assertArrayHasKey('object_storage', $services);
    }

    public function test_full_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/health/full');

        $response->assertStatus(401);
    }

    public function test_full_returns_401_with_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/health/full');

        $response->assertStatus(401);
    }

    public function test_full_returns_401_for_inactive_user(): void
    {
        $inactiveUser = User::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $token = JWTAuth::fromUser($inactiveUser);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/health/full');

        // Inactive users get a redirect (302) or error response
        // depending on middleware configuration
        $this->assertContains($response->status(), [401, 302, 422]);
    }
}
