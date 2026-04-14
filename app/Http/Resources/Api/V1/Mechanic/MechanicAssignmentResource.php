<?php

namespace App\Http\Resources\Api\V1\Mechanic;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Resources\Api\V1\WorkOrder\WorkOrderServiceResource;
use Illuminate\Http\Request;

class MechanicAssignmentResource extends BaseJsonApiResource
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
        return 'mechanic_assignments';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'status'       => $this->status->value ?? $this->status,
            'assigned_at'  => $this->assigned_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'workOrderService' => WorkOrderServiceResource::class,
            'mechanic'         => UserResource::class,
        ];
    }
}
