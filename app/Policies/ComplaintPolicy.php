<?php

namespace App\Policies;

use App\Models\Complaint;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintPolicy
{
    use HandlesAuthorization;

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function isWorkOrderCarOwner(AuthUser $authUser, Complaint $model): bool
    {
        return $authUser->id === $model->workOrder->car->owner_id;
    }

    protected function isAssignedMechanic(AuthUser $authUser, Complaint $model): bool
    {
        return $model->complaintServices()->whereHas('mechanicAssignments', function ($query) use ($authUser) {
            $query->where('mechanic_id', $authUser->id);
        })->exists();
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
        return $authUser->can('view_any_complaint');
    }

    public function view(AuthUser $authUser, Complaint $model): bool
    {
        return $authUser->can('view_complaint') ||
            $this->isWorkOrderCarOwner($authUser, $model) ||
            $this->isAssignedMechanic($authUser, $model);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_complaint');
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Actions
    |--------------------------------------------------------------------------
    */

    public function reassign(AuthUser $authUser, Complaint $model): bool
    {
        return $authUser->can('reassign_complaint');
    }

    public function resolve(AuthUser $authUser, Complaint $model): bool
    {
        return $authUser->can('resolve_complaint');
    }

    public function reject(AuthUser $authUser, Complaint $model): bool
    {
        return $authUser->can('reject_complaint');
    }

    public function assignMechanic(AuthUser $authUser, Complaint $model): bool
    {
        return $authUser->can('assign_mechanic_complaint');
    }
}
