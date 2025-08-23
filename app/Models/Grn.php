<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grn extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'delivery_person_name',
        'delivery_person_contact',
        'vehicle_no',
        'delivered_at',
        'total_cost',
        'total_items',
        'total_refillable',
        'total_non_refillable',
        'created_by',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'total_cost' => 'decimal:2',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'source_id')->where('source_type', \App\Enums\StockMovementSourceType::GRN);
    }
}
