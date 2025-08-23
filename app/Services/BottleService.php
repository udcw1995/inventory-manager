<?php

namespace App\Services;

use App\Enums\BottleMovementType;
use App\Models\BottleMovement;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BottleService
{
    public function deliver(
        Shop $shop,
        Product $product,
        int $qty,
        ?Invoice $invoice = null,
        ?Carbon $occurredAt = null,
    ): BottleMovement {
        return DB::transaction(function () use ($shop, $product, $qty, $invoice, $occurredAt) {
            $movement = BottleMovement::create([
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'type' => BottleMovementType::DELIVERED,
                'quantity' => $qty,
                'invoice_id' => $invoice?->id,
                'occurred_at' => $occurredAt ?? Carbon::now(),
                'meta' => [],
            ]);

            $this->recalculateShopBalance($shop->id);

            return $movement;
        });
    }

    public function returned(
        Shop $shop,
        Product $product,
        int $qty,
        ?Invoice $invoice = null,
        ?Carbon $occurredAt = null,
    ): BottleMovement {
        return DB::transaction(function () use ($shop, $product, $qty, $invoice, $occurredAt) {
            $movement = BottleMovement::create([
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'type' => BottleMovementType::RETURNED,
                'quantity' => $qty,
                'invoice_id' => $invoice?->id,
                'occurred_at' => $occurredAt ?? Carbon::now(),
                'meta' => [],
            ]);

            $this->recalculateShopBalance($shop->id);

            return $movement;
        });
    }

    public function damaged(
        Shop $shop,
        Product $product,
        int $qty,
        ?Invoice $invoice = null,
        ?Carbon $occurredAt = null,
    ): BottleMovement {
        return DB::transaction(function () use ($shop, $product, $qty, $invoice, $occurredAt) {
            $movement = BottleMovement::create([
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'type' => BottleMovementType::DAMAGED,
                'quantity' => $qty,
                'invoice_id' => $invoice?->id,
                'occurred_at' => $occurredAt ?? Carbon::now(),
                'meta' => [],
            ]);

            $this->recalculateShopBalance($shop->id);

            return $movement;
        });
    }

    public function recalculateShopBalance(int $shopId): int
    {
        return DB::transaction(function () use ($shopId) {
            $shop = Shop::findOrFail($shopId);

            $delivered = BottleMovement::where('shop_id', $shopId)
                ->where('type', BottleMovementType::DELIVERED)
                ->sum('quantity');

            $returned = BottleMovement::where('shop_id', $shopId)
                ->where('type', BottleMovementType::RETURNED)
                ->sum('quantity');

            $damaged = BottleMovement::where('shop_id', $shopId)
                ->where('type', BottleMovementType::DAMAGED)
                ->sum('quantity');

            // Assuming LOST and ADJUSTMENT are also possible, though not explicitly requested for recalculation
            // If they should affect balance, add them here.
            // For now, only delivered, returned, and damaged are considered for the balance.

            $balance = $delivered - $returned - $damaged;

            $shop->update(['refillable_balance' => $balance]);

            return $balance;
        });
    }
}
