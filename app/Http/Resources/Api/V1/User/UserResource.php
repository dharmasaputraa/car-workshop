<?php

namespace App\Http\Resources\Api\V1\User;

use App\Http\Resources\Api\V1\User\RoleResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
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
            'deleted_at'        => $this->whenNotNull($this->resource->deleted_at?->toIso8601String()),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'roles' => fn() => $this->whenLoaded('roles'),

            // Contoh: Relasi 'logs' hanya muncul jika user adalah Super Admin
            // 'logs' => fn () => $this->resource->isSuperAdmin() ? $this->whenLoaded('logs') : null,
        ];
    }

    public function toMeta(Request $request): array
    {
        return [
            'is_super_admin' => $this->resource->isSuperAdmin(),
        ];
    }
}
