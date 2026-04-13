<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\ChangeRoleRequest;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\User\UserCollection;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * @tags Users
 */
class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    #[QueryParameter('filter[is_active]', description: 'Filter by active status', type: 'boolean', example: true)]
    #[QueryParameter('filter[name]', description: 'Filter by name (partial match)', type: 'string', example: 'john')]
    #[QueryParameter('filter[email]', description: 'Filter by email (partial match)', type: 'string', example: 'gmail')]
    #[QueryParameter('filter[role]', description: 'Filter by role name', type: 'string', example: 'admin')]
    #[QueryParameter('sort', description: 'Sort by field (prefix - for desc). Options: name, email, created_at, is_active', type: 'string', example: '-created_at')]
    #[QueryParameter('include', description: 'Include relations: roles', type: 'string', example: 'roles')]
    #[QueryParameter('per_page', description: 'Number of results per page', type: 'integer', example: 15)]
    #[QueryParameter('page', description: 'Page number', type: 'integer', example: 1)]
    /**
     * List Users
     *
     * Retrieve a paginated list of active users with filtering, sorting, and eager loading support.
     */
    public function index(): UserCollection
    {
        Gate::authorize('viewAny', User::class);

        return new UserCollection(
            $this->userService->getPaginatedUsers()
        );
    }

    /**
     * Create User
     *
     * Create a new user and optionally assign a role.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get User
     *
     * Retrieve a single user by ID.
     */
    public function show(string $id): UserResource
    {
        $user = $this->userService->getUserById($id);
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Update User
     *
     * Update an existing user's data. Role is not updatable via this endpoint.
     */
    public function update(UpdateUserRequest $request, string $id): UserResource
    {
        $user = $this->userService->getUserById($id);
        Gate::authorize('update', $user);

        return new UserResource(
            $this->userService->updateUser($user, $request->validated())
        );
    }

    /**
     * Delete User
     *
     * Soft delete a user. Can be restored later via the restore endpoint.
     */
    public function destroy(string $id): Response
    {
        $user = $this->userService->getUserById($id);
        Gate::authorize('delete', $user);
        $this->userService->deleteUser($user);

        return response()->noContent();
    }

    #[QueryParameter('filter[name]', description: 'Filter by name (partial match)', type: 'string', example: 'john')]
    #[QueryParameter('filter[email]', description: 'Filter by email (partial match)', type: 'string', example: 'gmail')]
    #[QueryParameter('sort', description: 'Sort by field (prefix - for desc). Options: name, deleted_at', type: 'string', example: '-deleted_at')]
    #[QueryParameter('per_page', description: 'Number of results per page', type: 'integer', example: 15)]
    #[QueryParameter('page', description: 'Page number', type: 'integer', example: 1)]
    /**
     * List Trashed Users
     *
     * Retrieve soft-deleted users with filtering and sorting support.
     */
    public function trashed(): UserCollection
    {
        Gate::authorize('viewAny', User::class);

        return new UserCollection(
            $this->userService->getTrashedUsers()
        );
    }

    /**
     * Restore User
     *
     * Restore a soft-deleted user by ID.
     */
    public function restore(string $id): UserResource
    {
        $user = $this->userService->getUserTrashedById($id);
        Gate::authorize('restore', $user);

        return new UserResource(
            $this->userService->restoreUser($id)
        );
    }

    /**
     * Toggle User Active Status
     *
     * Toggle the is_active field of a user between true and false.
     */
    public function toggleActive(string $id): UserResource
    {
        $user = $this->userService->getUserById($id);
        Gate::authorize('toggleActive', $user);

        return new UserResource(
            $this->userService->toggleActive($user)
        );
    }

    /**
     * Change User Role
     *
     * Assign a new role to a user, replacing any previously assigned roles.
     */
    public function changeRole(ChangeRoleRequest $request, string $id): UserResource
    {
        $user = $this->userService->getUserById($id);
        Gate::authorize('changeRole', $user);

        return new UserResource(
            $this->userService->changeRole($user, $request->validated('role'))
        );
    }
}
