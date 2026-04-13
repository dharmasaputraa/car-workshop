<?php

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserAuthResource extends JsonApiResource
{
    public function __construct(
        mixed $resource,
        protected readonly array $tokenData = [],
        protected readonly bool $isNew = false,
    ) {
        parent::__construct($resource);
    }

    public function toId(Request $request): string
    {
        return (string) $this->resource->getKey();
    }

    public function toType(Request $request): string
    {
        return 'users';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'name'              => $this->resource->name,
            'email'             => $this->resource->email,
            'avatar_url'        => $this->resource->getFilamentAvatarUrl(),
            'is_active'         => $this->resource->is_active,
            'email_verified_at' => $this->resource->email_verified_at?->toIso8601String(),
            'created_at'        => $this->resource->created_at?->toIso8601String(),
            'updated_at'        => $this->resource->updated_at?->toIso8601String(),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'roles' => fn() => $this->whenLoaded('roles'),
        ];
    }

    public function toMeta(Request $request): array
    {
        return array_filter([
            'is_super_admin' => $this->resource->isSuperAdmin(),
        ], fn($v) => $v !== null);
    }

    public function with($request): array
    {
        $meta = array_filter([
            'is_new' => $this->isNew ?: null,
            'token'  => $this->tokenData ?: null,
        ]);

        return array_filter([
            'meta' => $meta ?: null,
        ]);
    }
}
