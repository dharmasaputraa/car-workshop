<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\InvoiceService;
use Exception;

class CancelInvoiceAction
{
    public function __construct(
        protected InvoiceRepositoryInterface $invoiceRepository,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Cancel an invoice.
     * Transitions invoice from DRAFT or UNPAID to CANCELED.
     *
     * @param string $invoiceId
     * @return Invoice
     * @throws Exception
     */
    public function execute(string $invoiceId): Invoice
    {
        $invoice = $this->invoiceRepository->findById($invoiceId);

        // Validate invoice can be canceled
        $this->invoiceService->validateCanCancel($invoice);

        // Update status to CANCELED
        $invoice = $this->invoiceRepository->updateStatus(
            $invoice,
            InvoiceStatus::CANCELED->value
        );

        return $invoice;
    }
}
