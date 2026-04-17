<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Complaint $complaint)
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

        $workOrder = $this->complaint->workOrder;
        $invoice = $workOrder->invoices()->where('complaint_id', $this->complaint->id)->first();

        $formattedTotal = $invoice ? 'Rp ' . number_format($invoice->total, 0, ',', '.') : 'Rp 0';
        $invoiceNumber = $invoice ? $invoice->invoice_number : 'N/A';
        $dueDate = $invoice && $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A';

        return (new MailMessage)
            ->subject("Complaint Resolved - {$workOrder->order_number} - {$appName}")
            ->greeting("Hello, {$notifiable->name}!")
            ->line("We're pleased to inform you that your complaint has been resolved.")
            ->line("Here are the details:")
            ->line("**Work Order:** {$workOrder->order_number}")
            ->line("**Complaint:** {$this->complaint->description}")
            ->line("**Status:** Resolved")
            ->line("**Invoice Number:** {$invoiceNumber}")
            ->line("**Total Due:** {$formattedTotal}")
            ->line("**Due Date:** {$dueDate}")
            ->line("Your vehicle is ready for pickup. Please proceed with the payment at the cashier if applicable.")
            ->success();
    }
}
