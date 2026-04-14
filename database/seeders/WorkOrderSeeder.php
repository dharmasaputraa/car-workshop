<?php

namespace Database\Seeders;

use App\Enums\MechanicAssignmentStatus;
use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Enums\RoleType;
use App\Models\Car;
use App\Models\MechanicAssignment;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use Illuminate\Database\Seeder;

class WorkOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing data
        $mechanics = User::role(RoleType::MECHANIC->value)->get();
        $admin = User::role(RoleType::ADMIN->value)->first();
        $cars = Car::all();
        $services = Service::where('is_active', true)->get();

        if ($mechanics->isEmpty()) {
            $this->command->error('No mechanics found. Please run CustomerCarSeeder first.');
            return;
        }

        if ($cars->isEmpty()) {
            $this->command->error('No cars found. Please run CustomerCarSeeder first.');
            return;
        }

        if ($services->isEmpty()) {
            $this->command->error('No services found. Please run ServiceSeeder first.');
            return;
        }

        $workOrderCount = 0;
        $workOrderServiceCount = 0;
        $mechanicAssignmentCount = 0;

        // Generate order numbers counter
        $orderNumberCounter = 1001;

        /*
        |--------------------------------------------------------------------------
        | 1. DRAFT Work Orders (3) - No services, no assignments
        |--------------------------------------------------------------------------
        */
        for ($i = 0; $i < 3; $i++) {
            $car = $cars->random();
            $workOrder = $this->createWorkOrder($car, $admin, WorkOrderStatus::DRAFT, $orderNumberCounter++);
            $workOrderCount++;
            $this->command->info("Created DRAFT WO #{$workOrder->order_number}");
        }

        /*
        |--------------------------------------------------------------------------
        | 2. DIAGNOSED Work Orders (2) - Services with PENDING status, no assignments
        |--------------------------------------------------------------------------
        */
        for ($i = 0; $i < 2; $i++) {
            $car = $cars->random();
            $workOrder = $this->createWorkOrder($car, $admin, WorkOrderStatus::DIAGNOSED, $orderNumberCounter++);
            $workOrder->diagnosis_notes = "Customer reported engine issues. Recommended services listed.";
            $workOrder->save();

            // Add 2-3 services with PENDING status
            $numServices = rand(2, 3);
            for ($j = 0; $j < $numServices; $j++) {
                $service = $services->random();
                $this->createWorkOrderService($workOrder, $service, ServiceItemStatus::PENDING);
                $workOrderServiceCount++;
            }

            $workOrderCount++;
            $this->command->info("Created DIAGNOSED WO #{$workOrder->order_number} with {$numServices} services");
        }

        /*
        |--------------------------------------------------------------------------
        | 3. APPROVED Work Orders (2) - Services with PENDING status, mechanic ASSIGNED
        |--------------------------------------------------------------------------
        */
        for ($i = 0; $i < 2; $i++) {
            $car = $cars->random();
            $workOrder = $this->createWorkOrder($car, $admin, WorkOrderStatus::APPROVED, $orderNumberCounter++);
            $workOrder->estimated_completion = now()->addDays(rand(2, 5));
            $workOrder->save();

            // Add 2-3 services with PENDING status and ASSIGNED mechanics
            $numServices = rand(2, 3);
            for ($j = 0; $j < $numServices; $j++) {
                $service = $services->random();
                $woService = $this->createWorkOrderService($workOrder, $service, ServiceItemStatus::PENDING);
                $workOrderServiceCount++;

                // Assign a mechanic
                $mechanic = $mechanics->random();
                $this->createMechanicAssignment($woService, $mechanic, MechanicAssignmentStatus::ASSIGNED);
                $mechanicAssignmentCount++;
            }

            $workOrderCount++;
            $this->command->info("Created APPROVED WO #{$workOrder->order_number} with {$numServices} services and assignments");
        }

        /*
        |--------------------------------------------------------------------------
        | 4. IN_PROGRESS Work Orders (3) - Services mixed status, mechanics IN_PROGRESS
        |--------------------------------------------------------------------------
        */
        for ($i = 0; $i < 3; $i++) {
            $car = $cars->random();
            $workOrder = $this->createWorkOrder($car, $admin, WorkOrderStatus::IN_PROGRESS, $orderNumberCounter++);
            $workOrder->estimated_completion = now()->addDays(rand(1, 3));
            $workOrder->save();

            // Add 2-3 services with mixed status
            $numServices = rand(2, 3);
            for ($j = 0; $j < $numServices; $j++) {
                $service = $services->random();
                $serviceStatus = $j === 0 ? ServiceItemStatus::IN_PROGRESS : ServiceItemStatus::ASSIGNED;
                $woService = $this->createWorkOrderService($workOrder, $service, $serviceStatus);
                $workOrderServiceCount++;

                // Assign a mechanic
                $mechanic = $mechanics->random();
                $assignmentStatus = $j === 0 ? MechanicAssignmentStatus::IN_PROGRESS : MechanicAssignmentStatus::ASSIGNED;
                $this->createMechanicAssignment($woService, $mechanic, $assignmentStatus);
                $mechanicAssignmentCount++;
            }

            $workOrderCount++;
            $this->command->info("Created IN_PROGRESS WO #{$workOrder->order_number} with {$numServices} services and assignments");
        }

        /*
        |--------------------------------------------------------------------------
        | 5. COMPLETED Work Orders (3) - Services COMPLETED, mechanics COMPLETED
        |--------------------------------------------------------------------------
        */
        for ($i = 0; $i < 3; $i++) {
            $car = $cars->random();
            $workOrder = $this->createWorkOrder($car, $admin, WorkOrderStatus::COMPLETED, $orderNumberCounter++);
            $workOrder->estimated_completion = now()->subDays(rand(1, 5));
            $workOrder->save();

            // Add 2-3 services with COMPLETED status
            $numServices = rand(2, 3);
            for ($j = 0; $j < $numServices; $j++) {
                $service = $services->random();
                $woService = $this->createWorkOrderService($workOrder, $service, ServiceItemStatus::COMPLETED);
                $workOrderServiceCount++;

                // Assign and complete mechanic
                $mechanic = $mechanics->random();
                $this->createMechanicAssignment($woService, $mechanic, MechanicAssignmentStatus::COMPLETED, true);
                $mechanicAssignmentCount++;
            }

            $workOrderCount++;
            $this->command->info("Created COMPLETED WO #{$workOrder->order_number} with {$numServices} services and assignments");
        }

        /*
        |--------------------------------------------------------------------------
        | 6. INVOICED Work Orders (2) - Services COMPLETED, mechanics COMPLETED
        |--------------------------------------------------------------------------
        */
        for ($i = 0; $i < 2; $i++) {
            $car = $cars->random();
            $workOrder = $this->createWorkOrder($car, $admin, WorkOrderStatus::INVOICED, $orderNumberCounter++);
            $workOrder->estimated_completion = now()->subDays(rand(5, 10));
            $workOrder->save();

            // Add 2-3 services with COMPLETED status
            $numServices = rand(2, 3);
            for ($j = 0; $j < $numServices; $j++) {
                $service = $services->random();
                $woService = $this->createWorkOrderService($workOrder, $service, ServiceItemStatus::COMPLETED);
                $workOrderServiceCount++;

                // Assign and complete mechanic
                $mechanic = $mechanics->random();
                $this->createMechanicAssignment($woService, $mechanic, MechanicAssignmentStatus::COMPLETED, true);
                $mechanicAssignmentCount++;
            }

            $workOrderCount++;
            $this->command->info("Created INVOICED WO #{$workOrder->order_number} with {$numServices} services and assignments");
        }

        /*
        |--------------------------------------------------------------------------
        | 7. CLOSED Work Orders (2) - Services COMPLETED, mechanics COMPLETED
        |--------------------------------------------------------------------------
        */
        for ($i = 0; $i < 2; $i++) {
            $car = $cars->random();
            $workOrder = $this->createWorkOrder($car, $admin, WorkOrderStatus::CLOSED, $orderNumberCounter++);
            $workOrder->estimated_completion = now()->subDays(rand(10, 20));
            $workOrder->save();

            // Add 2-3 services with COMPLETED status
            $numServices = rand(2, 3);
            for ($j = 0; $j < $numServices; $j++) {
                $service = $services->random();
                $woService = $this->createWorkOrderService($workOrder, $service, ServiceItemStatus::COMPLETED);
                $workOrderServiceCount++;

                // Assign and complete mechanic
                $mechanic = $mechanics->random();
                $this->createMechanicAssignment($woService, $mechanic, MechanicAssignmentStatus::COMPLETED, true);
                $mechanicAssignmentCount++;
            }

            $workOrderCount++;
            $this->command->info("Created CLOSED WO #{$workOrder->order_number} with {$numServices} services and assignments");
        }

        /*
        |--------------------------------------------------------------------------
        | 8. CANCELED Work Orders (2) - Some services CANCELED
        |--------------------------------------------------------------------------
        */
        for ($i = 0; $i < 2; $i++) {
            $car = $cars->random();
            $workOrder = $this->createWorkOrder($car, $admin, WorkOrderStatus::CANCELED, $orderNumberCounter++);
            $workOrder->diagnosis_notes = "Customer canceled the work order.";
            $workOrder->save();

            // Add 0-1 services with CANCELED status
            $numServices = rand(0, 1);
            for ($j = 0; $j < $numServices; $j++) {
                $service = $services->random();
                $this->createWorkOrderService($workOrder, $service, ServiceItemStatus::CANCELED);
                $workOrderServiceCount++;
            }

            $workOrderCount++;
            $this->command->info("Created CANCELED WO #{$workOrder->order_number} with {$numServices} services");
        }

        /*
        |--------------------------------------------------------------------------
        | 9. COMPLAINED Work Order (1) - Services COMPLAINED
        |--------------------------------------------------------------------------
        */
        $car = $cars->random();
        $workOrder = $this->createWorkOrder($car, $admin, WorkOrderStatus::COMPLAINED, $orderNumberCounter++);
        $workOrder->diagnosis_notes = "Customer complained about oil leak after service.";
        $workOrder->estimated_completion = now()->subDays(rand(2, 5));
        $workOrder->save();

        // Add 2-3 services with COMPLAINED status
        $numServices = rand(2, 3);
        for ($j = 0; $j < $numServices; $j++) {
            $service = $services->random();
            $woService = $this->createWorkOrderService($workOrder, $service, ServiceItemStatus::COMPLAINED);
            $workOrderServiceCount++;

            // Mechanics were completed but work was complained
            $mechanic = $mechanics->random();
            $this->createMechanicAssignment($woService, $mechanic, MechanicAssignmentStatus::COMPLETED, true);
            $mechanicAssignmentCount++;
        }

        $workOrderCount++;
        $this->command->info("Created COMPLAINED WO #{$workOrder->order_number} with {$numServices} services and assignments");

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */
        $this->command->info('------------------------------');
        $this->command->info('WorkOrder Seeding completed:');
        $this->command->info("- Work Orders: {$workOrderCount}");
        $this->command->info("- Work Order Services: {$workOrderServiceCount}");
        $this->command->info("- Mechanic Assignments: {$mechanicAssignmentCount}");
        $this->command->info('------------------------------');
    }

    private function createWorkOrder(Car $car, User $admin, WorkOrderStatus $status, int $orderNumber): WorkOrder
    {
        return WorkOrder::create([
            'order_number' => 'WO-2026-' . $orderNumber,
            'car_id' => $car->id,
            'created_by' => $admin->id,
            'status' => $status,
            'diagnosis_notes' => null,
            'estimated_completion' => null,
        ]);
    }

    private function createWorkOrderService(WorkOrder $workOrder, Service $service, ServiceItemStatus $status): WorkOrderService
    {
        return WorkOrderService::create([
            'work_order_id' => $workOrder->id,
            'service_id' => $service->id,
            'price' => $service->base_price,
            'status' => $status,
            'notes' => $status === ServiceItemStatus::PENDING ? 'Proposed service' : null,
        ]);
    }

    private function createMechanicAssignment(WorkOrderService $woService, User $mechanic, MechanicAssignmentStatus $status, bool $isCompleted = false): MechanicAssignment
    {
        $data = [
            'work_order_service_id' => $woService->id,
            'mechanic_id' => $mechanic->id,
            'assigned_at' => now()->subHours(rand(1, 24)),
            'completed_at' => null,
            'status' => $status,
        ];

        if ($isCompleted) {
            $data['completed_at'] = now()->subHours(rand(1, 48));
        }

        return MechanicAssignment::create($data);
    }
}
