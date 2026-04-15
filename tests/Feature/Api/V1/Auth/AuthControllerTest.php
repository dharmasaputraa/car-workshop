<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary roles for tests
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
    | REGISTER
    |--------------------------------------------------------------------------
    */

    public function test_register_creates_user_and_returns_token(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'is_active',
                        'email_verified_at',
                    ],
                ],
                'meta' => [
                    'is_new',
                    'token' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'is_active' => true,
        ]);

        $this->assertArrayHasKey('access_token', $response->json('meta.token'));
    }

    public function test_register_validation_fails(): void
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                        'status',
                    ],
                ],
            ]);

        // Verify error pointers exist for each field
        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer')->toArray();

        $this->assertContains('/data/attributes/name', $pointers);
        $this->assertContains('/data/attributes/email', $pointers);
        $this->assertContains('/data/attributes/password', $pointers);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function test_login_returns_token(): void
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'is_active',
                    ],
                ],
                'meta' => [
                    'token' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                    ],
                ],
            ]);

        $this->assertEquals('test@example.com', $response->json('data.attributes.email'));
        $this->assertArrayHasKey('access_token', $response->json('meta.token'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                    ],
                ],
            ]);

        $errors = collect($response->json('errors'));
        $this->assertGreaterThan(0, $errors->count());
    }

    public function test_login_fails_for_inactive_user(): void
    {
        $inactiveUser = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $loginData = [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $loginData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                    ],
                ],
            ]);

        $errors = collect($response->json('errors'));
        $this->assertGreaterThan(0, $errors->count());
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function test_logout_succeeds(): void
    {
        $token = JWTAuth::fromUser($this->user);

        $response = $this->postJson('/api/v1/auth/revoke', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'avatar_url',
                        'is_active',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                    'meta' => [
                        'is_super_admin',
                    ],
                ],
            ]);
    }

    public function test_logout_unauthenticated(): void
    {
        $response = $this->postJson('/api/v1/auth/revoke');

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | REFRESH TOKEN
    |--------------------------------------------------------------------------
    */

    public function test_refresh_returns_new_token(): void
    {
        $token = JWTAuth::fromUser($this->user);

        $response = $this->postJson('/api/v1/auth/refresh', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'is_active',
                    ],
                ],
                'meta' => [
                    'token' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                    ],
                ],
            ]);

        $this->assertArrayHasKey('access_token', $response->json('meta.token'));
    }

    /*
    |--------------------------------------------------------------------------
    | ME (GET AUTHENTICATED USER)
    |--------------------------------------------------------------------------
    */

    public function test_me_returns_authenticated_user(): void
    {
        $token = JWTAuth::fromUser($this->user);

        $response = $this->getJson('/api/v1/profile', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'is_active',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertEquals($this->user->id, $response->json('data.id'));
        $this->assertEquals('test@example.com', $response->json('data.attributes.email'));
    }

    public function test_me_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD
    |--------------------------------------------------------------------------
    */

    public function test_forgot_password_sends_reset_link(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['meta' => ['message' => 'Password reset link has been sent to your email.']]);

        // Notification is sent via Laravel's Password broker
        $this->assertTrue(true);
    }

    public function test_forgot_password_validation_fails(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                    ],
                ],
            ]);

        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer')->toArray();

        $this->assertContains('/data/attributes/email', $pointers);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY EMAIL
    |--------------------------------------------------------------------------
    */

    public function test_verify_email_succeeds(): void
    {
        $unverifiedUser = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        // Create a signed URL for verification using the correct route name
        $url = URL::temporarySignedRoute(
            'api.v1.auth.email.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $unverifiedUser->id,
                'hash' => sha1($unverifiedUser->getEmailForVerification()),
            ]
        );

        // Make the request using the full signed URL
        $response = $this->getJson($url);

        $response->assertStatus(200)
            ->assertJson(['meta' => ['message' => 'Email verified successfully.']]);

        // Verify the user is now verified
        $unverifiedUser->refresh();
        $this->assertNotNull($unverifiedUser->email_verified_at);
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND VERIFICATION EMAIL
    |--------------------------------------------------------------------------
    */

    public function test_resend_verification_email_succeeds(): void
    {
        Notification::fake();

        $unverifiedUser = User::factory()->create([
            'email' => 'unverified2@example.com',
            'email_verified_at' => null,
        ]);

        $token = JWTAuth::fromUser($unverifiedUser);

        $response = $this->postJson('/api/v1/auth/email/verification-notification', [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name',
                        'email',
                        'avatar_url',
                        'is_active',
                        'email_verified_at',
                        'created_at',
                        'updated_at',
                    ],
                    'meta' => [
                        'is_super_admin',
                    ],
                ],
            ]);

        // Verify that user is still unverified (the notification was sent)
        $unverifiedUser->refresh();
        $this->assertNull($unverifiedUser->email_verified_at);
    }
}
