<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use App\Actions\Invoices\CancelInvoiceAction;
use App\Actions\Invoices\GenerateInvoiceAction;
use App\Actions\Invoices\PayInvoiceAction;
use App\Actions\Invoices\SendInvoiceAction;
use App\DTOs\Invoice\GenerateInvoiceData;
use App\DTOs\Invoice\PayInvoiceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Invoice\PayInvoiceRequest;
use App\Http\Resources\Api\V1\Invoice\InvoiceResource;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

#[Group('Billing - Invoices')]
class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceRepositoryInterface $invoiceRepository
    ) {}

    /**
     * List Invoices
     *
     * Retrieve a paginated list of invoices with filtering, sorting, and eager loading support.
     */
    #[QueryParameter('filter[status]', description: 'Filter by invoice status (e.g., draft, unpaid, paid)', type: 'string', example: 'unpaid')]
    #[QueryParameter('filter[work_order_id]', description: 'Filter by exact Work Order UUID', type: 'string')]
    #[QueryParameter('filter[invoice_number]', description: 'Filter by partial invoice number', type: 'string', example: 'INV-2023')]
    #[QueryParameter('sort', description: 'Sort by field. Options: invoice_number, created_at, due_date, status, total', type: 'string', example: '-created_at')]
    #[QueryParameter('include', description: 'Include relations: workOrder, workOrder.car, workOrder.car.owner', type: 'string', example: 'workOrder,workOrder.car')]
    #[QueryParameter('per_page', description: 'Number of results per page', type: 'integer', example: 15)]
    #[QueryParameter('page', description: 'Page number', type: 'integer', example: 1)]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Invoice::class);

        return InvoiceResource::collection(
            $this->invoiceRepository->getPaginatedInvoices()
        );
    }

    /**
     * Generate Invoice
     *
     * Generate a new invoice from a completed work order.
     * The invoice starts in DRAFT status and needs to be sent to the customer.
     */
    public function generate(GenerateInvoiceData $dto, GenerateInvoiceAction $action): JsonResponse
    {
        Gate::authorize('create', Invoice::class);

        $invoice = $action->execute(
            $dto->workOrderId,
            $dto->discount ?? 0.0,
            $dto->tax ?? 0.0,
            $dto->dueDate
        );

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get Invoice
     *
     * Retrieve a single invoice detail by its UUID.
     */
    #[QueryParameter('include', description: 'Include relations: workOrder, workOrder.car, workOrder.car.owner, workOrder.workOrderServices', type: 'string', example: 'workOrder,workOrder.car')]
    public function show(string $id): InvoiceResource
    {
        $invoice = $this->invoiceRepository->findById($id);
        Gate::authorize('view', $invoice);

        return new InvoiceResource($invoice);
    }

    /**
     * Send Invoice
     *
     * Send an invoice to the customer.
     * Transitions invoice from DRAFT to UNPAID and dispatches email notification.
     */
    public function send(string $id, SendInvoiceAction $action): InvoiceResource
    {
        $invoice = $this->invoiceRepository->findById($id);
        Gate::authorize('send', $invoice);

        $invoice = $action->execute($id);

        return new InvoiceResource($invoice);
    }

    /**
     * Pay Invoice
     *
     * Mark an invoice as paid.
     * Transitions invoice from UNPAID to PAID.
     */
    public function pay(PayInvoiceRequest $request, string $id, PayInvoiceAction $action): InvoiceResource
    {
        $invoice = $this->invoiceRepository->findById($id);
        Gate::authorize('pay', $invoice);

        $dto = PayInvoiceData::fromRequest($request);

        $invoice = $action->execute(
            $id,
            $dto->paymentMethod,
            $dto->paymentReference,
            $dto->paymentNotes
        );

        return new InvoiceResource($invoice);
    }

    /**
     * Cancel Invoice
     *
     * Cancel an invoice.
     * Transitions invoice from DRAFT or UNPAID to CANCELED.
     */
    public function cancel(string $id, CancelInvoiceAction $action): InvoiceResource
    {
        $invoice = $this->invoiceRepository->findById($id);
        Gate::authorize('cancel', $invoice);

        $invoice = $action->execute($id);

        return new InvoiceResource($invoice);
    }
}
