<?php

namespace App\Services\User;

use App\DTOs\User\Profile\ChangePasswordData;
use App\DTOs\User\Profile\UpdateProfileData;
use App\DTOs\User\Profile\UploadAvatarData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    /*
    |--------------------------------------------------------------------------
    | READ
    |--------------------------------------------------------------------------
    */

    public function getProfile(User $user): User
    {
        return $user->loadMissing(['roles']);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE
    |--------------------------------------------------------------------------
    */

    public function updateProfile(User $user, UpdateProfileData $data): User
    {
        $user->update($data->toArray());

        return $user->fresh();
    }

    public function changePassword(User $user, ChangePasswordData $data): User
    {
        $user->update([
            'password' => Hash::make($data->password),
        ]);

        return $user->fresh();
    }

    public function uploadAvatar(User $user, UploadAvatarData $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Clear existing avatar from media collection
            $user->clearMediaCollection('avatars');

            // Add new avatar to media collection (uploads to S3)
            $media = $user->addMedia($data->avatar)
                ->toMediaCollection('avatars', 's3');

            // Update avatar_url field
            $user->update([
                'avatar_url' => $media->getPathRelativeToRoot(),
            ]);

            return $user->fresh();
        });
    }
}
