<?php

namespace App\Http\Resources\Api\V1\Complaint;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use App\Http\Resources\Api\V1\Service\ServiceResource;
use App\Http\Resources\Api\V1\User\UserResource;
use Illuminate\Http\Request;

class ComplaintServiceResource extends BaseJsonApiResource
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
        return 'complaint-services';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'status'      => $this->status->value ?? $this->status,
            'description' => $this->description,
            'price'       => $this->price ? (float) $this->price : null,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'complaint' => ComplaintResource::class,
            'service'   => ServiceResource::class,
            'mechanic'  => UserResource::class,
        ];
    }
}
