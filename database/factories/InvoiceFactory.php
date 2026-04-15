<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_number' => 'INV-' . strtoupper($this->faker->unique()->lexify('????????')),
            'work_order_id' => WorkOrder::factory(),
            'subtotal' => $this->faker->randomFloat(2, 100, 1000),
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => function (array $attributes) {
                return $attributes['subtotal'] - $attributes['discount'] + $attributes['tax'];
            },
            'status' => InvoiceStatus::DRAFT->value,
            'due_date' => now()->addDays(14),
        ];
    }

    public function draft(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => InvoiceStatus::DRAFT->value,
        ]);
    }

    public function unpaid(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => InvoiceStatus::UNPAID->value,
        ]);
    }

    public function paid(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => InvoiceStatus::PAID->value,
        ]);
    }

    public function canceled(): self
    {
        return $this->state(fn(array $attributes) => [
            'status' => InvoiceStatus::CANCELED->value,
        ]);
    }

    public function withDiscount(float $discount): self
    {
        return $this->state(function (array $attributes) use ($discount) {
            return [
                'discount' => $discount,
                'total' => $attributes['subtotal'] - $discount + $attributes['tax'],
            ];
        });
    }

    public function withTax(float $tax): self
    {
        return $this->state(function (array $attributes) use ($tax) {
            return [
                'tax' => $tax,
                'total' => $attributes['subtotal'] - $attributes['discount'] + $tax,
            ];
        });
    }
}
