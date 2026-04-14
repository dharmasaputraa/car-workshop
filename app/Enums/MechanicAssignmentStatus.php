<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Defines the lifecycle states of a mechanic's assignment to a specific service task.
 * * Typical Flow:
 * ASSIGNED -> IN_PROGRESS -> COMPLETED
 * * Alternative Flow:
 * ASSIGNED / IN_PROGRESS -> CANCELED (e.g., reassigned to another mechanic or service dropped)
 */
enum MechanicAssignmentStatus: string implements HasColor, HasLabel
{
    /**
     * The mechanic has been assigned to the service task but has not started working on it yet.
     */
    case ASSIGNED = 'assigned';

    /**
     * The mechanic is currently executing the assigned service task.
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * The mechanic has successfully finished the assigned service task.
     */
    case COMPLETED = 'completed';

    /**
     * The assignment was revoked or canceled.
     * Usually occurs if the task is reassigned to another mechanic or the customer cancels the service.
     */
    case CANCELED = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::ASSIGNED => 'Assigned',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELED => 'Canceled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ASSIGNED => 'info',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::CANCELED => 'danger',
        };
    }
}
