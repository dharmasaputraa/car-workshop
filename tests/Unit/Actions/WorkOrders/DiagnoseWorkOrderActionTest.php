<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\DiagnoseWorkOrderAction;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderDiagnosed;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Repositories\Contracts\WorkOrderServiceRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DiagnoseWorkOrderActionTest extends TestCase
{
    /** @var MockInterface|WorkOrderRepositoryInterface */
    protected $workOrderRepositoryMock;

    /** @var MockInterface|WorkOrderServiceRepositoryInterface */
    protected $workOrderServiceRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workOrderRepositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
        $this->workOrderServiceRepositoryMock = Mockery::mock(WorkOrderServiceRepositoryInterface::class);
    }

    public function test_diagnose_draft_work_order_successfully(): void
    {
        Event::fake();

        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DRAFT->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $servicesData = [
            ['service_id' => 'service-1', 'notes' => 'Oil change'],
            ['service_id' => 'service-2', 'notes' => 'Brake check'],
        ];

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($workOrder, ['diagnosis_notes' => 'Car needs maintenance'])
            ->andReturn($workOrder);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('cancelAllServices')
            ->once()
            ->with($workOrder);

        $this->workOrderServiceRepositoryMock
            ->shouldReceive('addServicesToWorkOrder')
            ->once()
            ->with($workOrder, $servicesData);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::DIAGNOSED->value)
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('loadMissingRelations')
            ->once()
            ->with($workOrder, Mockery::type('array'))
            ->andReturn($workOrder);

        $action = new DiagnoseWorkOrderAction(
            $this->workOrderRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $result = $action->execute('wo-123', $servicesData, 'Car needs maintenance');

        $this->assertSame($workOrder, $result);

        Event::assertDispatched(WorkOrderDiagnosed::class, function ($event) use ($workOrder) {
            return $event->workOrder === $workOrder;
        });
    }

    public function test_diagnose_throws_exception_when_not_draft(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only Work Orders with DRAFT status can be diagnosed.');

        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::APPROVED->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new DiagnoseWorkOrderAction(
            $this->workOrderRepositoryMock,
            $this->workOrderServiceRepositoryMock
        );

        $action->execute('wo-123', [], 'Test diagnosis');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
