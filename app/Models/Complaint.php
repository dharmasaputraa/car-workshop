<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
    use HasUuids;

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES & CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'work_order_id',
        'description',
        'status',
        'in_progress_at',
        'resolved_at',
        'rejected_at',
    ];

    protected $casts = [
        'status' => \App\Enums\ComplaintStatus::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * The Work Order that this complaint belongs to.
     * work_orders ||--o{ complaints : "has"
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * The specific services required to resolve this complaint.
     * complaints ||--o{ complaint_services : "requires"
     */
    public function complaintServices(): HasMany
    {
        return $this->hasMany(ComplaintService::class);
    }
}
