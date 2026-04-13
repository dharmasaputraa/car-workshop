<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RoleType: string implements HasColor, HasLabel
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::MEMBER => 'Member',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'success',
            self::ADMIN => 'info',
            self::MEMBER => 'gray',
        };
    }
}
