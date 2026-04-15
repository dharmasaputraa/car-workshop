<?php

namespace Tests\Feature\Api\V1\WorkOrder;

use App\Enums\RoleType;
use App\Models\Car;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class WorkOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;
    protected $car;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles first
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => RoleType::ADMIN->value, 'guard_name' => 'api']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => RoleType::CUSTOMER->value, 'guard_name' => 'api']);

        // Create permissions first
        $permissions = [
            'view_any_work_order',
            'create_work_order',
            'diagnose_work_order',
            'approve_work_order',
            'complete_work_order',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleType::ADMIN->value);

        // Grant all necessary permissions to admin
        $this->admin->givePermissionTo($permissions);

        $this->customer = User::factory()->create();
        $this->customer->assignRole(RoleType::CUSTOMER->value);

        $this->car = Car::factory()->create(['owner_id' => $this->customer->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATION
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access_work_orders(): void
    {
        $response = $this->getJson('/api/v1/work-orders');
        $response->assertStatus(401);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE WORK ORDER
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_create_work_order(): void
    {
        $token = JWTAuth::fromUser($this->admin);

        $data = [
            'car_id' => $this->car->id,
            'diagnosis_notes' => 'Test diagnosis',
            'estimated_completion' => '2026-04-20',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/work-orders', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes' => [
                        'order_number',
                        'status',
                        'diagnosis_notes',
                        'estimated_completion',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('work_orders', [
            'car_id' => $this->car->id,
            'status' => 'draft',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LIST WORK ORDERS
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_list_work_orders(): void
    {
        $token = JWTAuth::fromUser($this->admin);

        WorkOrder::factory()->count(5)->create(['car_id' => $this->car->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/work-orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DIAGNOSE WORK ORDER
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_diagnose_work_order(): void
    {
        $token = JWTAuth::fromUser($this->admin);

        $workOrder = WorkOrder::factory()->create([
            'car_id' => $this->car->id,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $services = Service::factory()->count(2)->create();

        $data = [
            'services' => [
                ['service_id' => $services[0]->id, 'notes' => 'Oil change'],
                ['service_id' => $services[1]->id, 'notes' => 'Brake check'],
            ],
            'diagnosis_notes' => 'Car needs maintenance',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/work-orders/{$workOrder->id}/diagnose", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.status', 'diagnosed');

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'status' => 'diagnosed',
        ]);
    }

    public function test_diagnose_non_draft_work_order_returns_error(): void
    {
        $token = JWTAuth::fromUser($this->admin);

        $workOrder = WorkOrder::factory()->create([
            'car_id' => $this->car->id,
            'status' => 'approved',
            'created_by' => $this->admin->id,
        ]);

        $data = [
            'services' => [],
            'diagnosis_notes' => 'Test diagnosis',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/work-orders/{$workOrder->id}/diagnose", $data);

        $response->assertStatus(500);
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE WORK ORDER
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_approve_work_order(): void
    {
        $token = JWTAuth::fromUser($this->admin);

        $workOrder = WorkOrder::factory()->create([
            'car_id' => $this->car->id,
            'status' => 'diagnosed',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/work-orders/{$workOrder->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.status', 'approved');

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'status' => 'approved',
        ]);
    }

    public function test_approve_non_diagnosed_work_order_returns_error(): void
    {
        $token = JWTAuth::fromUser($this->admin);

        $workOrder = WorkOrder::factory()->create([
            'car_id' => $this->car->id,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/work-orders/{$workOrder->id}/approve");

        $response->assertStatus(500);
    }

    /*
    |--------------------------------------------------------------------------
    | COMPLETE WORK ORDER
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_complete_work_order(): void
    {
        $token = JWTAuth::fromUser($this->admin);

        $workOrder = WorkOrder::factory()->create([
            'car_id' => $this->car->id,
            'status' => 'in_progress',
            'created_by' => $this->admin->id,
        ]);

        // Create completed services
        $service = Service::factory()->create();
        $workOrder->workOrderServices()->create([
            'service_id' => $service->id,
            'price' => $service->base_price,
            'status' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/work-orders/{$workOrder->id}/complete");

        $response->assertStatus(200)
            ->assertJsonPath('data.attributes.status', 'completed');

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'status' => 'completed',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHORIZATION
    |--------------------------------------------------------------------------
    */

    public function test_customer_cannot_diagnose_work_order(): void
    {
        $token = JWTAuth::fromUser($this->customer);

        $workOrder = WorkOrder::factory()->create([
            'car_id' => $this->car->id,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $data = [
            'services' => [],
            'diagnosis_notes' => 'Test diagnosis',
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/v1/work-orders/{$workOrder->id}/diagnose", $data);

        $response->assertStatus(403);
    }
}
