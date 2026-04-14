<?php

namespace App\Listeners;

use App\Events\WorkOrderApproved;
use App\Notifications\WorkOrderApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWorkOrderApprovedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public function handle(WorkOrderApproved $event): void
    {
        $workOrder = $event->workOrder;
        $creator = $workOrder->creator;

        if ($creator) {
            $creator->notify(new WorkOrderApprovedNotification($workOrder));
        }
    }
}
