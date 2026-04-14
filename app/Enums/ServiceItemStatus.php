<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Defines the lifecycle states of an individual service item (WorkOrderService or ComplaintService).
 * Typical Flow:
 * PENDING -> ASSIGNED -> IN_PROGRESS -> COMPLETED
 */
enum ServiceItemStatus: string implements HasColor, HasLabel
{
    /**
     * The service is listed but no mechanic has started working on it yet.
     */
    case PENDING = 'pending';

    /**
     * A mechanic has been assigned to this service.
     * This state typically triggers notification emails to the technician.
     */
    case ASSIGNED = 'assigned';

    /**
     * A mechanic is currently executing this specific service.
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * The mechanic has finished this specific service.
     */
    case COMPLETED = 'completed';


    case COMPLAINED = 'complained';

    /**
     * The service was removed or canceled from the work order.
     */
    case CANCELED = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ASSIGNED => 'Assigned',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::COMPLAINED => 'Complained',
            self::CANCELED => 'Canceled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::ASSIGNED => 'warning',
            self::IN_PROGRESS => 'primary',
            self::COMPLETED => 'success',
            self::COMPLAINED => 'danger',
            self::CANCELED => 'danger',
        };
    }
}
