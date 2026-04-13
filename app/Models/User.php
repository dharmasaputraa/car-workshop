<?php

namespace App\Models;

use App\Enums\RoleType;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable as BreezyTwoFactorAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasAvatar, FilamentUser, HasMedia
{
    use HasFactory,
        Notifiable,
        HasRoles,
        BreezyTwoFactorAuthenticatable,
        InteractsWithMedia,
        SoftDeletes,
        HasUuids;

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES & CONFIGURATION
    |--------------------------------------------------------------------------
    */

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'is_active',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOTED METHOD
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        // Otomatis hapus file lama di S3 saat avatar diupdate
        static::updating(function ($user) {
            if ($user->isDirty('avatar_url') && ($user->getOriginal('avatar_url') !== null)) {
                Storage::disk('s3')->delete($user->getOriginal('avatar_url'));
            }
        });

        // Otomatis hapus file di S3 saat user di hapus permanen
        static::deleted(function ($user) {
            if (!$user->deleted_at || $user->isForceDeleting()) {
                Storage::disk('s3')->delete($user->avatar_url);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES & GLOBAL QUERIES
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('roles');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    public function getFilamentAvatarUrl(): ?string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        return $this->avatar_url
            ? $disk->url($this->avatar_url)
            : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?s=200&d=mp&r=pg';
    }

    /*
    |--------------------------------------------------------------------------
    | PERMISSIONS & ROLES LOGIC
    |--------------------------------------------------------------------------
    */

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasAnyRole([
                RoleType::SUPER_ADMIN->value,
                RoleType::ADMIN->value,
            ]),
            default => false,
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleType::SUPER_ADMIN->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleType::ADMIN->value);
    }

    public function isMember(): bool
    {
        return $this->hasRole(RoleType::MEMBER->value);
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->roles->first()?->name;
    }

    /*
    |--------------------------------------------------------------------------
    | IMPERSONATION
    |--------------------------------------------------------------------------
    */

    public function canImpersonate(): bool
    {
        return $this->isSuperAdmin() || $this->hasPermissionTo('impersonate_user');
    }

    public function canBeImpersonated(): bool
    {
        // Cegah impersonate email perusahaan internal
        return !str_ends_with($this->email, '@mycorp.com');
    }

    /*
    |--------------------------------------------------------------------------
    | MEDIA COLLECTIONS
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile()
            ->useDisk('s3');
    }


        /*
        |--------------------------------------------------------------------------
        | MEDIA COLLECTIONS
        |--------------------------------------------------------------------------
        */

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
