<?php

namespace App\Listeners;

use App\Events\WorkOrderCompleted;
use App\Notifications\WorkOrderCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWorkOrderCompletedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WorkOrderCompleted $event): void
    {
        $workOrder = $event->workOrder;

        // Retrieve the owner via the WorkOrder -> Car -> Owner relationship
        $owner = $workOrder->car->owner;

        if ($owner) {
            $owner->notify(new WorkOrderCompletedNotification($workOrder));
        }
    }
}
