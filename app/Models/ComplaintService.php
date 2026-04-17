<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class ComplaintService extends Model
{
    use HasFactory, HasUuids;

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES & CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'complaint_id',
        'service_id',
        'status',
        'description',
        'price',
    ];

    protected $casts = [
        'status' => \App\Enums\ServiceItemStatus::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * The parent complaint.
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * The master service reference used for this complaint resolution.
     * services ||--o{ complaint_services : "used in"
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Mechanic assignments for this complaint service.
     * complaint_services ||--o{ mechanic_assignments : "assigned to"
     */
    public function mechanicAssignments(): HasMany
    {
        return $this->hasMany(MechanicAssignment::class, 'complaint_service_id');
    }

    /**
     * Get the assigned mechanics for this complaint service.
     * Uses hasManyThrough to traverse complaint_service → mechanic_assignments → users (mechanics)
     */
    public function mechanics()
    {
        return $this->hasManyThrough(
            User::class,                // Target model
            MechanicAssignment::class,   // Through model
            'complaint_service_id',      // FK on mechanic_assignments → complaint_services
            'mechanic_id',               // FK on mechanic_assignments → users
            'id',                        // Local key on complaint_services
            'id'                         // Local key on users
        );
    }
}
