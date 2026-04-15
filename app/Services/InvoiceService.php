<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\ServiceItemStatus;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class InvoiceService
{
    public function __construct(
        protected InvoiceRepositoryInterface $invoiceRepository,
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    /**
     * Calculate invoice totals from work order services.
     *
     * @param string $workOrderId
     * @return array{subtotal: float, total: float}
     * @throws Exception
     */
    public function calculateInvoiceTotals(string $workOrderId): array
    {
        $workOrder = $this->workOrderRepository->findById($workOrderId);

        // Only include non-canceled services
        $subtotal = $workOrder->workOrderServices
            ->filter(fn($service) => $service->status !== ServiceItemStatus::CANCELED)
            ->sum('price');

        $total = $subtotal; // Can add tax and discount logic later

        return [
            'subtotal' => (float) number_format($subtotal, 2, '.', ''),
            'total' => (float) number_format($total, 2, '.', ''),
        ];
    }

    /**
     * Generate a unique invoice number.
     *
     * @return string
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $year = date('Y');
        $month = date('m');

        // Get the last invoice number for this month
        $lastInvoice = Invoice::where('invoice_number', 'like', "{$prefix}{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$year}{$month}-{$newNumber}";
    }

    /**
     * Generate invoice from work order.
     *
     * @param string $workOrderId
     * @param float $discount
     * @param float $tax
     * @param string|null $dueDate
     * @return Invoice
     * @throws Exception
     */
    public function generateInvoice(
        string $workOrderId,
        float $discount = 0.0,
        float $tax = 0.0,
        ?string $dueDate = null
    ): Invoice {
        $workOrder = $this->workOrderRepository->findById($workOrderId);

        // Check if work order has an invoice already
        $existingInvoice = $this->invoiceRepository->findByWorkOrderId($workOrderId);
        if ($existingInvoice) {
            throw new Exception("Work Order already has an invoice.");
        }

        // Calculate totals
        $totals = $this->calculateInvoiceTotals($workOrderId);

        // Generate invoice number
        $invoiceNumber = $this->generateInvoiceNumber();

        // Set default due date (7 days from now) if not provided
        if (!$dueDate) {
            $dueDate = now()->addDays(7)->toDateString();
        }

        // Create invoice
        $invoice = $this->invoiceRepository->create([
            'invoice_number' => $invoiceNumber,
            'work_order_id' => $workOrderId,
            'subtotal' => $totals['subtotal'],
            'discount' => $discount,
            'tax' => $tax,
            'total' => $totals['subtotal'] - $discount + $tax,
            'status' => InvoiceStatus::DRAFT->value,
            'due_date' => $dueDate,
        ]);

        return $invoice;
    }

    /**
     * Validate invoice can be sent.
     *
     * @param Invoice $invoice
     * @return void
     * @throws Exception
     */
    public function validateCanSend(Invoice $invoice): void
    {
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw new Exception("Only invoices in DRAFT status can be sent.");
        }
    }

    /**
     * Validate invoice can be paid.
     *
     * @param Invoice $invoice
     * @return void
     * @throws Exception
     */
    public function validateCanPay(Invoice $invoice): void
    {
        if ($invoice->status !== InvoiceStatus::UNPAID) {
            throw new Exception("Only unpaid invoices can be marked as paid.");
        }
    }

    /**
     * Validate invoice can be canceled.
     *
     * @param Invoice $invoice
     * @return void
     * @throws Exception
     */
    public function validateCanCancel(Invoice $invoice): void
    {
        if ($invoice->status === InvoiceStatus::PAID) {
            throw new Exception("Paid invoices cannot be canceled.");
        }
    }
}
