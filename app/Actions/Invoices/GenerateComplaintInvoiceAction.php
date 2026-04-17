<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Exception;

class GenerateComplaintInvoiceAction
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected InvoiceRepositoryInterface $invoiceRepository
    ) {}

    /**
     * Generate a complaint invoice from a resolved complaint.
     * Creates a separate invoice for complaint/rework services.
     *
     * @param string $complaintId
     * @param float $discount
     * @param float $tax
     * @param string|null $dueDate
     * @return Invoice
     * @throws Exception
     */
    public function execute(
        string $complaintId,
        float $discount = 0.0,
        float $tax = 0.0,
        ?string $dueDate = null
    ): Invoice {
        // Generate invoice for the complaint (creates as DRAFT)
        try {
            $invoice = $this->invoiceService->generateComplaintInvoice(
                $complaintId,
                $discount,
                $tax,
                $dueDate
            );
        } catch (Exception $e) {
            throw new Exception("Failed to generate complaint invoice: " . $e->getMessage());
        }

        // Immediately send the invoice (transition to UNPAID)
        $invoice = $this->invoiceRepository->updateStatus(
            $invoice,
            InvoiceStatus::UNPAID->value
        );

        return $invoice;
    }
}
