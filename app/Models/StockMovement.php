<?php

namespace App\Models;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'direction',
        'quantity',
        'value',
        'source_type',
        'source_id',
        'occurred_at',
        'reversal_of_id',
        'meta',
    ];

    protected $casts = [
        'direction' => StockMovementDirection::class,
        'value' => 'decimal:2',
        'source_type' => StockMovementSourceType::class,
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'reversal_of_id');
    }
}
