<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkOrderCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder)
    {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $car = $this->workOrder->car;
        $appName = config('app.name', 'Workshop Ops');

        return (new MailMessage)
            ->subject("Your Vehicle is Ready for Pickup - {$appName} ({$this->workOrder->order_number})")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Good news! The work on your vehicle has been completed by our mechanics.")
            ->line("**Vehicle Details:** {$car->brand} {$car->model} ({$car->year}) - {$car->plate_number}")
            ->line("You may now visit our workshop to pick up your vehicle.")
            ->line("Final confirmation and payment (invoice) can be completed at our cashier upon your arrival.")
            ->line('Thank you for your trust in us!');
    }
}
