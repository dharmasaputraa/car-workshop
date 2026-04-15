<?php

namespace App\Http\Controllers\Api\V1\WorkOrder;

use App\Actions\WorkOrders\ApproveWorkOrderProposalAction;
use App\Actions\WorkOrders\AssignMechanicToServiceAction;
use App\Actions\WorkOrders\CancelMechanicAssignmentAction;
use App\Actions\WorkOrders\CancelWorkOrderAction;
use App\Actions\WorkOrders\CompleteWorkOrderAction;
use App\Actions\WorkOrders\CompleteWorkOrderServiceAction;
use App\Actions\WorkOrders\CreateWorkOrderAction;
use App\Actions\WorkOrders\DeleteWorkOrderAction;
use App\Actions\WorkOrders\DiagnoseWorkOrderAction;
use App\Actions\WorkOrders\MarkWorkOrderAsInvoicedAction;
use App\Actions\WorkOrders\RecordWorkOrderComplaintAction;
use App\Actions\WorkOrders\StartWorkOrderServiceAction;
use App\Actions\WorkOrders\UpdateWorkOrderAction;
use App\DTOs\WorkOrder\AssignMechanicData;
use App\Http\Requests\Api\V1\Complaint\RecordComplaintRequest;
use App\DTOs\WorkOrder\CreateWorkOrderData;
use App\DTOs\WorkOrder\DiagnoseWorkOrderData;
use App\DTOs\WorkOrder\UpdateWorkOrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrder\AssignMechanicRequest;
use App\Http\Requests\Api\V1\WorkOrder\CompleteWorkOrderServiceRequest;
use App\Http\Requests\Api\V1\WorkOrder\DiagnoseWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrder\StartWorkOrderServiceRequest;
use App\Http\Requests\Api\V1\WorkOrder\StoreWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrder\UpdateWorkOrderRequest;
use App\Http\Resources\Api\V1\Mechanic\MechanicAssignmentResource;
use App\Http\Resources\Api\V1\WorkOrder\WorkOrderResource;
use App\Http\Resources\Api\V1\WorkOrder\WorkOrderServiceResource;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

#[Group('Workshop Ops - WorkOrders')]
class WorkOrderController extends Controller
{
    public function __construct(
        protected WorkOrderRepositoryInterface $workOrderRepository
    ) {}

    /**
     * List Work Orders
     *
     * Retrieve a paginated list of work orders with filtering, sorting, and eager loading support.
     */
    #[QueryParameter('filter[status]', description: 'Filter by work order status (e.g., draft, diagnosed)', type: 'string', example: 'draft')]
    #[QueryParameter('filter[car_id]', description: 'Filter by exact Car UUID', type: 'string')]
    #[QueryParameter('filter[order_number]', description: 'Filter by partial order number', type: 'string', example: 'WO-2023')]
    #[QueryParameter('sort', description: 'Sort by field. Options: order_number, created_at, status', type: 'string', example: '-created_at')]
    #[QueryParameter('include', description: 'Include relations: car, car.owner, creator, workOrderServices, workOrderServices.service', type: 'string', example: 'car,workOrderServices')]
    #[QueryParameter('per_page', description: 'Number of results per page', type: 'integer', example: 15)]
    #[QueryParameter('page', description: 'Page number', type: 'integer', example: 1)]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', WorkOrder::class);

