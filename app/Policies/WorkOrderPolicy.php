<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\WorkOrder;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkOrderPolicy
{
    use HandlesAuthorization;

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function isCarOwner(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->id === $model->car()->value('owner_id');
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
        return $authUser->can('view_any_work_order');
    }

    public function view(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->can('view_work_order') || $this->isCarOwner($authUser, $model);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_work_order');
    }

    public function update(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->can('update_work_order');
    }

    public function delete(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->can('delete_work_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Actions
    |--------------------------------------------------------------------------
    */

    public function diagnose(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->can('diagnose_work_order');
    }

    public function approve(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->can('approve_work_order') || $this->isCarOwner($authUser, $model);
    }

    public function complete(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->can('complete_work_order');
    }

    public function cancel(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->can('cancel_work_order') || $this->isCarOwner($authUser, $model);
    }

    public function markAsInvoiced(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->can('mark_invoiced_work_order');
    }

    public function recordComplaint(AuthUser $authUser, WorkOrder $model): bool
    {
        return $authUser->can('record_complaint_work_order') || $this->isCarOwner($authUser, $model);
    }

    public function assignMechanic(AuthUser $authUser): bool
    {
        return $authUser->can('assign_mechanic_work_order');
    }

    public function cancelMechanicAssignment(AuthUser $authUser): bool
    {
        return $authUser->can('assign_mechanic_work_order');
    }

    public function startWorkOrderService(AuthUser $authUser): bool
    {
        return $authUser->can('start_work_order_service');
    }

    public function completeWorkOrderService(AuthUser $authUser): bool
    {
        return $authUser->can('complete_work_order_service');
    }
}
