<?php

namespace App\Actions\Invoices;

use App\Models\Invoice;
use App\Services\InvoiceService;

class GenerateInvoiceAction
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Generate an invoice from a work order.
     *
     * @param string $workOrderId
     * @param float $discount
     * @param float $tax
     * @param string|null $dueDate
     * @return Invoice
     */
    public function execute(
        string $workOrderId,
        float $discount = 0.0,
        float $tax = 0.0,
        ?string $dueDate = null
    ): Invoice {
        return $this->invoiceService->generateInvoice(
            $workOrderId,
            $discount,
            $tax,
            $dueDate
        );
    }
}
