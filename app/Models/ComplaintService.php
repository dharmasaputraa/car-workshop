<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
