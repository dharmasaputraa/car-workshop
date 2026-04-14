<?php

namespace App\Models;

use App\Enums\ServiceItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderService extends Model
{
    use HasFactory, HasUuids;

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES & CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'work_order_id',
        'service_id',
        'price',
        'status',
        'notes'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => ServiceItemStatus::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * The parent work order.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * The master service reference.
     */
    public function service(): BelongsTo
    {
        // Diubah dari serviceData() menjadi service() agar lebih konvensional di Laravel
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * Mechanic assignments for this specific work order service.
     * work_order_services ||--o{ mechanic_assignments : "assigned to"
     */
    public function mechanicAssignments(): HasMany
    {
        return $this->hasMany(MechanicAssignment::class, 'work_order_service_id');
    }
}
