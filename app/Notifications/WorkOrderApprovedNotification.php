<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkOrderApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
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
        $appName = config('app.name', 'Laravel');

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $assignmentUrl = "{$frontendUrl}/admin/work-orders/" . $this->workOrder->id . "/assign";

        return (new MailMessage)
            ->subject("Work Order Approved - {$appName} ({$this->workOrder->order_number})")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("The customer has approved the diagnosis proposal for Work Order **{$this->workOrder->order_number}**.")
            ->line("**Vehicle Details:** {$car->brand} {$car->model} ({$car->year}) - {$car->plate_number}")
            ->line("Please proceed to the dashboard to assign the required mechanics to the approved services.")
            ->action('Assign Mechanics', $assignmentUrl)
            ->line('Thank you!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'message' => 'Work Order ' . $this->workOrder->order_number . ' has been approved. Ready for mechanic assignment.',
        ];
    }
}
