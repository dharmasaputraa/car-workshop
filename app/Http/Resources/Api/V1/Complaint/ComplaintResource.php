<?php

namespace App\Http\Resources\Api\V1\Complaint;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use App\Http\Resources\Api\V1\WorkOrder\WorkOrderResource;
use Illuminate\Http\Request;

class ComplaintResource extends BaseJsonApiResource
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
        return 'complaints';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'description'   => $this->description,
            'status'        => $this->status->value ?? $this->status,
            'in_progress_at' => $this->in_progress_at?->toIso8601String(),
            'resolved_at'    => $this->resolved_at?->toIso8601String(),
            'rejected_at'    => $this->rejected_at?->toIso8601String(),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'workOrder'         => WorkOrderResource::class,
            'complaintServices' => ComplaintServiceResource::class,
        ];
    }
}
