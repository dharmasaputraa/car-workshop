<?php

namespace App\Http\Resources\Api\V1\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * Override method toAttributes untuk mendefinisikan data dinamis.
     * (Hapus properti public $attributes dari hasil generate artisan)
     *
     * @param Request $request
     * @return array
     */
    #[\Override]
    public function toAttributes(Request $request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'avatar-url' => $this->getFilamentAvatarUrl(),
            'is-active' => $this->is_active,
            'role-name' => $this->role_name,
            'created-at' => $this->created_at,
            'updated-at' => $this->updated_at,
            'deleted-at' => $this->when($this->deleted_at !== null, $this->deleted_at),
        ];
    }

    /**
     * Override method toRelationships untuk menangani relasi JSON:API.
     * (Hapus properti public $relationships dari hasil generate artisan)
     *
     * @param Request $request
     * @return array
     */
    #[\Override]
    public function toRelationships(Request $request): array
    {
        return [
            // Gunakan closure agar relasi tidak dieksekusi jika tidak di-load (mencegah N+1)
            'roles' => fn() => $this->whenLoaded('roles', function () {
                // Asumsi Anda punya RoleResource, jika tidak, cukup return mapping array
                return $this->roles->map(fn($role) => [
                    'type' => 'roles',
                    'id' => (string) $role->id,
                    'attributes' => [
                        'name' => $role->name,
                    ]
                ]);
            }),
        ];
    }

    /**
     * Opsional: Override toLinks jika Anda ingin menambahkan link kustom
     * selain link standar yang mungkin di-generate Laravel.
     */
    #[\Override]
    public function toLinks(Request $request): array
    {
        return [
            'self' => route('api.v1.users.show', ['user' => $this->id]),
        ];
    }
}
