<?php

namespace App\Models;

use App\Enums\RoleType;
use App\Notifications\CustomResetPasswordNotification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable as BreezyTwoFactorAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements HasAvatar, FilamentUser, HasMedia, JWTSubject
{
    use HasFactory,
        Notifiable,
        HasRoles,
        BreezyTwoFactorAuthenticatable,
        InteractsWithMedia,
        SoftDeletes,
        HasUuids;

    /**
     * Forces Spatial Permission to use the 'api' guard by default
     * for this model, even if called from a web/Filament context.
     */
    protected $guard_name = 'api';

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

    public function isMechanic(): bool
    {
        return $this->hasRole(RoleType::MECHANIC->value);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(RoleType::CUSTOMER->value);
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
        return !str_ends_with($this->email, '@carworkshop.com');
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
    | JWT
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
        return [
            'role' => $this->roles->first()?->name,
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->toArray(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification());
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * List of cars owned by the user (as Customer).
     * users ||--o{ cars : "owns"
     */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class, 'owner_id');
    }

    /**
     * List of Work Orders created by the user (as Admin/Mechanic).
     * users ||--o{ work_orders : "creates"
     */
    public function workOrdersCreated(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'created_by');
    }

    /**
     * List of work assignments if the user is a mechanic.
     * users ||--o{ mechanic_assignments : "performs as mechanic"
     */
    public function mechanicAssignments(): HasMany
    {
        return $this->hasMany(MechanicAssignment::class, 'mechanic_id');
    }
}
