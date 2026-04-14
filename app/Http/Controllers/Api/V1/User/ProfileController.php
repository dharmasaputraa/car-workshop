<?php

namespace App\Http\Controllers\Api\V1\User;

use App\DTOs\User\Profile\ChangePasswordData;
use App\DTOs\User\Profile\UpdateProfileData;
use App\DTOs\User\Profile\UploadAvatarData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\Profile\ChangePasswordRequest;
use App\Http\Requests\Api\V1\User\Profile\UpdateProfileRequest;
use App\Http\Requests\Api\V1\User\Profile\UploadAvatarRequest;
use App\Http\Resources\Api\V1\User\ProfileResource;
use App\Services\User\ProfileService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('System - Profile')]
class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {}

    /**
     * Get Profile
     *
     * Retrieve the authenticated user's profile information.
     */
    public function show(): ProfileResource
    {
        $user = auth('api')->user();

        return new ProfileResource(
            $this->profileService->getProfile($user)
        );
    }

    /**
     * Update Profile
     *
     * Update the authenticated user's profile information.
     */
    public function update(UpdateProfileRequest $request): ProfileResource
    {
        $user = auth('api')->user();
        $data = UpdateProfileData::fromRequest($request);

        return new ProfileResource(
            $this->profileService->updateProfile($user, $data)
        );
    }

    /**
     * Change Password
     *
     * Change the authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $data = ChangePasswordData::fromRequest($request);

        $this->profileService->changePassword($user, $data);

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Upload Avatar
     *
     * Upload a new avatar for the authenticated user.
     */
    public function uploadAvatar(UploadAvatarRequest $request): ProfileResource
    {
        $user = auth('api')->user();
        $data = UploadAvatarData::fromRequest($request);

        return new ProfileResource(
            $this->profileService->uploadAvatar($user, $data)
        );
    }
}
