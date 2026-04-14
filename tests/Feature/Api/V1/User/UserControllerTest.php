<?php

namespace Tests\Feature\Api\V1\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected string $adminToken;

    protected User $regularUser;
    protected string $regularToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles with api guard
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $userRole = Role::create(['name' => 'user', 'guard_name' => 'api']);

        // Create permissions with api guard
        $permissions = [
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'restore_user',
            'toggle_active_user',
            'change_role_user',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }

        // Assign all permissions to admin role
        $adminRole->givePermissionTo($permissions);

        // Create admin user with full permissions
        $this->adminUser = User::factory()->create([
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('admin');
        $this->adminToken = JWTAuth::fromUser($this->adminUser);

        // Create regular user with limited permissions
        $this->regularUser = User::factory()->create([
            'is_active' => true,
        ]);
        $this->regularUser->assignRole('user');
        $this->regularToken = JWTAuth::fromUser($this->regularUser);
    }

    protected function withAuth(?string $token = null): array
    {
        return $token ? ['Authorization' => "Bearer {$token}"] : [];
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX - List Users
    |--------------------------------------------------------------------------
    */

    public function test_index_returns_paginated_users(): void
    {
        User::factory()->count(25)->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/users', $this->withAuth($this->adminToken));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
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
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_index_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }

    public function test_index_without_permission(): void
    {
        // Create user without view_any_user permission
        $restrictedUser = User::factory()->create(['is_active' => true]);
        $restrictedUser->assignRole('user');
        $restrictedToken = JWTAuth::fromUser($restrictedUser);

        $response = $this->getJson('/api/v1/users', $this->withAuth($restrictedToken));

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE - Create User
    |--------------------------------------------------------------------------
    */

    public function test_store_creates_user(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/users', $userData, $this->withAuth($this->adminToken));

        $response->assertStatus(201)
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
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_store_unauthenticated(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(401);
    }

    public function test_store_validation_fails(): void
    {
        $response = $this->postJson('/api/v1/users', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ], $this->withAuth($this->adminToken));

        $response->assertStatus(422);

        // JSON:API validation error format
        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer');

        $this->assertContains('/data/attributes/name', $pointers);
        $this->assertContains('/data/attributes/email', $pointers);
        $this->assertContains('/data/attributes/password', $pointers);
    }

    public function test_store_without_permission(): void
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/users', $userData, $this->withAuth($this->regularToken));

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW - Get User
    |--------------------------------------------------------------------------
    */

    public function test_show_returns_user(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/v1/users/{$user->id}", $this->withAuth($this->adminToken));

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
            ]);

        $this->assertEquals($user->id, $response->json('data.id'));
    }

    public function test_show_user_not_found(): void
    {
        $response = $this->getJson('/api/v1/users/invalid-uuid', $this->withAuth($this->adminToken));

        $response->assertStatus(404);
    }

    public function test_show_unauthenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$user->id}");

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE - Update User
    |--------------------------------------------------------------------------
    */

    public function test_update_updates_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'is_active' => true,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $updateData, $this->withAuth($this->adminToken));

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_active' => false,
        ]);
    }

    public function test_update_user_not_found(): void
    {
        $response = $this->putJson('/api/v1/users/invalid-uuid', [
            'name' => 'Updated Name',
        ], $this->withAuth($this->adminToken));

        $response->assertStatus(404);
    }

    public function test_update_unauthenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY - Delete User
    |--------------------------------------------------------------------------
    */

    public function test_destroy_soft_deletes_user(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/v1/users/{$user->id}", [], $this->withAuth($this->adminToken));

        $response->assertStatus(204);

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    public function test_destroy_user_not_found(): void
    {
        $response = $this->deleteJson('/api/v1/users/invalid-uuid', [], $this->withAuth($this->adminToken));

        $response->assertStatus(404);
    }

    public function test_destroy_unauthenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/v1/users/{$user->id}");

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | TRASHED - List Soft-Deleted Users
    |--------------------------------------------------------------------------
    */

    public function test_trashed_returns_soft_deleted_users(): void
    {
        $activeUser = User::factory()->create();
        $deletedUser = User::factory()->create();
        $deletedUser->delete();

        $response = $this->getJson('/api/v1/users/trashed', $this->withAuth($this->adminToken));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'attributes' => [
                            'name',
                            'email',
                            'deleted_at',
                        ],
                    ],
                ],
            ]);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains($deletedUser->id, $ids);
        $this->assertNotContains($activeUser->id, $ids);
    }

    public function test_trashed_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/users/trashed');

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | RESTORE - Restore User
    |--------------------------------------------------------------------------
    */

    public function test_restore_restores_user(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->postJson("/api/v1/users/{$user->id}/restore", [], $this->withAuth($this->adminToken));

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_restore_user_not_found(): void
    {
        $response = $this->postJson('/api/v1/users/invalid-uuid/restore', [], $this->withAuth($this->adminToken));

        $response->assertStatus(404);
    }

    public function test_restore_unauthenticated(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->postJson("/api/v1/users/{$user->id}/restore");

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | TOGGLE ACTIVE - Toggle User Active Status
    |--------------------------------------------------------------------------
    */

    public function test_toggle_active_toggles_status(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->patchJson("/api/v1/users/{$user->id}/toggle-active", [], $this->withAuth($this->adminToken));

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);

        // Toggle back
        $response = $this->patchJson("/api/v1/users/{$user->id}/toggle-active", [], $this->withAuth($this->adminToken));

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_toggle_active_forbidden_without_permission(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->patchJson("/api/v1/users/{$user->id}/toggle-active", [], $this->withAuth($this->regularToken));

        $response->assertStatus(403);
    }

    public function test_toggle_active_unauthenticated(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->patchJson("/api/v1/users/{$user->id}/toggle-active");

        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE ROLE - Change User Role
    |--------------------------------------------------------------------------
    */

    public function test_change_role_updates_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->patchJson("/api/v1/users/{$user->id}/role", [
            'role' => 'admin',
        ], $this->withAuth($this->adminToken));

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('user'));
    }

    public function test_change_role_validation_fails(): void
    {
        $user = User::factory()->create();

        $response = $this->patchJson("/api/v1/users/{$user->id}/role", [
            'role' => '',
        ], $this->withAuth($this->adminToken));

        $response->assertStatus(422);

        // JSON:API validation error format
        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer');

        $this->assertContains('/data/attributes/role', $pointers);
    }

    public function test_change_role_unauthenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->patchJson("/api/v1/users/{$user->id}/role", [
            'role' => 'admin',
        ]);

        $response->assertStatus(401);
    }

    public function test_change_role_forbidden_without_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->patchJson("/api/v1/users/{$user->id}/role", [
            'role' => 'admin',
        ], $this->withAuth($this->regularToken));

        $response->assertStatus(403);
    }
}
