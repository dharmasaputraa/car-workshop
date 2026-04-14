<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintService extends Model
{
    use HasUuids;

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES & CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'complaint_id',
        'service_id',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
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
}
