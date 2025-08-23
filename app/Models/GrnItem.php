<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_id',
        'product_id',
        'qty',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(Grn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
