<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Requests\Api\V1\User\ChangeRoleRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {
        //
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $users = $this->userService->getPaginatedUsers($request->query());

        return UserResource::collection($users);
    }

    public function create()
    {
        //
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id): UserResource
    {
        $user = $this->userService->getUserById($id, $request->query());

        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    public function edit(User $user)
    {
        //
    }

    public function update(UpdateUserRequest $request, string $id): UserResource
    {
        $user = $this->userService->getUserById($id);

        $updatedUser = $this->userService->updateUser($user, $request->validated());

        return new UserResource($updatedUser);
    }

    public function destroy(string $id): \Illuminate\Http\JsonResponse
    {
        $user = $this->userService->getUserById($id);

        Gate::authorize('delete', $user);

        $this->userService->deleteUser($user);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function trashed(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        $users = $this->userService->getTrashedUsers($request->query());
        return UserResource::collection($users);
    }

    public function restore(string $id): UserResource
    {
        $user = User::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $user);

        $this->userService->restoreUser($id);
        return new UserResource($user->fresh());
    }

    public function toggleActive(string $id): UserResource
    {
        $user = $this->userService->getUserById($id);

        Gate::authorize('toggleActive', $user);

        $updatedUser = $this->userService->toggleActive($user);
        return new UserResource($updatedUser);
    }

    public function changeRole(ChangeRoleRequest $request, string $id): UserResource
    {
        $user = $this->userService->getUserById($id);

        $updatedUser = $this->userService->changeRole($user, $request->validated('role'));

        return new UserResource($updatedUser);
    }
}
