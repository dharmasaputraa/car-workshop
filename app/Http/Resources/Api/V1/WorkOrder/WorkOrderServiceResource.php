<?php

namespace App\Http\Resources\Api\V1\WorkOrder;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use App\Http\Resources\Api\V1\Mechanic\MechanicAssignmentResource;
use App\Http\Resources\Api\V1\Service\ServiceResource;
use Illuminate\Http\Request;

class WorkOrderServiceResource extends BaseJsonApiResource
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
        return 'work_order_services';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'status'     => $this->status->value ?? $this->status,
            'price'      => $this->price,
            'notes'      => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'service'            => ServiceResource::class,
            'mechanicAssignments' => MechanicAssignmentResource::class,
        ];
    }
}
