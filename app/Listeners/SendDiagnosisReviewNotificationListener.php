<?php

namespace App\Listeners;

use App\Events\WorkOrderDiagnosed;
use App\Notifications\DiagnosisReviewNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendDiagnosisReviewNotificationListener implements ShouldQueue
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
    public function handle(WorkOrderDiagnosed $event): void
    {
        $workOrder = $event->workOrder;

        $owner = $workOrder->car->owner;

        if ($owner) {
            $owner->notify(new DiagnosisReviewNotification($workOrder));
        }
    }
}
