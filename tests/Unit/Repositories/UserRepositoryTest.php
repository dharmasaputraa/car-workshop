<?php

namespace Tests\Unit\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_get_paginated_users(): void
    {
        User::factory()->count(20)->create();

        $result = $this->repository->getPaginatedUsers();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result->items()); // Default PER_PAGE = 15
        $this->assertEquals(20, $result->total());
    }

    public function test_get_paginated_users_filters_by_is_active(): void
    {
        User::factory()->count(10)->create(['is_active' => true]);
        User::factory()->count(5)->create(['is_active' => false]);

        request()->merge(['filter' => ['is_active' => true]]);

        $result = $this->repository->getPaginatedUsers();

        $this->assertCount(10, $result->items());
        foreach ($result->items() as $user) {
            $this->assertTrue($user->is_active);
        }
    }

    public function test_get_paginated_users_filters_by_name(): void
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        User::factory()->create(['name' => 'Bob Johnson']);

        request()->merge(['filter' => ['name' => 'john']]);

        $result = $this->repository->getPaginatedUsers();

        $this->assertCount(2, $result->items());
    }

    public function test_get_paginated_users_filters_by_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);
        User::factory()->create(['email' => 'jane@example.com']);
        User::factory()->create(['email' => 'bob@gmail.com']);

        request()->merge(['filter' => ['email' => 'example']]);

        $result = $this->repository->getPaginatedUsers();

        $this->assertCount(2, $result->items());
    }

    public function test_get_trashed_users(): void
    {
        $activeUsers = User::factory()->count(3)->create();
        $deletedUsers = User::factory()->count(2)->create();

        foreach ($deletedUsers as $user) {
            $user->delete();
        }

        $result = $this->repository->getTrashedUsers();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
        foreach ($result->items() as $user) {
            $this->assertNotNull($user->deleted_at);
        }
    }

    public function test_find_by_id(): void
    {
        $user = User::factory()->create();

        $result = $this->repository->findById($user->id);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->name, $result->name);
        $this->assertEquals($user->email, $result->email);
    }

    public function test_find_by_id_throws_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findById('invalid-uuid');
    }

    public function test_find_trashed_by_id(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $result = $this->repository->findTrashedById($user->id);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertNotNull($result->deleted_at);
    }

    public function test_find_trashed_by_id_throws_for_non_existent(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findTrashedById('invalid-uuid');
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_create_user(): void
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(User::class, $result);
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $this->assertNotNull($result->id);
    }

    public function test_update_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'is_active' => true,
        ]);

        $result = $this->repository->update($user, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_active' => false,
        ]);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Updated Name', $result->name);
        $this->assertEquals('updated@example.com', $result->email);
        $this->assertFalse($result->is_active);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'is_active' => false,
        ]);
    }

    public function test_delete_user(): void
    {
        $user = User::factory()->create();

        $this->repository->delete($user);

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
        $this->assertNotNull($user->fresh()->deleted_at);
    }

    public function test_restore_user(): void
    {
        $user = User::factory()->create();
        $user->delete();

        $result = $this->repository->restore($user);

        $this->assertInstanceOf(User::class, $result);
        $this->assertNull($result->deleted_at);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_toggle_active(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $result = $this->repository->toggleActive($user);

        $this->assertInstanceOf(User::class, $result);
        $this->assertFalse($result->is_active);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);

        // Toggle back
        $result = $this->repository->toggleActive($user);
        $this->assertTrue($result->is_active);
    }

    public function test_sync_roles(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $userRole = Role::create(['name' => 'user', 'guard_name' => 'api']);

        $user = User::factory()->create();
        $user->assignRole('user');

        $result = $this->repository->syncRoles($user, ['admin']);

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($result->hasRole('admin'));
        $this->assertFalse($result->hasRole('user'));
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS & MEDIA
    |--------------------------------------------------------------------------
    */

    public function test_load_relations(): void
    {
        $user = User::factory()->create();

        $result = $this->repository->loadRelations($user, ['roles']);

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($result->relationLoaded('roles'));
    }

    public function test_load_missing_relations(): void
    {
        $user = User::factory()->create();

        $result = $this->repository->loadMissingRelations($user, ['roles']);

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($result->relationLoaded('roles'));
    }
}
