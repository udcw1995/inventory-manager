<?php

namespace App\Observers;

use App\Enums\StockMovementSourceType;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\BottleService;
use App\Services\InventoryService;

class InvoiceObserver
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected BottleService $bottleService,
    ) {}

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        // Reverse related stock movements
        foreach ($invoice->items as $item) {
            $movements = $invoice->stockMovements()->where('source_id', $invoice->id)->where('product_id', $item->product_id)->get();
            foreach ($movements as $movement) {
                $this->inventoryService->reverseMovement($movement->id);
            }
        }

        // Delete associated bottle movements
        $invoice->bottleMovements()->delete();

        $this->bottleService->recalculateShopBalance($invoice->shop_id);
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        // Re-post related stock movements
        foreach ($invoice->items as $item) {
            $this->inventoryService->postOut(
                product: $item->product,
                qty: $item->qty,
                value: $item->unit_price,
                sourceType: StockMovementSourceType::INVOICE,
                sourceId: $invoice->id,
                occurredAt: $invoice->issued_at,
                meta: ['invoice_item_id' => $item->id],
            );
        }

        // Re-create bottle movements based on invoice items
        foreach ($invoice->items as $item) {
            $product = Product::find($item->product_id);

            if ($product->is_refillable) {
                // DELIVERED = sum of refillable qty
                $this->bottleService->deliver(
                    shop: $invoice->shop,
                    product: $product,
                    qty: $item->qty,
                    invoice: $invoice,
                    occurredAt: $invoice->issued_at,
                );

                // RETURNED = sum of returned_empty
                if ($item->returned_empty > 0) {
                    $this->bottleService->returned(
                        shop: $invoice->shop,
                        product: $product,
                        qty: $item->returned_empty,
                        invoice: $invoice,
                        occurredAt: $invoice->issued_at,
                    );
                }

                // DAMAGED = sum of damaged_lost
                if ($item->damaged_lost > 0) {
                    $this->bottleService->damaged(
                        shop: $invoice->shop,
                        product: $product,
                        qty: $item->damaged_lost,
                        invoice: $invoice,
                        occurredAt: $invoice->issued_at,
                    );
                }
            }
        }

        $this->bottleService->recalculateShopBalance($invoice->shop_id);
    }

    /**
     * Handle the Invoice "force deleted" event.
     */
    public function forceDeleted(Invoice $invoice): void
    {
        //
    }
}
