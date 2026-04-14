<?php

namespace App\Http\Resources\Api\V1\User;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class RoleResource extends BaseJsonApiResource
{
    public function toAttributes(Request $request): array
    {
        return [
            'name'       => $this->name,
            'guard_name' => $this->guard_name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
