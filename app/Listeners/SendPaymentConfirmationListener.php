<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Notifications\PaymentConfirmationNotification;

class SendPaymentConfirmationListener
{
    public function handle(PaymentReceived $event): void
    {
        $owner = $event->invoice->workOrder->car->owner;

        if ($owner) {
            $owner->notify(new PaymentConfirmationNotification($event->invoice));
        }
    }
}
