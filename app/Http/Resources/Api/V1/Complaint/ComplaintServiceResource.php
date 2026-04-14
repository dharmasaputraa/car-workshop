<?php

namespace App\Http\Resources\Api\V1\Complaint;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use App\Http\Resources\Api\V1\Service\ServiceResource;
use Illuminate\Http\Request;

class ComplaintServiceResource extends BaseJsonApiResource
{
    public function toId(Request $request): string
    {
        return (string) $this->id;
    }

    public function toType(Request $request): string
    {
        return 'complaint_services';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'price'        => (float) $this->price,
            'status'       => $this->status->value ?? $this->status,
            'status_label' => $this->status->getLabel(),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'complaint' => ComplaintResource::class,
            'service'   => ServiceResource::class,
        ];
    }
}
