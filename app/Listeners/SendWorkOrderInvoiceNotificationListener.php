<?php

namespace App\Listeners;

use App\Events\SendWorkOrderInvoice;
use App\Notifications\WorkOrderInvoiceNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWorkOrderInvoiceNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public function handle(SendWorkOrderInvoice $event): void
    {
        $workOrder = $event->workOrder;

        // Load relasi jika belum diload
        $workOrder->loadMissing(['car', 'car.owner']);
        $owner = $workOrder->car->owner;

        if ($owner) {
            $owner->notify(new WorkOrderInvoiceNotification($workOrder));
        }
    }
}
