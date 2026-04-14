<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Defines the lifecycle states of a Customer Complaint.
 * Typical Flow:
 * PENDING -> IN_PROGRESS -> RESOLVED
 * Alternative Flow:
 * PENDING -> REJECTED (If the complaint is deemed invalid or out of warranty)
 */
enum ComplaintStatus: string implements HasColor, HasLabel
{
    /**
     * Complaint has been submitted by the customer but not yet reviewed.
     */
    case PENDING = 'pending';

    /**
     * The workshop is currently working on resolving the complaint (reworking the car).
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * The issue has been fixed and accepted by the customer.
     */
    case RESOLVED = 'resolved';

    /**
     * The complaint is denied (e.g., issue caused by customer negligence, not the service).
     */
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::RESOLVED => 'Resolved',
            self::REJECTED => 'Rejected',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::IN_PROGRESS => 'info',
            self::RESOLVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
