<?php

namespace Tests\Feature\Api\V1\Mechanic;

use App\Enums\MechanicAssignmentStatus;
use App\Enums\RoleType;
use App\Enums\WorkOrderStatus;
use App\Events\MechanicAssigned;
use App\Models\MechanicAssignment;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class MechanicAssignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $mechanic;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'view_any_mechanic_assignment',
            'view_mechanic_assignment',
            'create_mechanic_assignment',
            'update_mechanic_assignment',
            'delete_mechanic_assignment',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles
        Role::firstOrCreate(['name' => RoleType::ADMIN->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => RoleType::MECHANIC->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => RoleType::CUSTOMER->value, 'guard_name' => 'api']);

        // Create users
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        $this->admin->assignRole(RoleType::ADMIN->value);
        $this->admin->givePermissionTo(['view_any_mechanic_assignment', 'view_mechanic_assignment', 'create_mechanic_assignment', 'update_mechanic_assignment', 'delete_mechanic_assignment']);

        $this->mechanic = User::factory()->create([
            'email' => 'mechanic@example.com',
            'is_active' => true,
        ]);
        $this->mechanic->assignRole(RoleType::MECHANIC->value);

        $this->customer = User::factory()->create([
            'email' => 'customer@example.com',
            'is_active' => true,
        ]);
        $this->customer->assignRole(RoleType::CUSTOMER->value);
    }

    protected function actingAsAdmin()
    {
        $token = JWTAuth::fromUser($this->admin);
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    protected function actingAsMechanic()
    {
        $token = JWTAuth::fromUser($this->mechanic);
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function test_index_lists_assignments(): void
    {
        $assignments = MechanicAssignment::factory()->count(5)->create();

        $response = $this->actingAsAdmin()
            ->getJson('/api/v1/mechanic-assignments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'attributes' => [
                            'status',
                            'assigned_at',
                            'completed_at',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ]);

        $this->assertEquals(5, count($response->json('data')));
    }

    public function test_index_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/v1/mechanic-assignments');

        $response->assertStatus(401);
    }

    public function test_index_mechanic_can_view_own_assignments(): void
    {
        $assignment = MechanicAssignment::factory()->create([
            'mechanic_id' => $this->mechanic->id,
        ]);

        $response = $this->actingAsMechanic()
            ->getJson('/api/v1/mechanic-assignments');

        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function test_show_returns_assignment(): void
    {
        $assignment = MechanicAssignment::factory()->create();

        $response = $this->actingAsAdmin()
            ->getJson("/api/v1/mechanic-assignments/{$assignment->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ]);

        $this->assertEquals($assignment->id, $response->json('data.id'));
    }

    public function test_show_mechanic_can_view_own_assignment(): void
    {
        $assignment = MechanicAssignment::factory()->create([
            'mechanic_id' => $this->mechanic->id,
        ]);

        $response = $this->actingAsMechanic()
            ->getJson("/api/v1/mechanic-assignments/{$assignment->id}");

        $response->assertStatus(200);
    }

    public function test_show_mechanic_cannot_view_others_assignment(): void
    {
        $assignment = MechanicAssignment::factory()->create([
            'mechanic_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAsMechanic()
            ->getJson("/api/v1/mechanic-assignments/{$assignment->id}");

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function test_store_creates_assignment_successfully(): void
    {
        Event::fake();
        Notification::fake();

        // Create work order and service
        $car = \App\Models\Car::factory()->create();
        $workOrder = WorkOrder::factory()
            ->for($car)
            ->create(['status' => WorkOrderStatus::APPROVED]);

        $service = Service::factory()->create();
        $workOrderService = WorkOrderService::factory()
            ->for($workOrder)
            ->for($service)
            ->create();

        $mechanic = User::factory()->create();

        $data = [
            'work_order_service_id' => $workOrderService->id,
            'mechanic_id' => $mechanic->id,
        ];

        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/mechanic-assignments', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes',
                ],
            ]);

        // Assert assignment created
        $this->assertDatabaseHas('mechanic_assignments', [
            'work_order_service_id' => $workOrderService->id,
            'mechanic_id' => $mechanic->id,
            'status' => MechanicAssignmentStatus::ASSIGNED->value,
        ]);

        // Assert WO Service status updated
        $this->assertDatabaseHas('work_order_services', [
            'id' => $workOrderService->id,
            'status' => 'in_progress',
        ]);

        // Assert Work Order status updated
        $workOrder->refresh();
        $this->assertEquals(WorkOrderStatus::IN_PROGRESS, $workOrder->status);

        // Assert event dispatched
        Event::assertDispatched(MechanicAssigned::class);
    }

    public function test_store_requires_work_order_service_id(): void
    {
        $data = [
            'mechanic_id' => $this->mechanic->id,
        ];

        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/mechanic-assignments', $data);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                    ],
                ],
            ]);

        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/work_order_service_id', $pointers);
    }

    public function test_store_requires_mechanic_id(): void
    {
        $workOrderService = WorkOrderService::factory()->create();

        $data = [
            'work_order_service_id' => $workOrderService->id,
        ];

        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/mechanic-assignments', $data);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                    ],
                ],
            ]);

        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/mechanic_id', $pointers);
    }

    public function test_store_work_order_service_id_must_exist(): void
    {
        $data = [
            'work_order_service_id' => 'non-existent-uuid',
            'mechanic_id' => $this->mechanic->id,
        ];

        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/mechanic-assignments', $data);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                    ],
                ],
            ]);

        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/work_order_service_id', $pointers);
    }

    public function test_store_mechanic_id_must_exist(): void
    {
        $workOrderService = WorkOrderService::factory()->create();

        $data = [
            'work_order_service_id' => $workOrderService->id,
            'mechanic_id' => 'non-existent-uuid',
        ];

        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/mechanic-assignments', $data);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                    ],
                ],
            ]);

        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/mechanic_id', $pointers);
    }

    public function test_store_fails_when_work_order_not_approved_or_in_progress(): void
    {
        $car = \App\Models\Car::factory()->create();
        $workOrder = WorkOrder::factory()
            ->for($car)
            ->create(['status' => WorkOrderStatus::PENDING]);

        $service = Service::factory()->create();
        $workOrderService = WorkOrderService::factory()
            ->for($workOrder)
            ->for($service)
            ->create();

        $data = [
            'work_order_service_id' => $workOrderService->id,
            'mechanic_id' => $this->mechanic->id,
        ];

        $response = $this->actingAsAdmin()
            ->postJson('/api/v1/mechanic-assignments', $data);

        $response->assertStatus(500);
    }

    public function test_store_unauthenticated_returns_401(): void
    {
        $data = [
            'work_order_service_id' => WorkOrderService::factory()->create()->id,
            'mechanic_id' => $this->mechanic->id,
        ];

        $response = $this->postJson('/api/v1/mechanic-assignments', $data);

        $response->assertStatus(401);
    }

    public function test_store_unauthorized_returns_403(): void
    {
        $data = [
            'work_order_service_id' => WorkOrderService::factory()->create()->id,
            'mechanic_id' => $this->mechanic->id,
        ];

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/mechanic-assignments', $data);

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function test_update_updates_assignment_status(): void
    {
        $assignment = MechanicAssignment::factory()->create([
            'status' => MechanicAssignmentStatus::ASSIGNED,
        ]);

        $data = [
            'status' => MechanicAssignmentStatus::IN_PROGRESS->value,
        ];

        $response = $this->actingAsAdmin()
            ->putJson("/api/v1/mechanic-assignments/{$assignment->id}", $data);

        $response->assertStatus(200);

        $assignment->refresh();
        $this->assertEquals(MechanicAssignmentStatus::IN_PROGRESS, $assignment->status);
    }

    public function test_update_mechanic_can_update_own_assignment(): void
    {
        $assignment = MechanicAssignment::factory()->create([
            'mechanic_id' => $this->mechanic->id,
            'status' => MechanicAssignmentStatus::ASSIGNED,
        ]);

        $data = [
            'status' => MechanicAssignmentStatus::IN_PROGRESS->value,
        ];

        $response = $this->actingAsMechanic()
            ->putJson("/api/v1/mechanic-assignments/{$assignment->id}", $data);

        $response->assertStatus(200);
    }

    public function test_update_requires_status(): void
    {
        $assignment = MechanicAssignment::factory()->create();

        $response = $this->actingAsAdmin()
            ->putJson("/api/v1/mechanic-assignments/{$assignment->id}", []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                    ],
                ],
            ]);

        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/status', $pointers);
    }

    public function test_update_status_must_be_valid_enum(): void
    {
        $assignment = MechanicAssignment::factory()->create();

        $data = [
            'status' => 'invalid_status',
        ];

        $response = $this->actingAsAdmin()
            ->putJson("/api/v1/mechanic-assignments/{$assignment->id}", $data);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'errors' => [
                    '*' => [
                        'detail',
                        'source' => ['pointer'],
                    ],
                ],
            ]);

        $errors = collect($response->json('errors'));
        $pointers = $errors->pluck('source.pointer')->toArray();
        $this->assertContains('/data/attributes/status', $pointers);
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

    public function test_cancel_assignment(): void
    {
        $assignment = MechanicAssignment::factory()->create([
            'status' => MechanicAssignmentStatus::ASSIGNED,
        ]);

        $response = $this->actingAsAdmin()
            ->patchJson("/api/v1/mechanic-assignments/{$assignment->id}/cancel");

        $response->assertStatus(200);

        $assignment->refresh();
        $this->assertEquals(MechanicAssignmentStatus::CANCELED, $assignment->status);
    }

    public function test_cancel_unauthenticated_returns_401(): void
    {
        $assignment = MechanicAssignment::factory()->create();

        $response = $this->patchJson("/api/v1/mechanic-assignments/{$assignment->id}/cancel");

        $response->assertStatus(401);
    }

    public function test_cancel_unauthorized_returns_403(): void
    {
        $assignment = MechanicAssignment::factory()->create();

        $response = $this->actingAs($this->customer)
            ->patchJson("/api/v1/mechanic-assignments/{$assignment->id}/cancel");

        $response->assertStatus(403);
    }
}
