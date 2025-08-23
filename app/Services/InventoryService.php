<?php

namespace App\Services;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementSourceType;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function postIn(
        Product $product,
        int $qty,
        float $value,
        StockMovementSourceType $sourceType,
        int $sourceId,
        Carbon $occurredAt,
        array $meta = [],
    ): StockMovement {
        return DB::transaction(function () use ($product, $qty, $value, $sourceType, $sourceId, $occurredAt, $meta) {
            return StockMovement::create([
                'product_id' => $product->id,
                'direction' => StockMovementDirection::IN,
                'quantity' => $qty,
                'value' => $value,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'occurred_at' => $occurredAt,
                'meta' => $meta,
            ]);
        });
    }

    public function postOut(
        Product $product,
        int $qty,
        float $value,
        StockMovementSourceType $sourceType,
        int $sourceId,
        Carbon $occurredAt,
        array $meta = [],
    ): StockMovement {
        return DB::transaction(function () use ($product, $qty, $value, $sourceType, $sourceId, $occurredAt, $meta) {
            return StockMovement::create([
                'product_id' => $product->id,
                'direction' => StockMovementDirection::OUT,
                'quantity' => $qty,
                'value' => $value,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'occurred_at' => $occurredAt,
                'meta' => $meta,
            ]);
        });
    }

    public function reverseMovement(int $movementId): StockMovement
    {
        return DB::transaction(function () use ($movementId) {
            $originalMovement = StockMovement::findOrFail($movementId);

            $reversedDirection = match ($originalMovement->direction) {
                StockMovementDirection::IN => StockMovementDirection::OUT,
                StockMovementDirection::OUT => StockMovementDirection::IN,
                StockMovementDirection::ADJUST => StockMovementDirection::ADJUST, // Adjustments are reversed by another adjustment
            };

            return StockMovement::create([
                'product_id' => $originalMovement->product_id,
                'direction' => $reversedDirection,
                'quantity' => $originalMovement->quantity,
                'value' => $originalMovement->value,
                'source_type' => $originalMovement->source_type,
                'source_id' => $originalMovement->source_id,
                'occurred_at' => Carbon::now(), // Reversal occurs now
                'reversal_of_id' => $originalMovement->id,
                'meta' => array_merge($originalMovement->meta, ['reversed_at' => Carbon::now()->toDateTimeString()]),
            ]);
        });
    }

    public function stockOnHand(Product $product): int
    {
        $in = StockMovement::where('product_id', $product->id)
            ->where('direction', StockMovementDirection::IN)
            ->whereNull('reversal_of_id')
            ->sum('quantity');

        $out = StockMovement::where('product_id', $product->id)
            ->where('direction', StockMovementDirection::OUT)
            ->whereNull('reversal_of_id')
            ->sum('quantity');

        // Account for reversals: sum quantities of movements that are reversals of other movements
        $reversedIn = StockMovement::where('product_id', $product->id)
            ->where('direction', StockMovementDirection::IN)
            ->whereNotNull('reversal_of_id')
            ->sum('quantity');

        $reversedOut = StockMovement::where('product_id', $product->id)
            ->where('direction', StockMovementDirection::OUT)
            ->whereNotNull('reversal_of_id')
            ->sum('quantity');

        return ($in - $reversedOut) - ($out - $reversedIn);
    }
}
