<?php

namespace App\Listeners;

use App\Events\ComplaintResolved;
use App\Notifications\ComplaintResolvedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendComplaintResolvedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public function handle(ComplaintResolved $event): void
    {
        $complaint = $event->complaint;
        $workOrder = $complaint->workOrder;

        // Load relasi jika belum diload
        $workOrder->loadMissing(['car', 'car.owner']);
        $owner = $workOrder->car->owner;

        if ($owner) {
            $owner->notify(new ComplaintResolvedNotification($complaint));
        }
    }
}
