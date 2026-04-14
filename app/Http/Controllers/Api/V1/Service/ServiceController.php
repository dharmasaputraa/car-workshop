<?php

namespace App\Http\Controllers\Api\V1\Service;

use App\DTOs\Service\StoreServiceData;
use App\DTOs\Service\UpdateServiceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Service\StoreServiceRequest;
use App\Http\Requests\Api\V1\Service\UpdateServiceRequest;
use App\Http\Resources\Api\V1\Service\ServiceResource;
use App\Models\Service;
use App\Services\Service\ServiceService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Master Data - Services')]
class ServiceController extends Controller
{
    public function __construct(
        protected ServiceService $serviceService
    ) {}

    /**
     * List Services
     *
     * Retrieve a paginated list of workshop services/spare parts.
     */
    #[QueryParameter('filter[is_active]', description: 'Filter by active status', type: 'boolean', example: true)]
    #[QueryParameter('filter[name]', description: 'Filter by name (partial match)', type: 'string', example: 'Ganti Oli')]
    #[QueryParameter('sort', description: 'Sort by field (prefix - for desc). Options: name, base_price, created_at', type: 'string', example: 'base_price')]
    #[QueryParameter('include', description: 'Include relations: workOrderServices, complaintServices', type: 'string', example: 'workOrderServices')]
    #[QueryParameter('per_page', description: 'Number of results per page', type: 'integer', example: 15)]
    #[QueryParameter('page', description: 'Page number', type: 'integer', example: 1)]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Service::class);

        return ServiceResource::collection(
            $this->serviceService->getPaginatedServices()
        );
    }

    /**
     * Create Service
     *
     * Add a new service or spare part to the catalog.
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        Gate::authorize('create', Service::class);

        $dto = StoreServiceData::fromArray($request->validated());

        $service = $this->serviceService->createService($dto);

        return (new ServiceResource($service))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get Service
     *
     * Retrieve details of a specific service.
     */
    #[QueryParameter('include', description: 'Include relations: workOrderServices, complaintServices', type: 'string', example: 'workOrderServices')]
    public function show(string $id): ServiceResource
    {
        $service = $this->serviceService->getServiceById($id);

        Gate::authorize('view', $service);

        return new ServiceResource($service);
    }

    /**
     * Update Service
     *
     * Update pricing, name, or description of a service.
     */
    public function update(UpdateServiceRequest $request, string $id): ServiceResource
    {
        $service = $this->serviceService->getServiceById($id);
        Gate::authorize('update', $service);

        $dto = UpdateServiceData::fromArray($request->validated());

        $updatedService = $this->serviceService->updateService($service, $dto);

        return new ServiceResource($updatedService);
    }

    /**
     * Delete Service
     *
     * Permanently remove a service from the catalog.
     */
    public function destroy(string $id): Response
    {
        $service = $this->serviceService->getServiceById($id);
        Gate::authorize('delete', $service);

        $this->serviceService->deleteService($service);

        return response()->noContent();
    }

    /**
     * Toggle Service Active Status
     *
     * Enable or disable a service (e.g., if a spare part is out of stock).
     */
    public function toggleActive(string $id): ServiceResource
    {
        $service = $this->serviceService->getServiceById($id);
        Gate::authorize('toggleActive', $service);

        return new ServiceResource(
            $this->serviceService->toggleActive($service)
        );
    }
}
