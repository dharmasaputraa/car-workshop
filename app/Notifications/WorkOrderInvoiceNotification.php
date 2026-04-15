<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Ingat: Hapus 'implements ShouldQueue' di Listener Anda agar tidak double-queue,
// dan biarkan class Notification ini saja yang masuk antrean.
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

        $invoice = $this->workOrder->invoice;

        $dummyInvoiceUrl = rtrim(config('app.frontend_url'), '/') . '/invoices/dummy-' . $invoice->id;

        $formattedTotal = 'Rp ' . number_format($invoice->total, 0, ',', '.');
        $dueDate = $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A';

        return (new MailMessage)
            ->subject("Your Invoice {$invoice->invoice_number} - {$appName}")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("Thank you for trusting us with your vehicle. The service is complete and your vehicle is ready for pickup.")
            ->line("Here is the summary of your invoice:")
            ->line("**Invoice Number:** {$invoice->invoice_number}")
            ->line("**Work Order:** {$this->workOrder->order_number}")
            ->line("**Total Due:** {$formattedTotal}")
            ->line("**Due Date:** {$dueDate}")
            ->action('View & Download Invoice', $dummyInvoiceUrl)
            ->line('Please proceed with the payment at the cashier when you pick up your vehicle.')
            ->success();
    }
}
