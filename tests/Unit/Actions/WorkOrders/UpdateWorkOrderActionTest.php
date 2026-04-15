<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\UpdateWorkOrderAction;
use App\DTOs\WorkOrder\UpdateWorkOrderData;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UpdateWorkOrderActionTest extends TestCase
{
    protected MockInterface|WorkOrderRepositoryInterface $workOrderRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workOrderRepositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
    }

    public function test_update_draft_work_order_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DRAFT->value;

        $data = new UpdateWorkOrderData(
            carId: 'car-123',
            diagnosisNotes: 'Updated diagnosis',
            estimatedCompletion: '2026-04-20',
            validatedData: [
                'car_id' => 'car-123',
                'diagnosis_notes' => 'Updated diagnosis',
                'estimated_completion' => '2026-04-20'
            ]
        );

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($workOrder, $data->toArray())
            ->andReturn($workOrder);

        $action = new UpdateWorkOrderAction($this->workOrderRepositoryMock);
        $result = $action->execute('wo-123', $data);

        $this->assertSame($workOrder, $result);
    }

    public function test_update_partial_fields_successfully(): void
    {
        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DRAFT->value;

        $data = new UpdateWorkOrderData(
            carId: null,
            diagnosisNotes: 'New diagnosis notes only',
            estimatedCompletion: null,
            validatedData: [
                'diagnosis_notes' => 'New diagnosis notes only'
            ]
        );

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('update')
            ->once()
            ->with($workOrder, ['diagnosis_notes' => 'New diagnosis notes only'])
            ->andReturn($workOrder);

        $action = new UpdateWorkOrderAction($this->workOrderRepositoryMock);
        $result = $action->execute('wo-123', $data);

        $this->assertSame($workOrder, $result);
    }

    public function test_update_throws_exception_when_not_draft(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only Work Orders with DRAFT status can be changed directly.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DIAGNOSED->value;

        $data = new UpdateWorkOrderData(
            carId: 'car-123',
            diagnosisNotes: 'Updated diagnosis',
            estimatedCompletion: null,
            validatedData: [
                'car_id' => 'car-123',
                'diagnosis_notes' => 'Updated diagnosis'
            ]
        );

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new UpdateWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123', $data);
    }

    public function test_update_throws_exception_when_diagnosed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only Work Orders with DRAFT status can be changed directly.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::APPROVED->value;

        $data = new UpdateWorkOrderData(
            carId: null,
            diagnosisNotes: null,
            estimatedCompletion: '2026-04-20',
            validatedData: [
                'estimated_completion' => '2026-04-20'
            ]
        );

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new UpdateWorkOrderAction($this->workOrderRepositoryMock);
        $action->execute('wo-123', $data);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
