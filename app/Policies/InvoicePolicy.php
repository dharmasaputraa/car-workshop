<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Invoice;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function isWorkOrderCarOwner(AuthUser $authUser, Invoice $model): bool
    {
        return $authUser->id === $model->workOrder->car->owner_id;
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
        return $authUser->can('view_any_invoice');
    }

    public function view(AuthUser $authUser, Invoice $model): bool
    {
        return $authUser->can('view_invoice') || $this->isWorkOrderCarOwner($authUser, $model);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_invoice');
    }

    public function update(AuthUser $authUser, Invoice $model): bool
    {
        return $authUser->can('update_invoice');
    }

    public function delete(AuthUser $authUser, Invoice $model): bool
    {
        return $authUser->can('delete_invoice');
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Actions
    |--------------------------------------------------------------------------
    */

    public function send(AuthUser $authUser, Invoice $model): bool
    {
        return $authUser->can('send_invoice');
    }

    public function pay(AuthUser $authUser, Invoice $model): bool
    {
        return $authUser->can('pay_invoice');
    }

    public function cancel(AuthUser $authUser, Invoice $model): bool
    {
        return $authUser->can('cancel_invoice');
    }
}
