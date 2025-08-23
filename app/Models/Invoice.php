<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'shop_id',
        'issued_at',
        'total_cost',
        'fines_total',
        'final_total',
        'total_items',
        'total_refillable',
        'total_non_refillable',
        'returned_refillable_total',
        'damaged_lost_total',
        'issued_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'total_cost' => 'decimal:2',
        'fines_total' => 'decimal:2',
        'final_total' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'source_id')->where('source_type', \App\Enums\StockMovementSourceType::INVOICE);
    }

    public function bottleMovements(): HasMany
    {
        return $this->hasMany(BottleMovement::class, 'invoice_id');
    }
}
