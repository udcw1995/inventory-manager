<?php

namespace App\Models;

use App\Enums\BottleMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BottleMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'product_id',
        'type',
        'quantity',
        'invoice_id',
        'occurred_at',
        'meta',
    ];

    protected $casts = [
        'type' => BottleMovementType::class,
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
