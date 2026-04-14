<?php

namespace App\Notifications;

use App\Enums\ServiceItemStatus;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DiagnosisReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public WorkOrder $workOrder)
    {
        $this->onQueue('emails');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $car = $this->workOrder->car;
        $services = $this->workOrder->workOrderServices;
        $appName = config('app.name', 'Laravel');

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $reviewUrl = "{$frontendUrl}/work-orders/" . $this->workOrder->id . "/review";

        $mail = (new MailMessage)
            ->subject("Diagnosis Review Required - {$appName} ({$this->workOrder->order_number})")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Your vehicle with the following details has been diagnosed by our team:")
            ->line("**Vehicle:** {$car->brand} {$car->model} ({$car->year}) - {$car->plate_number}");

        if ($this->workOrder->diagnosis_notes) {
            $mail->line("**Diagnosis Notes:**")
                ->line($this->workOrder->diagnosis_notes);
        }

        $mail->line("**Recommended Services / Repairs:**");

        // Looping data services
        $activeServices = $this->workOrder->workOrderServices->where('status', '!=', ServiceItemStatus::CANCELED->value);

        foreach ($activeServices as $item) {
            $serviceName = $item->service->name ?? 'Unknown service';
            $price = number_format($item->price, 0, ',', '.');
            $mail->line("- {$serviceName} (Rp {$price})");
        }

        return $mail
            ->action('Review & Approve Diagnosis', $reviewUrl)
            ->line('Please review and approve the above actions so our mechanics can begin working on your vehicle.')
            ->line('If you have any questions, feel free to contact us.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'order_number' => $this->workOrder->order_number,
            'message' => 'Diagnosis completed for ' . $this->workOrder->car->plate_number,
        ];
    }
}
