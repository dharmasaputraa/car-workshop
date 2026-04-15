<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice)
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
        $workOrder = $this->invoice->workOrder;

        $formattedTotal = 'Rp ' . number_format($this->invoice->total, 0, ',', '.');

        return (new MailMessage)
            ->subject("Payment Received - Invoice {$this->invoice->invoice_number}")
            ->greeting("Thank you, {$notifiable->name}!")
            ->line("We have received your payment for Invoice **{$this->invoice->invoice_number}**.")
            ->line("Payment Details:")
            ->line("- **Amount Paid:** {$formattedTotal}")
            ->line("- **Work Order:** {$workOrder->order_number}")
            ->line("- **Status:** PAID")
            ->line("Your transaction is now complete. We hope to see you again for your next vehicle service!")
            ->line("You can keep this email as your official receipt.");
    }
}