        return WorkOrderResource::collection(
            $this->workOrderRepository->getPaginatedWorkOrders()
        );
    }

    /**
     * Create Work Order
     *
     * Create a new work order draft for a specific car.
     */
    public function store(StoreWorkOrderRequest $request, CreateWorkOrderAction $action): JsonResponse
    {
        Gate::authorize('create', WorkOrder::class);

        $dto = CreateWorkOrderData::fromRequest($request);
        $workOrder = $action->execute($dto, Auth::id());

        return (new WorkOrderResource($workOrder))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get Work Order
     *
     * Retrieve a single work order detail by its UUID.
     */
    #[QueryParameter('include', description: 'Include relations: car, car.owner, creator, workOrderServices, workOrderServices.service', type: 'string', example: 'car,workOrderServices')]
    public function show(string $id): WorkOrderResource
    {
        $workOrder = $this->workOrderRepository->findById($id);
        Gate::authorize('view', $workOrder);

        return new WorkOrderResource($workOrder);
    }

    /**
     * Update Work Order
     *
     * Update an existing work order's base information (Only allowed when status is DRAFT).
     */
    public function update(UpdateWorkOrderRequest $request, string $id, UpdateWorkOrderAction $action): WorkOrderResource
    {
        $workOrder = $this->workOrderRepository->findById($id);
        Gate::authorize('update', $workOrder);

        $dto = UpdateWorkOrderData::fromRequest($request);

        return new WorkOrderResource(
            $action->execute($id, $dto)
        );
    }

    /**
     * Delete Work Order
     *
     * Hard or soft delete a work order draft. Cannot delete if in progress or completed.
     */
    public function destroy(string $id, DeleteWorkOrderAction $action): Response
    {
        $workOrder = $this->workOrderRepository->findById($id);
        Gate::authorize('delete', $workOrder);

        $action->execute($id);

        return response()->noContent();
    }

    /**
     * Cancel Work Order
     *
     * Cancel a work order (changes status to CANCELED).
     * Cannot cancel if in progress or completed.
     */
    public function cancel(string $id, CancelWorkOrderAction $action): JsonResponse
    {
        $workOrder = $this->workOrderRepository->findById($id);
        Gate::authorize('cancel', $workOrder);

        $workOrder = $action->execute($id);

        return (new WorkOrderResource($workOrder))
            ->response()
            ->setStatusCode(200);
    }

    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE CUSTOM ENDPOINTS (STATE MACHINE)
    |--------------------------------------------------------------------------
    */

    /**
     * Diagnose Work Order
     *
     * Submit mechanic diagnosis notes and propose required services/spareparts.
     * Transitions state from DRAFT to DIAGNOSED.
     */
    public function diagnose(DiagnoseWorkOrderRequest $request, string $id, DiagnoseWorkOrderAction $action): WorkOrderResource
    {
        $workOrder = $this->workOrderRepository->findById($id);
        Gate::authorize('diagnose', $workOrder);

        $dto = DiagnoseWorkOrderData::fromRequest($request);

        return new WorkOrderResource(
            $action->execute($id, $dto->services, $dto->diagnosisNotes)
        );
    }

    /**
     * Approve Work Order
     *
     * Customer approves the proposed diagnosis and services.
     * Transitions state from DIAGNOSED to APPROVED.
     */
    public function approve(string $id, ApproveWorkOrderProposalAction $action): WorkOrderResource
    {
        $workOrder = $this->workOrderRepository->findById($id);
        Gate::authorize('approve', $workOrder);

        return new WorkOrderResource(
            $action->execute($id)
        );
    }

    /**
     * Complete Work Order
     *
     * Mark the entire work order as completed. Fails if any individual service is not done.
     * Transitions state from IN_PROGRESS to COMPLETED.
     */
    public function complete(string $id, CompleteWorkOrderAction $action): WorkOrderResource
    {
        $workOrder = $this->workOrderRepository->findById($id);
        Gate::authorize('complete', $workOrder);

        return new WorkOrderResource(
            $action->execute($id)
        );
    }

    /**
     * Assign Mechanic
     *
     * Assign a mechanic to a specific service item within a work order.
     * Transitions the service item status to in_progress.
     */
    public function assignMechanic(AssignMechanicRequest $request, string $workOrderServiceId, AssignMechanicToServiceAction $action): JsonResponse
    {
        // Otorisasi spesifik untuk assignment (bisa dipisah ke Policy tersendiri nanti)
        Gate::authorize('assignMechanic', WorkOrder::class);

        $dto = AssignMechanicData::fromRequest($request);
        $assignment = $action->execute($workOrderServiceId, $dto->mechanicId);

        return (new MechanicAssignmentResource($assignment))->response()->setStatusCode(201);
    }

    /**
     * Cancel Mechanic Assignment
     *
     * Cancel a specific mechanic assignment.
     */
    public function cancelMechanicAssignment(string $assignmentId, CancelMechanicAssignmentAction $action): JsonResponse
    {
        Gate::authorize('cancelMechanicAssignment', WorkOrder::class);

        $assignment = $action->execute($assignmentId);

        // Sama seperti assign, gunakan Resource jika ada
        return (new MechanicAssignmentResource($assignment))->response()->setStatusCode(200);
    }

    /**
     * Start Work Order Service
     *
     * Start working on a specific service item. Transitions the service status from ASSIGNED to IN_PROGRESS
     * and cascades the status change to all active mechanic assignments for that service.
     */
    public function startService(StartWorkOrderServiceRequest $request, string $workOrderServiceId, StartWorkOrderServiceAction $action): JsonResponse
    {
        Gate::authorize('startWorkOrderService', WorkOrder::class);

        $woService = $action->execute($workOrderServiceId);

        return (new WorkOrderServiceResource($woService))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Complete Work Order Service
     *
     * Mark a specific service item as completed. Transitions the service status from IN_PROGRESS to COMPLETED
     * and cascades the status change to all active mechanic assignments for that service (sets completed_at).
     */
    public function completeService(CompleteWorkOrderServiceRequest $request, string $workOrderServiceId, CompleteWorkOrderServiceAction $action): JsonResponse
    {
        Gate::authorize('completeWorkOrderService', WorkOrder::class);

        $woService = $action->execute($workOrderServiceId);

        return (new WorkOrderServiceResource($woService))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Mark Work Order as Invoiced
     *
     * The admin marks the customer as satisfied and issues an invoice.
     * Transitions state from COMPLETED to INVOICED.
     */
    public function markAsInvoiced(string $id, MarkWorkOrderAsInvoicedAction $action): WorkOrderResource
    {
        $workOrder = $this->workOrderRepository->findById($id);
        Gate::authorize('markAsInvoiced', $workOrder);

        return new WorkOrderResource(
            $action->execute($id)
        );
    }

    /**
     * Record Complaint
     *
     * Record a complaint on a completed work order.
     * Transitions state from COMPLETED to COMPLAINED.
     */
    public function recordComplaint(RecordComplaintRequest $request, string $id, RecordWorkOrderComplaintAction $action): WorkOrderResource
    {
        $workOrder = $this->workOrderRepository->findById($id);
        Gate::authorize('recordComplaint', $workOrder);

        $dto = \App\DTOs\Complaint\RecordComplaintData::fromRequest($request);

        return new WorkOrderResource(
            $action->execute($id, $dto->description, $dto->services)
        );
    }
}
