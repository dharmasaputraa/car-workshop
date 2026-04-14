<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkOrderInvoiceNotification extends Notification implements ShouldQueue
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
        $appName = config('app.name', 'Workshop Ops');
        $dummyInvoiceUrl = config('app.frontend_url') . '/invoices/dummy-' . $this->workOrder->id;

        return (new MailMessage)
            ->subject("Your Invoice for Work Order {$this->workOrder->order_number} - {$appName}")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Thank you for your confirmation. Your vehicle is ready for pickup.")
            ->line("Here are the invoice details for your vehicle service.")
            ->action('Download Invoice (PDF)', $dummyInvoiceUrl)
            ->line('Please proceed with payment at the cashier when you pick up your vehicle.');
    }
}
