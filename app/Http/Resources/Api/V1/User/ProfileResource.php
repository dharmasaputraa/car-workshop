<?php

namespace App\Http\Resources\Api\V1\User;

use App\Http\Resources\Api\V1\BaseJsonApiResource;
use Illuminate\Http\Request;

class ProfileResource extends BaseJsonApiResource
{
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->includePreviouslyLoadedRelationships();
    }

    public function toType(Request $request): string
    {
        return 'profiles';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'name'              => $this->name,
            'email'             => $this->email,
            'avatar_url'        => $this->getFilamentAvatarUrl(),
            'is_active'         => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
            'deleted_at'        => $this->whenNotNull($this->deleted_at?->toIso8601String()),
        ];
    }

    public function toRelationships(Request $request): array
    {
        return [
            'roles' => RoleResource::class,
        ];
    }

    public function toMeta(Request $request): array
    {
        return [
            'is_super_admin' => $this->isSuperAdmin(),
        ];
    }
}
