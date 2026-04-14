<?php

namespace App\Http\Controllers\Api\V1\Mechanic;

use App\DTOs\Mechanic\MechanicAssignmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mechanic\CompleteMechanicAssignmentRequest;
use App\Http\Requests\Api\V1\Mechanic\StartMechanicAssignmentRequest;
use App\Http\Requests\Api\V1\Mechanic\StoreMechanicAssignmentRequest;
use App\Http\Requests\Api\V1\Mechanic\UpdateMechanicAssignmentRequest;
use App\Http\Resources\Api\V1\Mechanic\MechanicAssignmentResource;
use App\Models\MechanicAssignment;
use App\Services\MechanicAssignment\MechanicAssignmentService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Knuckles\Scribe\Attributes\UrlParam;

#[Group('Workshop Ops - Mechanic Assignments')]
class MechanicAssignmentController extends Controller
{
    public function __construct(
        private readonly MechanicAssignmentService $assignmentService
    ) {}

    /**
     * List Mechanic Assignments
     *
     * Retrieve a paginated list of mechanic assignments.
     */
    #[QueryParameter('filter[status]', description: 'Filter by assignment status', type: 'string', example: 'in_progress')]
    #[QueryParameter('filter[mechanic_id]', description: 'Filter by mechanic UUID', type: 'string', example: 'uuid-here')]
    #[QueryParameter('filter[work_order_service_id]', description: 'Filter by specific work order service UUID', type: 'string', example: 'uuid-here')]
    #[QueryParameter('sort', description: 'Sort by field. Options: assigned_at, completed_at, created_at', type: 'string', example: '-assigned_at')]
    #[QueryParameter('include', description: 'Include relations: mechanic, workOrderService, workOrderService.service', type: 'string', example: 'mechanic')]
    #[QueryParameter('per_page', description: 'Number of results per page', type: 'integer', example: 15)]
    #[QueryParameter('page', description: 'Page number', type: 'integer', example: 1)]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', MechanicAssignment::class);

        return MechanicAssignmentResource::collection(
            $this->assignmentService->getPaginatedAssignments()
        );
    }

    /**
     * Get Mechanic Assignment
     *
     * Retrieve details of a specific assignment.
     */
    #[QueryParameter('include', description: 'Include relations: mechanic, workOrderService, workOrderService.service', type: 'string', example: 'mechanic,workOrderService')]
    public function show(string $id): MechanicAssignmentResource
    {
        $assignment = $this->assignmentService->getAssignmentById($id);
        Gate::authorize('view', $assignment);

        return new MechanicAssignmentResource($assignment);
    }

    /**
     * Create Mechanic Assignment
     *
     * Assign a mechanic to a work order service item.
     */
    public function store(StoreMechanicAssignmentRequest $request): MechanicAssignmentResource
    {
        Gate::authorize('create', MechanicAssignment::class);

        // Transform validated request to DTO
        $dto = MechanicAssignmentData::fromRequest($request);

        $assignment = $this->assignmentService->createAssignment($dto);

        return new MechanicAssignmentResource($assignment);
    }

    /**
     * Update Mechanic Assignment Status
     *
     * Update the progress of an assignment (e.g., mark as completed).
     */
    public function update(UpdateMechanicAssignmentRequest $request, string $id): MechanicAssignmentResource
    {
        $assignment = $this->assignmentService->getAssignmentById($id);
        Gate::authorize('update', $assignment);

        $assignment = $this->assignmentService->updateAssignment($assignment, $request->validated());

        return new MechanicAssignmentResource($assignment);
    }

    /**
     * Delete Mechanic Assignment
     *
     * Remove a mechanic from a service item.
     */
    public function destroy(string $id): JsonResponse
    {
        $assignment = $this->assignmentService->getAssignmentById($id);
        Gate::authorize('delete', $assignment);

        $this->assignmentService->deleteAssignment($assignment);

        return response()->json(null, 204);
    }

    /**
     * Cancel Mechanic Assignment
     *
     * Cancel a mechanic assignment (changes status to CANCELED).
     * Cannot cancel if in progress or completed.
     */
    public function cancel(string $id): JsonResponse
    {
        $assignment = $this->assignmentService->getAssignmentById($id);

        Gate::authorize('cancel', $assignment);

        $canceledAssignment = $this->assignmentService->cancelAssignment($id);

        return (new MechanicAssignmentResource($canceledAssignment))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Start Mechanic Assignment
     *
     * Start a specific mechanic's assignment. If this is the first mechanic to start,
     * the WorkOrderService will automatically transition to IN_PROGRESS.
     */
    public function start(StartMechanicAssignmentRequest $request, string $id): JsonResponse
    {
        $assignment = $this->assignmentService->getAssignmentById($id);
        Gate::authorize('start', $assignment);

        $startedAssignment = $this->assignmentService->startAssignment($id);

        return (new MechanicAssignmentResource($startedAssignment))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Complete Mechanic Assignment
     *
     * Complete a specific mechanic's assignment. If all mechanics have completed their assignments,
     * the WorkOrderService will automatically transition to COMPLETED.
     */
    public function complete(CompleteMechanicAssignmentRequest $request, string $id): JsonResponse
    {
        $assignment = $this->assignmentService->getAssignmentById($id);
        Gate::authorize('complete', $assignment);

        $result = $this->assignmentService->completeAssignment($id);

        $pendingAssignmentsCount = $result['workOrderService']->mechanicAssignments()
            ->where('status', '!=', \App\Enums\MechanicAssignmentStatus::CANCELED->value)
            ->where('status', '!=', \App\Enums\MechanicAssignmentStatus::COMPLETED->value)
            ->count();

        return (new MechanicAssignmentResource($result['assignment']))
            ->additional([
                'meta' => [
                    'service_auto_completed' => $result['serviceAutoCompleted'],
                    'pending_assignments_count' => $pendingAssignmentsCount,
                    'work_order_service_status' => $result['workOrderService']->status->value,
                ]
            ])
            ->response()
            ->setStatusCode(200);
    }
}
