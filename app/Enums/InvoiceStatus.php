<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Defines the lifecycle states of an Invoice.
 * Typical Flow:
 * UNPAID -> PAID
 * Alternative Flow:
 * UNPAID -> CANCELED (If the work order is canceled or invoice is regenerated)
 */
enum InvoiceStatus: string implements HasColor, HasLabel
{
    /**
     * The invoice has been generated and sent to the customer, but payment has not been received.
     */
    case UNPAID = 'unpaid';

    /**
     * The customer has successfully paid the invoice.
     */
    case PAID = 'paid';

    /**
     * The invoice was voided or canceled.
     */
    case CANCELED = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::PAID => 'Paid',
            self::CANCELED => 'Canceled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::UNPAID => 'warning',
            self::PAID => 'success',
            self::CANCELED => 'danger',
        };
    }
}
