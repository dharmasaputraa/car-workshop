<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Events\SendWorkOrderInvoice;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\InvoiceService;
use Exception;

class SendInvoiceAction
{
    public function __construct(
        protected InvoiceRepositoryInterface $invoiceRepository,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Send an invoice to the customer.
     * Transitions invoice from DRAFT to UNPAID and dispatches email event.
     *
     * @param string $invoiceId
     * @return Invoice
     * @throws Exception
     */
    public function execute(string $invoiceId): Invoice
    {
        $invoice = $this->invoiceRepository->findById($invoiceId);

        // Validate invoice can be sent
        $this->invoiceService->validateCanSend($invoice);

        // Update status to UNPAID
        $invoice = $this->invoiceRepository->updateStatus(
            $invoice,
            InvoiceStatus::UNPAID->value
        );

        // Dispatch email event
        SendWorkOrderInvoice::dispatch($invoice->workOrder);

        return $invoice;
    }
}
