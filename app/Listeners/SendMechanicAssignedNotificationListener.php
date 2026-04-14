<?php

namespace App\Listeners;

use App\Events\MechanicAssigned;
use App\Notifications\MechanicAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMechanicAssignedNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public function handle(MechanicAssigned $event): void
    {
        $assignment = $event->assignment;
        $mechanic = $assignment->mechanic;

        if ($mechanic) {
            $mechanic->notify(new MechanicAssignedNotification($assignment));
        }
    }
}
