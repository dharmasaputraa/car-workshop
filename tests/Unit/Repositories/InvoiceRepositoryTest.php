<?php

namespace Tests\Unit\Repositories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Repositories\Eloquent\InvoiceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class InvoiceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new InvoiceRepository();
    }

    /*
    |--------------------------------------------------------------------------
    | READ OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_get_paginated_invoices(): void
    {
        Invoice::factory()->count(20)->create();

        $result = $this->repository->getPaginatedInvoices();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result->items()); // Default PER_PAGE = 15
        $this->assertEquals(20, $result->total());
    }

    public function test_get_paginated_invoices_filters_by_status(): void
    {
        Invoice::factory()->count(10)->create(['status' => InvoiceStatus::UNPAID->value]);
        Invoice::factory()->count(5)->create(['status' => InvoiceStatus::PAID->value]);

        request()->merge(['filter' => ['status' => 'unpaid']]);

        $result = $this->repository->getPaginatedInvoices();

        $this->assertCount(10, $result->items());
        foreach ($result->items() as $invoice) {
            $this->assertEquals(InvoiceStatus::UNPAID->value, $invoice->status->value);
        }
    }

    public function test_get_paginated_invoices_filters_by_invoice_number(): void
    {
        Invoice::factory()->create(['invoice_number' => 'INV-2024-001']);
        Invoice::factory()->create(['invoice_number' => 'INV-2024-002']);
        Invoice::factory()->create(['invoice_number' => 'INV-2025-001']);

        request()->merge(['filter' => ['invoice_number' => '2024']]);

        $result = $this->repository->getPaginatedInvoices();

        $this->assertCount(2, $result->items());
    }

    public function test_find_by_id(): void
    {
        $invoice = Invoice::factory()->create();

        $result = $this->repository->findById($invoice->id);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertEquals($invoice->id, $result->id);
        $this->assertEquals($invoice->invoice_number, $result->invoice_number);
    }

    public function test_find_by_id_throws_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findById('invalid-uuid');
    }

    public function test_find_by_work_order_id(): void
    {
        $workOrder = WorkOrder::factory()->create();
        $invoice = Invoice::factory()->create(['work_order_id' => $workOrder->id]);

        $result = $this->repository->findByWorkOrderId($workOrder->id);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertEquals($invoice->id, $result->id);
        $this->assertEquals($workOrder->id, $result->work_order_id);
    }

    public function test_find_by_work_order_id_returns_null_when_not_found(): void
    {
        $result = $this->repository->findByWorkOrderId('non-existent-wo-id');

        $this->assertNull($result);
    }

    /*
    |--------------------------------------------------------------------------
    | WRITE OPERATIONS
    |--------------------------------------------------------------------------
    */

    public function test_create_invoice(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $data = [
            'invoice_number' => 'INV-2024-001',
            'work_order_id' => $workOrder->id,
            'subtotal' => 1000.00,
            'discount' => 100.00,
            'tax' => 90.00,
            'total' => 990.00,
            'status' => InvoiceStatus::UNPAID->value,
            'due_date' => '2024-05-01',
        ];

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-2024-001',
            'work_order_id' => $workOrder->id,
            'total' => 990.00,
            'status' => InvoiceStatus::UNPAID->value,
        ]);
        $this->assertNotNull($result->id);
    }

    public function test_create_invoice_uses_default_discount_and_tax(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $data = [
            'invoice_number' => 'INV-2024-002',
            'work_order_id' => $workOrder->id,
            'subtotal' => 500.00,
            'total' => 500.00,
            'status' => InvoiceStatus::UNPAID->value,
            'due_date' => '2024-05-01',
        ];

        $result = $this->repository->create($data);

        $this->assertEquals(0, $result->discount);
        $this->assertEquals(0, $result->tax);
    }

    public function test_update_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'total' => 1000.00,
            'status' => InvoiceStatus::UNPAID->value,
        ]);

        $result = $this->repository->update($invoice, [
            'total' => 900.00,
            'status' => InvoiceStatus::PAID->value,
        ]);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertEquals(900.00, $result->total);
        $this->assertEquals(InvoiceStatus::PAID->value, $result->status->value);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'total' => 900.00,
            'status' => InvoiceStatus::PAID->value,
        ]);
    }

    public function test_update_status(): void
    {
        $invoice = Invoice::factory()->create(['status' => InvoiceStatus::UNPAID->value]);

        $result = $this->repository->updateStatus($invoice, InvoiceStatus::PAID->value);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertEquals(InvoiceStatus::PAID->value, $result->status->value);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::PAID->value,
        ]);
    }

    public function test_delete_invoice(): void
    {
        $invoice = Invoice::factory()->create();
        $invoiceId = $invoice->id;

        $this->repository->delete($invoice);

        $this->assertDatabaseMissing('invoices', [
            'id' => $invoiceId,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function test_load_relations(): void
    {
        $invoice = Invoice::factory()->create();

        $result = $this->repository->loadRelations($invoice, ['workOrder']);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertTrue($result->relationLoaded('workOrder'));
    }

    public function test_load_missing_relations(): void
    {
        $invoice = Invoice::factory()->create();

        $result = $this->repository->loadMissingRelations($invoice, ['workOrder', 'workOrder.car']);

        $this->assertInstanceOf(Invoice::class, $result);
        $this->assertTrue($result->relationLoaded('workOrder'));
        $this->assertTrue($result->workOrder->relationLoaded('car'));
    }
}
