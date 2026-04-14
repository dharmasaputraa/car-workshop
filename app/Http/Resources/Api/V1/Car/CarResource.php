<?php

namespace App\Http\Resources\Api\V1\Car;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Http\Resources\Api\V1\WorkOrder\WorkOrderResource;
use Illuminate\Http\Request;

class CarResource extends BaseJsonApiResource
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
        return 'cars';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'plate_number' => $this->plate_number,
            'brand'        => $this->brand,
            'model'        => $this->model,
            'year'         => $this->year,
            'color'        => $this->color,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'owner'      => UserResource::class,
            'workOrders' => WorkOrderResource::class,
        ];
    }
}
