<?php

namespace App\Services\User;

use App\DTOs\User\Profile\ChangePasswordData;
use App\DTOs\User\Profile\UpdateProfileData;
use App\DTOs\User\Profile\UploadAvatarData;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getProfile(User $user): User
    {
        return $this->userRepository->loadMissingRelations($user, ['roles']);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function updateProfile(User $user, UpdateProfileData $data): User
    {
        $this->userRepository->update($user, $data->toArray());

        return $user->fresh();
    }

    public function changePassword(User $user, ChangePasswordData $data): User
    {
        $this->userRepository->update($user, [
            'password' => Hash::make($data->password),
        ]);

        return $user->fresh();
    }

    public function uploadAvatar(User $user, UploadAvatarData $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Clear existing avatar from media collection
            $this->userRepository->clearMediaCollection($user, 'avatars');

            // Add new avatar to media collection (uploads to S3)
            $media = $this->userRepository->addMedia($user, $data->avatar, 'avatars', 's3');

            // Update avatar_url field
            $this->userRepository->update($user, [
                'avatar_url' => $media->getPathRelativeToRoot(),
            ]);

            return $user->fresh();
        });
    }
}
