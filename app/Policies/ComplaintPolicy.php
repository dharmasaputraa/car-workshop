<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    /**
     * Determine whether the user can view any complaints.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole([
            RoleType::SUPER_ADMIN->value,
            RoleType::ADMIN->value,
            RoleType::CUSTOMER->value,
            RoleType::MECHANIC->value,
        ]);
    }

    /**
     * Determine whether the user can view the complaint.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        // Super Admin and Admin can view all complaints
        if ($user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value])) {
            return true;
        }

        // Customer can view complaints on their own work orders
        if ($user->hasRole(RoleType::CUSTOMER->value)) {
            return $complaint->workOrder->car->owner_id === $user->id;
        }

        // Mechanic can view complaints where they are assigned
        if ($user->hasRole(RoleType::MECHANIC->value)) {
            return $complaint->complaintServices()->whereHas('mechanicAssignments', function ($query) use ($user) {
                $query->where('mechanic_id', $user->id);
            })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can record a complaint.
     */
    public function create(User $user): bool
    {
        // Only Admin and Super Admin can record complaints
        return $user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]);
    }

    /**
     * Determine whether the user can reassign a complaint.
     */
    public function reassign(User $user, Complaint $complaint): bool
    {
        // Only Admin and Super Admin can reassign complaints
        return $user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]);
    }

    /**
     * Determine whether the user can resolve a complaint.
     */
    public function resolve(User $user, Complaint $complaint): bool
    {
        // Only Admin and Super Admin can resolve complaints
        return $user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]);
    }

    /**
     * Determine whether the user can reject a complaint.
     */
    public function reject(User $user, Complaint $complaint): bool
    {
        // Only Admin and Super Admin can reject complaints
        return $user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]);
    }

    /**
     * Determine whether the user can assign a mechanic to a complaint service.
     */
    public function assignMechanic(User $user, Complaint $complaint): bool
    {
        // Only Admin and Super Admin can assign mechanics
        return $user->hasRole([RoleType::SUPER_ADMIN->value, RoleType::ADMIN->value]);
    }
}
