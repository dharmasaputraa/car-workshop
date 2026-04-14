<?php

namespace App\Http\Resources\Api\V1\WorkOrder;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use App\Http\Resources\Api\V1\Car\CarResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Resources\Api\V1\Complaint\ComplaintResource;
use App\Http\Resources\Api\V1\Invoice\InvoiceResource;

class WorkOrderResource extends BaseJsonApiResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->includePreviouslyLoadedRelationships();
    }

    public function toId(Request $request): string
    {
        return (string) $this->id;
    }

    public function toType(Request $request): string
    {
        return 'work_orders';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'order_number'         => $this->order_number,
            'status'               => $this->status->value,
            'diagnosis_notes'      => $this->diagnosis_notes,
            'estimated_completion' => $this->estimated_completion?->toDateString(),
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'car'               => CarResource::class,
            'creator'           => UserResource::class,
            'workOrderServices' => WorkOrderServiceResource::class,
            'complaints'        => ComplaintResource::class,
            'invoice'           => InvoiceResource::class,
        ];
    }

    public function toMeta(Request $request): array
    {
        return [
            'is_overdue' => $this->estimated_completion
                && $this->estimated_completion->isPast()
                && $this->status->value !== 'completed',
        ];
    }
}
