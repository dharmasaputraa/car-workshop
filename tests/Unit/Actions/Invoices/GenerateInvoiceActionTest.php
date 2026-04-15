<?php

namespace Tests\Unit\Actions\Invoices;

use App\Actions\Invoices\GenerateInvoiceAction;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GenerateInvoiceActionTest extends TestCase
{
    /** @var MockInterface|InvoiceService */
    protected $invoiceServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceServiceMock = Mockery::mock(InvoiceService::class);
    }

    public function test_generate_invoice_delegates_to_service(): void
    {
        $workOrderId = 'work-order-123';
        $discount = 10.00;
        $tax = 5.00;
        $dueDate = '2026-05-01';

        $invoice = new Invoice();

        $this->invoiceServiceMock
            ->shouldReceive('generateInvoice')
            ->once()
            ->with($workOrderId, $discount, $tax, $dueDate)
            ->andReturn($invoice);

        $action = new GenerateInvoiceAction($this->invoiceServiceMock);
        $result = $action->execute($workOrderId, $discount, $tax, $dueDate);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertSame($invoice, $result);
    }

    public function test_generate_invoice_with_defaults(): void
    {
        $workOrderId = 'work-order-123';

        $invoice = new Invoice();

        $this->invoiceServiceMock
            ->shouldReceive('generateInvoice')
            ->once()
            ->with($workOrderId, 0.0, 0.0, null)
            ->andReturn($invoice);

        $action = new GenerateInvoiceAction($this->invoiceServiceMock);
        $result = $action->execute($workOrderId);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertSame($invoice, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
