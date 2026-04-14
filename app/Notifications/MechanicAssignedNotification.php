<?php

namespace App\Notifications;

use App\Models\MechanicAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MechanicAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public MechanicAssignment $assignment)
    {
        $this->onQueue('emails');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workOrder = $this->assignment->workOrderService->workOrder;
        $car = $workOrder->car;
        $service = $this->assignment->workOrderService->service;
        $appName = config('app.name', 'Laravel');

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $taskUrl = "{$frontendUrl}/mechanic/tasks/" . $this->assignment->id;

        return (new MailMessage)
            ->subject("New Task Assigned - {$appName}")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("You have been assigned to a new service task.")
            ->line("**Work Order:** {$workOrder->order_number}")
            ->line("**Vehicle:** {$car->brand} {$car->model} ({$car->year}) - {$car->plate_number}")
            ->line("**Service Task:** {$service->name}")
            ->line("Please review the task details and begin work when ready.")
            ->action('View Task Details', $taskUrl);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'message' => 'You have been assigned to a new task for ' . $this->assignment->workOrderService->workOrder->car->plate_number,
        ];
    }
}
