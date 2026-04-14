<?php

namespace App\Http\Resources\Api\V1\Service;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use Illuminate\Http\Request;

class ServiceResource extends BaseJsonApiResource
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
        return 'services';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'name'        => $this->name,
            'description' => $this->description,
            'base_price'  => (float) $this->base_price,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        // Biasanya master service tidak di-include ke bawah kecuali untuk reporting admin
        return [];
    }
}
