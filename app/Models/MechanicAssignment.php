<?php

namespace App\Models;

use App\Enums\MechanicAssignmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MechanicAssignment extends Model
{
    use HasUuids;

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES & CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'work_order_service_id',
        'mechanic_id',
        'assigned_at',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'assigned_at'  => 'datetime',
        'completed_at' => 'datetime',
        'status' => MechanicAssignmentStatus::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * The specific work order service item this mechanic is assigned to.
     */
    public function workOrderService(): BelongsTo
    {
        return $this->belongsTo(WorkOrderService::class, 'work_order_service_id');
    }

    /**
     * The mechanic (User) assigned to do the job.
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}
