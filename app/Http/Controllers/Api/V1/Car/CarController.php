<?php

namespace App\Http\Controllers\Api\V1\Car;

use App\DTOs\Car\StoreCarData;
use App\DTOs\Car\UpdateCarData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Car\StoreCarRequest;
use App\Http\Requests\Api\V1\Car\UpdateCarRequest;
use App\Http\Resources\Api\V1\Car\CarResource;
use App\Models\Car;
use App\Services\Car\CarService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

#[Group('Master Data - Cars')]
class CarController extends Controller
{
    public function __construct(
        protected CarService $carService
    ) {}

    /**
     * List Cars
     *
     * Retrieve a paginated list of registered customer cars.
     */
    #[QueryParameter('filter[plate_number]', description: 'Filter by plate number (partial match)', type: 'string', example: 'B 1234 ABC')]
    #[QueryParameter('filter[brand]', description: 'Filter by car brand (partial match)', type: 'string', example: 'Toyota')]
    #[QueryParameter('filter[owner_id]', description: 'Filter by exact owner ID', type: 'string', example: '9a1b2c3d-4e5f-6a7b-8c9d-0e1f2a3b4c5d')]
    #[QueryParameter('sort', description: 'Sort by field. Options: brand, year, created_at', type: 'string', example: '-created_at')]
    #[QueryParameter('include', description: 'Include relations: owner, workOrders', type: 'string', example: 'owner')]
    #[QueryParameter('per_page', description: 'Number of results per page', type: 'integer', example: 15)]
    #[QueryParameter('page', description: 'Page number', type: 'integer', example: 1)]
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Car::class);

        return CarResource::collection(
            $this->carService->getPaginatedCars()
        );
    }

    /**
     * Create Car
     *
     * Register a new customer car into the system.
     */
    #[QueryParameter('include', description: 'Include relations: owner, workOrders', type: 'string', example: 'owner,workOrders')]
    public function store(StoreCarRequest $request): JsonResponse
    {
        Gate::authorize('create', Car::class);

        $dto = StoreCarData::fromArray($request->validated());

        $car = $this->carService->createCar($dto);

        return (new CarResource($car))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get Car
     *
     * Retrieve details of a specific car.
     */
    public function show(string $id): CarResource
    {
        $car = $this->carService->getCarById($id);
        Gate::authorize('view', $car);

        return new CarResource($car);
    }

    /**
     * Update Car
     *
     * Update information for an existing car.
     */
    public function update(UpdateCarRequest $request, string $id): CarResource
    {
        $car = $this->carService->getCarById($id);
        Gate::authorize('update', $car);

        $dto = UpdateCarData::fromArray($request->validated());

        $updatedCar = $this->carService->updateCar($car, $dto);

        return new CarResource($updatedCar);
    }

    /**
     * Delete Car
     *
     * Permanently remove a car from the system.
     */
    public function destroy(string $id): Response
    {
        $car = $this->carService->getCarById($id);
        Gate::authorize('delete', $car);

        $this->carService->deleteCar($car);

        return response()->noContent();
    }
}
