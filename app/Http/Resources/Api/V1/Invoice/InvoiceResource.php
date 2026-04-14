<?php

namespace App\Http\Resources\Api\V1\Invoice;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use App\Http\Resources\Api\V1\WorkOrder\WorkOrderResource;
use Illuminate\Http\Request;

class InvoiceResource extends BaseJsonApiResource
{
    public function toId(Request $request): string
    {
        return (string) $this->id;
    }

    public function toType(Request $request): string
    {
        return 'invoices';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'invoice_number' => $this->invoice_number,
            'subtotal'       => (float) $this->subtotal,
            'discount'       => (float) $this->discount,
            'tax'            => (float) $this->tax,
            'total'          => (float) $this->total,
            'status'         => $this->status->value ?? $this->status,
            'due_date'       => $this->due_date?->format('Y-m-d'),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'workOrder' => WorkOrderResource::class,
        ];
    }
}
