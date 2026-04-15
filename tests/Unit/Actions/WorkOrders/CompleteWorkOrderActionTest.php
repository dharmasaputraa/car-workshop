<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\CompleteWorkOrderAction;
use App\Enums\ServiceItemStatus;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderCompleted;
use App\Models\Service;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CompleteWorkOrderActionTest extends TestCase
{
    use RefreshDatabase;

    /** @var MockInterface|WorkOrderRepositoryInterface */
    protected $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
    }

    public function test_complete_work_order_when_all_services_finished(): void
    {
        Event::fake();

        // Create a real work order with completed services
        $workOrder = WorkOrder::factory()->create([
            'status' => WorkOrderStatus::IN_PROGRESS->value,
        ]);

        $service = Service::factory()->create();
        $workOrder->workOrderServices()->create([
            'service_id' => $service->id,
            'price' => $service->base_price,
            'status' => ServiceItemStatus::COMPLETED->value,
        ]);

        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($workOrder->id)
            ->andReturn($workOrder);

        $this->repositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::COMPLETED->value)
            ->andReturn($workOrder);

        $this->repositoryMock
            ->shouldReceive('loadMissingRelations')
            ->once()
            ->with($workOrder, ['car.owner'])
            ->andReturn($workOrder);

        $action = new CompleteWorkOrderAction($this->repositoryMock);
        $result = $action->execute($workOrder->id);

        $this->assertSame($workOrder, $result);

        Event::assertDispatched(WorkOrderCompleted::class, function ($event) use ($workOrder) {
            return $event->workOrder === $workOrder;
        });
    }

    public function test_complete_throws_exception_when_not_in_progress(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only Work Orders with IN_PROGRESS status can be completed.');

        $workOrder = WorkOrder::factory()->create([
            'status' => WorkOrderStatus::APPROVED->value,
        ]);

        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($workOrder->id)
            ->andReturn($workOrder);

        $action = new CompleteWorkOrderAction($this->repositoryMock);
        $action->execute($workOrder->id);
    }

    public function test_complete_throws_exception_when_services_unfinished(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to complete the Work Order. There are still 2 unfinished services.');

        // Create a real work order with unfinished services
        $workOrder = WorkOrder::factory()->create([
            'status' => WorkOrderStatus::IN_PROGRESS->value,
        ]);

        $service = Service::factory()->create();
        // Create 2 pending services
        $workOrder->workOrderServices()->create([
            'service_id' => $service->id,
            'price' => $service->base_price,
            'status' => ServiceItemStatus::PENDING->value,
        ]);
        $workOrder->workOrderServices()->create([
            'service_id' => $service->id,
            'price' => $service->base_price,
            'status' => ServiceItemStatus::PENDING->value,
        ]);

        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with($workOrder->id)
            ->andReturn($workOrder);

        $action = new CompleteWorkOrderAction($this->repositoryMock);
        $action->execute($workOrder->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
