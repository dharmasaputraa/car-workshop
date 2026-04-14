<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Defines the lifecycle states of a Work Order.
 *
 * Typical Flow:
 * DRAFT -> DIAGNOSED -> APPROVED -> IN_PROGRESS -> COMPLETED -> INVOICED -> CLOSED
 *
 * Alternative Flows:
 * - APPROVED -> PENDING (e.g., waiting for spare parts or mechanic schedule).
 * - COMPLETED -> COMPLAINED (Customer raised an issue) -> IN_PROGRESS (Rework).
 * - DRAFT/DIAGNOSED -> CANCELED (Aborted before execution).
 */
enum WorkOrderStatus: string implements HasColor, HasLabel
{
    /**
     * Initial state. The work order is created but the vehicle has not been inspected yet.
     * Actions allowed: Edit basic details, Diagnose.
     */
    case DRAFT = 'draft';

    /**
     * Mechanic has inspected the vehicle and proposed required services/parts.
     * Waiting for the customer's approval.
     */
    case DIAGNOSED = 'diagnosed';

    /**
     * Customer has agreed to the diagnosis proposal and estimated costs.
     * Ready to be assigned to a mechanic.
     */
    case APPROVED = 'approved';

    /**
     * Temporarily on hold.
     * Usually means waiting for spare parts to arrive or mechanic availability.
     */
    case PENDING = 'pending';

    /**
     * A mechanic is currently executing the assigned services.
     */
    case IN_PROGRESS = 'in_progress';

    /**
     * All assigned services are finished.
     * The vehicle is ready for pickup and pending final invoice/payment.
     */
    case COMPLETED = 'completed';

    /**
     * Customer is satisfied and invoice has been generated.
     * Waiting for payment.
     */
    case INVOICED = 'invoiced';

    /**
     * Customer reported an issue after the work was marked as completed.
     * Requires re-evaluation or rework by the mechanic.
     */
    case COMPLAINED = 'complained';

    /**
     * Final state. Invoice has been paid and the vehicle is handed over to the customer.
     * Immutable state.
     */
    case CLOSED = 'closed';

    /**
     * Aborted state. The work order was canceled before being completed.
     * Immutable state.
     */
    case CANCELED = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::DIAGNOSED => 'Diagnosed',
            self::APPROVED => 'Approved',
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::INVOICED => 'Invoiced',
            self::COMPLAINED => 'Complained',
            self::CLOSED => 'Closed',
            self::CANCELED => 'Canceled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::DIAGNOSED => 'info',
            self::APPROVED => 'primary',
            self::IN_PROGRESS => 'warning',
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::INVOICED => 'primary',
            self::COMPLAINED => 'danger',
            self::CLOSED => 'success',
            self::CANCELED => 'danger',
        };
    }
}
