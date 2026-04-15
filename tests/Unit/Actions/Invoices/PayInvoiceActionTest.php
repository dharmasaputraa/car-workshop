<?php

namespace Tests\Unit\Actions\Invoices;

use App\Actions\Invoices\PayInvoiceAction;
use App\Enums\InvoiceStatus;
use App\Events\PaymentReceived;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\InvoiceService;
use Exception;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PayInvoiceActionTest extends TestCase
{
    protected MockInterface|InvoiceRepositoryInterface $invoiceRepositoryMock;
    protected MockInterface|InvoiceService $invoiceServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceRepositoryMock = Mockery::mock(InvoiceRepositoryInterface::class);
        $this->invoiceServiceMock = Mockery::mock(InvoiceService::class);
    }

    public function test_pay_unpaid_invoice_successfully(): void
    {
        Event::fake();

        /** @var Invoice|MockInterface $invoice */
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $invoice->id = 'invoice-123';
        $invoice->status = InvoiceStatus::UNPAID->value;
        $invoice->shouldReceive('setAttribute')->passthru();
        $invoice->shouldReceive('loadMissing')->andReturnSelf();

        $this->invoiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('invoice-123')
            ->andReturn($invoice);

        $this->invoiceServiceMock
            ->shouldReceive('validateCanPay')
            ->once()
            ->with($invoice);

        $this->invoiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->with($invoice, InvoiceStatus::PAID->value)
            ->andReturn($invoice);

        $action = new PayInvoiceAction(
            $this->invoiceRepositoryMock,
            $this->invoiceServiceMock
        );

        $result = $action->execute(
            'invoice-123',
            'transfer',
            'REF-123',
            'Payment received'
        );

        $this->assertSame($invoice, $result);
        Event::assertDispatched(PaymentReceived::class, function ($event) use ($invoice) {
            return $event->invoice === $invoice;
        });
    }

    public function test_pay_throws_exception_when_not_unpaid(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invoice cannot be paid in its current status.');

        /** @var Invoice|MockInterface $invoice */
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $invoice->id = 'invoice-123';
        $invoice->status = InvoiceStatus::PAID->value;
        $invoice->shouldReceive('setAttribute')->passthru();

        $this->invoiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('invoice-123')
            ->andReturn($invoice);

        $this->invoiceServiceMock
            ->shouldReceive('validateCanPay')
            ->once()
            ->with($invoice)
            ->andThrow(new Exception('Invoice cannot be paid in its current status.'));

        $action = new PayInvoiceAction(
            $this->invoiceRepositoryMock,
            $this->invoiceServiceMock
        );

        $action->execute('invoice-123');
    }

    public function test_pay_invoice_with_optional_params(): void
    {
        Event::fake();

        /** @var Invoice|MockInterface $invoice */
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $invoice->id = 'invoice-123';
        $invoice->status = InvoiceStatus::UNPAID->value;
        $invoice->shouldReceive('setAttribute')->passthru();
        $invoice->shouldReceive('loadMissing')->andReturnSelf();

        $this->invoiceRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with('invoice-123')
            ->andReturn($invoice);

        $this->invoiceServiceMock
            ->shouldReceive('validateCanPay')
            ->once()
            ->with($invoice);

        $this->invoiceRepositoryMock
            ->shouldReceive('updateStatus')
            ->once()
            ->andReturn($invoice);

        $action = new PayInvoiceAction(
            $this->invoiceRepositoryMock,
            $this->invoiceServiceMock
        );

        $result = $action->execute('invoice-123');

        $this->assertSame($invoice, $result);
        Event::assertDispatched(PaymentReceived::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
