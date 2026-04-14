<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use HasFactory, HasUuids;

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTES & CONFIGURATION
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'owner_id',
        'plate_number',
        'brand',
        'model',
        'year',
        'color'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Owner of the car (User with Customer role).
     * users ||--o{ cars : "owns"
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * List of Work Orders for this car.
     * cars ||--o{ work_orders : "has"
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
