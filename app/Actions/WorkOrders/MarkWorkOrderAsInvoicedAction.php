<?php

namespace App\Actions\WorkOrders;

use App\Actions\Invoices\GenerateInvoiceAction;
use App\Enums\InvoiceStatus;
use App\Enums\WorkOrderStatus;
use App\Events\SendWorkOrderInvoice;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;

class MarkWorkOrderAsInvoicedAction
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository,
        protected GenerateInvoiceAction $generateInvoiceAction,
        protected InvoiceRepositoryInterface $invoiceRepository
    ) {}

    public function execute(string $workOrderId): WorkOrder
    {
        $workOrder = $this->workOrderRepository->findById($workOrderId);

        if ($workOrder->status !== WorkOrderStatus::COMPLETED) {
            throw new Exception("Only Work Orders with COMPLETED status can be issued Invoices.");
        }

        // Generate invoice for the work order (creates as DRAFT)
        try {
            $invoice = $this->generateInvoiceAction->execute($workOrderId);
        } catch (Exception $e) {
            throw new Exception("Failed to generate invoice: " . $e->getMessage());
        }

        // Immediately send the invoice (transition to UNPAID)
        $invoice = $this->invoiceRepository->updateStatus(
            $invoice,
            InvoiceStatus::UNPAID->value
        );

        // Dispatch email notification to customer
        SendWorkOrderInvoice::dispatch($workOrder);

        // Update work order status to INVOICED
        $workOrder = $this->workOrderRepository->updateStatus(
            $workOrder,
            WorkOrderStatus::INVOICED->value
        );

        // Load the invoice relationship
        $workOrder = $this->workOrderRepository->loadMissingRelations(
            $workOrder,
            ['invoice']
        );

        return $workOrder;
    }
}
