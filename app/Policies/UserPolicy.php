<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;
use STS\FilamentImpersonate\Facades\Impersonation;

class UserPolicy
{
    use HandlesAuthorization;

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function isNotSelf(AuthUser $authUser, User $model): bool
    {
        return $authUser->id !== $model->id;
    }

    protected function isImpersonating(): bool
    {
        return session()->has('impersonator_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Core Permissions
    |--------------------------------------------------------------------------
    */

    public function before($authUser, $ability)
    {
        if (! $authUser->is_active) {
            return false;
        }

        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        if (Impersonation::isImpersonating()) {
            return true;
        }

        if ($authUser->can('view_any_user')) {
            return true;
        }

        return false;
    }

    public function view(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('view_user');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_user');
    }

    public function update(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('update_user')
            && $this->isNotSelf($authUser, $model);
    }

    public function delete(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('delete_user')
            && $this->isNotSelf($authUser, $model);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_user');
    }

    public function restore(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('restore_user');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_user');
    }

    public function forceDelete(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('force_delete_user');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_user');
    }

    public function replicate(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('replicate_user');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_user');
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Actions (Filament Actions)
    |--------------------------------------------------------------------------
    */

    public function changeRole(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('change_role_user')
            && $this->isNotSelf($authUser, $model);
    }

    public function toggleActive(AuthUser $authUser, User $model): bool
    {
        return $authUser->can('toggle_active_user')
            && $this->isNotSelf($authUser, $model);
    }

    public function disable2FA(AuthUser $authUser, User $model): bool
    {
        return $this->isNotSelf($authUser, $model)
            && $model->hasConfirmedTwoFactor();
    }
}
