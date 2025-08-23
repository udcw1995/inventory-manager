<?php

namespace App\Observers;

use App\Enums\StockMovementSourceType;
use App\Models\Grn;
use App\Services\InventoryService;

class GrnObserver
{
    public function __construct(protected InventoryService $inventoryService) {}

    /**
     * Handle the Grn "deleted" event.
     */
    public function deleted(Grn $grn): void
    {
        // Reverse related stock movements
        foreach ($grn->items as $item) {
            $movements = $grn->stockMovements()->where('source_id', $grn->id)->where('product_id', $item->product_id)->get();
            foreach ($movements as $movement) {
                $this->inventoryService->reverseMovement($movement->id);
            }
        }
    }

    /**
     * Handle the Grn "restored" event.
     */
    public function restored(Grn $grn): void
    {
        // Re-post related stock movements
        foreach ($grn->items as $item) {
            $this->inventoryService->postIn(
                product: $item->product,
                qty: $item->qty,
                value: $item->unit_cost,
                sourceType: StockMovementSourceType::GRN,
                sourceId: $grn->id,
                occurredAt: $grn->delivered_at,
                meta: ['grn_item_id' => $item->id],
            );
        }
    }

    /**
     * Handle the Grn "force deleted" event.
     */
    public function forceDeleted(Grn $grn): void
    {
        //
    }
}
