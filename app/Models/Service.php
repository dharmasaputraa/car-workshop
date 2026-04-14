<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasUuids, HasFactory;

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES & CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'name',
        'description',
        'base_price',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_price' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * List of Work Order Services that use this master Service.
     * services ||--o{ work_order_services : "used in"
     */
    public function workOrderServices(): HasMany
    {
        return $this->hasMany(WorkOrderService::class, 'service_id');
    }

    /**
     * List of Complaint Services that use this master Service.
     * services ||--o{ complaint_services : "used in"
     */
    public function complaintServices(): HasMany
    {
        // Akan berguna saat masuk ke Phase 4
        return $this->hasMany(ComplaintService::class, 'service_id');
    }
}
