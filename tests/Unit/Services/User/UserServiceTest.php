<?php

namespace Tests\Unit\Services\User;

use App\DTOs\User\ChangeRoleData;
use App\DTOs\User\StoreUserData;
use App\DTOs\User\UpdateUserData;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\User\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    /** @var MockInterface|UserRepositoryInterface */
    protected $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(UserRepositoryInterface::class);
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_get_paginated_users_delegates_to_repository(): void
    {
        $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $this->repositoryMock
            ->shouldReceive('getPaginatedUsers')
            ->once()
            ->andReturn($paginator);

        $service = new UserService($this->repositoryMock);
        $result = $service->getPaginatedUsers();

        $this->assertSame($paginator, $result);
    }

    public function test_get_trashed_users_delegates_to_repository(): void
    {
        $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $this->repositoryMock
            ->shouldReceive('getTrashedUsers')
            ->once()
            ->andReturn($paginator);

        $service = new UserService($this->repositoryMock);
        $result = $service->getTrashedUsers();

        $this->assertSame($paginator, $result);
    }

    public function test_get_user_by_id_delegates_to_repository(): void
    {
        $user = Mockery::mock(User::class);
        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('test-id')
            ->andReturn($user);

        $service = new UserService($this->repositoryMock);
        $result = $service->getUserById('test-id');

        $this->assertSame($user, $result);
    }

    public function test_get_user_by_id_throws_when_not_found(): void
    {
        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('invalid-id')
            ->andThrow(new ModelNotFoundException('User not found'));

        $service = new UserService($this->repositoryMock);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $service->getUserById('invalid-id');
    }

    public function test_get_user_trashed_by_id_delegates_to_repository(): void
    {
        $user = Mockery::mock(User::class);
        $this->repositoryMock
            ->shouldReceive('findTrashedById')
            ->once()
            ->with('test-id')
            ->andReturn($user);

        $service = new UserService($this->repositoryMock);
        $result = $service->getUserTrashedById('test-id');

        $this->assertSame($user, $result);
    }

    public function test_get_user_trashed_by_id_throws_when_not_found(): void
    {
        $this->repositoryMock
            ->shouldReceive('findTrashedById')
            ->once()
            ->with('invalid-id')
            ->andThrow(new ModelNotFoundException('User not found'));

        $service = new UserService($this->repositoryMock);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $service->getUserTrashedById('invalid-id');
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_create_user_without_role(): void
    {
        $dto = new StoreUserData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            role: null,
            isActive: true
        );

        $user = Mockery::mock(User::class);
        $user->shouldReceive('assignRole')->never();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'is_active' => true,
            ])
            ->andReturn($user);

        $this->repositoryMock
            ->shouldReceive('loadRelations')
            ->once()
            ->with($user, ['roles'])
            ->andReturn($user);

        $service = new UserService($this->repositoryMock);
        $result = $service->createUser($dto);

        $this->assertSame($user, $result);
    }

    public function test_create_user_with_role(): void
    {
        $dto = new StoreUserData(
            name: 'Jane Doe',
            email: 'jane@example.com',
            password: 'password123',
            role: 'admin',
            isActive: true
        );

        $user = Mockery::mock(User::class);
        $user->shouldReceive('assignRole')
            ->once()
            ->with('admin')
            ->andReturn($user);

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
                'is_active' => true,
            ])
            ->andReturn($user);

        $this->repositoryMock
            ->shouldReceive('loadRelations')
            ->once()
            ->with($user, ['roles'])
            ->andReturn($user);

        $service = new UserService($this->repositoryMock);
        $result = $service->createUser($dto);

        $this->assertSame($user, $result);
    }

    public function test_update_user(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('fresh')
            ->once()
            ->with('roles')
            ->andReturn($user);

        $dto = new UpdateUserData(
            name: 'Updated Name',
            email: 'updated@example.com',
            password: null,
            isActive: false,
            avatarUrl: null
        );

        $this->repositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($user, [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'is_active' => false,
            ])
            ->andReturn($user);

        $service = new UserService($this->repositoryMock);
        $result = $service->updateUser($user, $dto);

        $this->assertSame($user, $result);
    }

    public function test_delete_user(): void
    {
        $user = Mockery::mock(User::class);

        $this->repositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($user);

        $service = new UserService($this->repositoryMock);
        $service->deleteUser($user);

        $this->assertTrue(true); // No exception thrown
    }

    public function test_restore_user(): void
    {
        $user = Mockery::mock(User::class);

        $this->repositoryMock
            ->shouldReceive('findTrashedById')
            ->once()
            ->with('test-id')
            ->andReturn($user);

        $this->repositoryMock
            ->shouldReceive('restore')
            ->once()
            ->with($user)
            ->andReturn($user);

        $this->repositoryMock
            ->shouldReceive('loadRelations')
            ->once()
            ->with($user, ['roles'])
            ->andReturn($user);

        $service = new UserService($this->repositoryMock);
        $result = $service->restoreUser('test-id');

        $this->assertSame($user, $result);
    }

    public function test_toggle_active(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('fresh')
            ->once()
            ->andReturn($user);

        $this->repositoryMock
            ->shouldReceive('toggleActive')
            ->once()
            ->with($user)
            ->andReturn($user);

        $service = new UserService($this->repositoryMock);
        $result = $service->toggleActive($user);

        $this->assertSame($user, $result);
    }

    public function test_change_role(): void
    {
        $user = Mockery::mock(User::class);

        $this->repositoryMock
            ->shouldReceive('syncRoles')
            ->once()
            ->with($user, ['admin'])
            ->andReturn($user);

        $this->repositoryMock
            ->shouldReceive('loadRelations')
            ->once()
            ->with($user, ['roles'])
            ->andReturn($user);

        $dto = new ChangeRoleData(role: 'admin');

        $service = new UserService($this->repositoryMock);
        $result = $service->changeRole($user, $dto);

        $this->assertSame($user, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
