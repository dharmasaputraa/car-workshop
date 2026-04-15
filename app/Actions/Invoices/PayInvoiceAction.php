<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Events\PaymentReceived;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\InvoiceService;
use Exception;

class PayInvoiceAction
{
    public function __construct(
        protected InvoiceRepositoryInterface $invoiceRepository,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Mark an invoice as paid.
     * Transitions invoice from UNPAID to PAID.
     *
     * @param string $invoiceId
     * @param string|null $paymentMethod
     * @param string|null $paymentReference
     * @param string|null $paymentNotes
     * @return Invoice
     * @throws Exception
     */
    public function execute(
        string $invoiceId,
        ?string $paymentMethod = null,
        ?string $paymentReference = null,
        ?string $paymentNotes = null
    ): Invoice {
        $invoice = $this->invoiceRepository->findById($invoiceId);

        // Validate invoice can be paid
        $this->invoiceService->validateCanPay($invoice);

        // Update status to PAID
        $invoice = $this->invoiceRepository->updateStatus(
            $invoice,
            InvoiceStatus::PAID->value
        );

        // Optionally, you could store payment details in a separate payment table
        // For now, we just mark the invoice as paid

        PaymentReceived::dispatch($invoice);

        return $invoice;
    }
}
