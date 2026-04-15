<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\WorkOrders\ApproveWorkOrderProposalAction;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderApproved;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ApproveWorkOrderProposalActionTest extends TestCase
{
    /** @var MockInterface|WorkOrderRepositoryInterface */
    protected $repositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
    }

    public function test_approve_diagnosed_work_order_successfully(): void
    {
        Event::fake();

        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DIAGNOSED->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->repositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::APPROVED->value)
            ->andReturn($workOrder);

        $this->repositoryMock
            ->shouldReceive('loadMissingRelations')
            ->once()
            ->with($workOrder, ['creator', 'car'])
            ->andReturn($workOrder);

        $action = new ApproveWorkOrderProposalAction($this->repositoryMock);
        $result = $action->execute('wo-123');

        $this->assertSame($workOrder, $result);

        Event::assertDispatched(WorkOrderApproved::class, function ($event) use ($workOrder) {
            return $event->workOrder === $workOrder;
        });
    }

    public function test_approve_throws_exception_when_not_diagnosed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only Work Orders with DIAGNOSED status can be approved.');

        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::DRAFT->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        $this->repositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new ApproveWorkOrderProposalAction($this->repositoryMock);
        $action->execute('wo-123');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
