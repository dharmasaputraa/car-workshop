<?php

namespace App\DTOs\Invoice;

use Illuminate\Http\Request;

class GenerateInvoiceData
{
    public function __construct(
        public string  $workOrderId,
        public ?string $invoiceNumber = null,
        public ?float  $discount = 0.0,
        public ?float  $tax = 0.0,
        public ?string $dueDate = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            workOrderId: $data['work_order_id'],
            invoiceNumber: $data['invoice_number'] ?? null,
            discount: (float) ($data['discount'] ?? 0),
            tax: (float) ($data['tax'] ?? 0),
            dueDate: $data['due_date'] ?? null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            workOrderId: $request->input('work_order_id'),
            invoiceNumber: $request->input('invoice_number'),
            discount: (float) $request->input('discount', 0),
            tax: (float) $request->input('tax', 0),
            dueDate: $request->input('due_date'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'work_order_id'   => $this->workOrderId,
            'invoice_number'  => $this->invoiceNumber,
            'discount'        => $this->discount,
            'tax'             => $this->tax,
            'due_date'        => $this->dueDate,
        ], fn($value) => $value !== null && $value !== '');
    }
}
