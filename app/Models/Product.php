<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'flavor',
        'is_refillable',
        'purchase_cost',
        'selling_price',
        'fine_per_damaged',
        'active',
    ];

    protected $casts = [
        'is_refillable' => 'boolean',
        'purchase_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'fine_per_damaged' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function bottleMovements(): HasMany
    {
        return $this->hasMany(BottleMovement::class);
    }
}
