<?php

namespace App\DTOs\User\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

readonly class UploadAvatarData
{
    public function __construct(
        public UploadedFile $avatar,
    ) {}

    /**
     * Create a new DTO instance from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            avatar: $data['avatar'],
        );
    }

    /**
     * Create a new DTO instance directly from a Laravel Request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            avatar: $request->file('avatar'),
        );
    }

    /**
     * Convert the DTO back into an associative array.
     */
    public function toArray(): array
    {
        return [
            'avatar' => $this->avatar,
        ];
    }
}
