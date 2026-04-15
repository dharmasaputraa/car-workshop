<?php

namespace Tests\Unit\Actions\WorkOrders;

use App\Actions\Invoices\GenerateInvoiceAction;
use App\Actions\WorkOrders\MarkWorkOrderAsInvoicedAction;
use App\Enums\InvoiceStatus;
use App\Enums\WorkOrderStatus;
use App\Events\SendWorkOrderInvoice;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class MarkWorkOrderAsInvoicedActionTest extends TestCase
{
    protected MockInterface|WorkOrderRepositoryInterface $workOrderRepositoryMock;
    protected MockInterface|GenerateInvoiceAction $generateInvoiceActionMock;
    protected MockInterface|InvoiceRepositoryInterface $invoiceRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workOrderRepositoryMock = Mockery::mock(WorkOrderRepositoryInterface::class);
        $this->generateInvoiceActionMock = Mockery::mock(GenerateInvoiceAction::class);
        $this->invoiceRepositoryMock = Mockery::mock(InvoiceRepositoryInterface::class);
    }

    public function test_mark_as_invoiced_successfully(): void
    {
        Event::fake();

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::COMPLETED->value;
        $workOrder->shouldReceive('setAttribute')->passthru();

        /** @var Invoice|MockInterface $invoice */
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $invoice->status = InvoiceStatus::DRAFT->value;
        $invoice->shouldReceive('setAttribute')->passthru();

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->generateInvoiceActionMock
            ->shouldReceive('execute')
            ->once()
            ->with('wo-123')
            ->andReturn($invoice);

        $this->invoiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($invoice, InvoiceStatus::UNPAID->value)
            ->andReturn($invoice);

        $this->workOrderRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($workOrder, WorkOrderStatus::INVOICED->value)
            ->andReturn($workOrder);

        $this->workOrderRepositoryMock
            ->shouldReceive('loadMissingRelations')
            ->once()
            ->with($workOrder, ['invoice'])
            ->andReturn($workOrder);

        $action = new MarkWorkOrderAsInvoicedAction(
            $this->workOrderRepositoryMock,
            $this->generateInvoiceActionMock,
            $this->invoiceRepositoryMock
        );

        $result = $action->execute('wo-123');

        $this->assertSame($workOrder, $result);
        Event::assertDispatched(SendWorkOrderInvoice::class);
    }

    public function test_mark_throws_exception_when_not_completed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only Work Orders with COMPLETED status can be issued Invoices.');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::IN_PROGRESS->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $action = new MarkWorkOrderAsInvoicedAction(
            $this->workOrderRepositoryMock,
            $this->generateInvoiceActionMock,
            $this->invoiceRepositoryMock
        );

        $action->execute('wo-123');
    }

    public function test_mark_throws_exception_when_invoice_generation_fails(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to generate invoice: Invoice generation error');

        /** @var WorkOrder|MockInterface $workOrder */
        $workOrder = Mockery::mock(WorkOrder::class)->makePartial();
        $workOrder->status = WorkOrderStatus::COMPLETED->value;

        $this->workOrderRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('wo-123')
            ->andReturn($workOrder);

        $this->generateInvoiceActionMock
            ->shouldReceive('execute')
            ->once()
            ->with('wo-123')
            ->andThrow(new Exception('Invoice generation error'));

        $action = new MarkWorkOrderAsInvoicedAction(
            $this->workOrderRepositoryMock,
            $this->generateInvoiceActionMock,
            $this->invoiceRepositoryMock
        );

        $action->execute('wo-123');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
