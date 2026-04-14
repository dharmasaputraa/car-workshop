<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Service;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    /*
    |--------------------------------------------------------------------------
    | Core Permissions
    |--------------------------------------------------------------------------
    */

    public function before(AuthUser $authUser, $ability)
    {
        if (! $authUser->is_active) {
            return false;
        }

        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_service') || $authUser->hasRole([RoleType::CUSTOMER->value, RoleType::MECHANIC->value]);
    }

    public function view(AuthUser $authUser, Service $model): bool
    {
        return $authUser->can('view_service') || $authUser->hasRole([RoleType::CUSTOMER->value, RoleType::MECHANIC->value]);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_service');
    }

    public function update(AuthUser $authUser, Service $model): bool
    {
        return $authUser->can('update_service');
    }

    public function delete(AuthUser $authUser, Service $model): bool
    {
        return $authUser->can('delete_service');
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Actions
    |--------------------------------------------------------------------------
    */

    public function toggleActive(AuthUser $authUser, Service $model): bool
    {
        return $authUser->can('toggle_active_service');
    }
}
