<?php

namespace App\Models;

use App\Enums\WorkOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkOrder extends Model
{
    use HasFactory, HasUuids;

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES & CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'order_number',
        'car_id',
        'created_by',
        'status',
        'diagnosis_notes',
        'estimated_completion'
    ];

    protected $casts = [
        'status' => WorkOrderStatus::class,
        'estimated_completion' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * The car associated with this work order.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * The admin/user who created this work order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The specific services proposed/done in this work order.
     * work_orders ||--o{ work_order_services : "includes"
     */
    public function workOrderServices(): HasMany
    {
        // Menggunakan nama relasi yang spesifik agar tidak rancu dengan master `Service`
        return $this->hasMany(WorkOrderService::class);
    }

    /**
     * Complaints related to this work order.
     * work_orders ||--o{ complaints : "has"
     */
    public function complaints(): HasMany
    {
        // Akan berguna di Phase 4
        return $this->hasMany(Complaint::class);
    }

    /**
     * Invoice for this work order.
     * work_orders ||--o| invoices : "billed via"
     */
    public function invoice(): HasOne
    {
        // Akan berguna di Phase 5
        return $this->hasOne(Invoice::class);
    }
}
