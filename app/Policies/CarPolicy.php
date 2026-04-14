<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Enums\WorkOrderStatus;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class CarPolicy
{
    use HandlesAuthorization;

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function isOwner(AuthUser $authUser, Car $model): bool
    {
        return $authUser->id === $model->owner_id;
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

        // Super Admin selalu bisa melakukan apapun (opsional, sesuaikan dengan logic Spatie Anda)
        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        // Customer dan Mechanic diizinkan viewAny, tapi di Repository di-filter berdasarkan owner_id/assignments
        return $authUser->can('view_any_car') ||
            $authUser->hasRole([RoleType::CUSTOMER->value, RoleType::MECHANIC->value]);
    }

    public function view(AuthUser $authUser, Car $model): bool
    {
        return $authUser->can('view_car') || $this->isOwner($authUser, $model);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_car') || $authUser->hasRole(RoleType::CUSTOMER->value);
    }

    public function update(AuthUser $authUser, Car $model): bool
    {
        return $authUser->can('update_car') || $this->isOwner($authUser, $model);
    }

    public function delete(AuthUser $authUser, Car $model): bool
    {
        // Prevent deleting car with active work orders
        if ($model->workOrders()->whereNotIn('status', [
            \App\Enums\WorkOrderStatus::COMPLETED->value,
            \App\Enums\WorkOrderStatus::CANCELED->value,
            \App\Enums\WorkOrderStatus::INVOICED->value,
        ])->exists()) {
            return false;
        }

        return $authUser->can('delete_car') || $this->isOwner($authUser, $model);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_car');
    }

    public function restore(AuthUser $authUser, Car $model): bool
    {
        return $authUser->can('restore_car');
    }

    public function forceDelete(AuthUser $authUser, Car $model): bool
    {
        return $authUser->can('force_delete_car');
    }
}
