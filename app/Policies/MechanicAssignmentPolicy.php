<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\MechanicAssignment;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class MechanicAssignmentPolicy
{
    use HandlesAuthorization;

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function isAssignedMechanic(AuthUser $authUser, MechanicAssignment $model): bool
    {
        return $authUser->id === $model->mechanic_id;
    }

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
        return $authUser->can('view_any_mechanic_assignment') || $authUser->hasRole(RoleType::MECHANIC->value);
    }

    public function view(AuthUser $authUser, MechanicAssignment $model): bool
    {
        return $authUser->can('view_mechanic_assignment') || $this->isAssignedMechanic($authUser, $model);
    }

    public function create(AuthUser $authUser): bool
    {
        // Biasanya hanya Admin/Service Advisor yang boleh assign mekanik
        return $authUser->can('create_mechanic_assignment');
    }

    public function update(AuthUser $authUser, MechanicAssignment $model): bool
    {
        // Admin bisa update, ATAU mekanik yang bersangkutan bisa update status pengerjaannya
        return $authUser->can('update_mechanic_assignment') || $this->isAssignedMechanic($authUser, $model);
    }

    public function delete(AuthUser $authUser, MechanicAssignment $model): bool
    {
        return $authUser->can('delete_mechanic_assignment');
    }

    public function cancel(AuthUser $authUser, MechanicAssignment $model): bool
    {
        return $authUser->can('cancel_mechanic_assignment');
    }

    public function start(AuthUser $authUser, MechanicAssignment $model): bool
    {
        return $authUser->can('start_mechanic_assignment') || $this->isAssignedMechanic($authUser, $model);
    }

    public function complete(AuthUser $authUser, MechanicAssignment $model): bool
    {
        return $authUser->can('complete_mechanic_assignment') || $this->isAssignedMechanic($authUser, $model);
    }
}
