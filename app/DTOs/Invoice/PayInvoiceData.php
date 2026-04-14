<?php

namespace App\DTOs\Invoice;

use Illuminate\Http\Request;

class PayInvoiceData
{
    public function __construct(
        public ?string $paymentMethod = null,
        public ?string $paymentReference = null,
        public ?string $paymentNotes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            paymentMethod: $data['payment_method'] ?? null,
            paymentReference: $data['payment_reference'] ?? null,
            paymentNotes: $data['payment_notes'] ?? null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            paymentMethod: $request->input('payment_method'),
            paymentReference: $request->input('payment_reference'),
            paymentNotes: $request->input('payment_notes'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'payment_method'     => $this->paymentMethod,
            'payment_reference'  => $this->paymentReference,
            'payment_notes'      => $this->paymentNotes,
        ], fn($value) => $value !== null && $value !== '');
    }
}
