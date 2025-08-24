<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Enums\StockMovementSourceType;
use App\Filament\Resources\InvoiceResource;
use App\Models\Product;
use App\Services\BottleService;
use App\Services\InventoryService;
use App\Services\NumberGeneratorService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $numberGeneratorService = app(NumberGeneratorService::class);
        $data['code'] = $numberGeneratorService->next(\App\Enums\DocumentType::INV);
        $data['issued_by'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $inventoryService = app(InventoryService::class);
            $bottleService = app(BottleService::class);

            // First create invoice (without items)
            $invoice = static::getModel()::create($data);

            // Now validate stock on hand using already-saved items
            foreach ($invoice->items as $item) {
                $product = $item->product;
                $currentStock = $inventoryService->stockOnHand($product);

                if ($item->qty > $currentStock) {
                    throw ValidationException::withMessages([
                        'items' => ['Product ' . $product->name . ' has insufficient stock. Available: ' . $currentStock],
                    ]);
                }
            }

            // Post stock + bottle movements
            foreach ($invoice->items as $item) {
                $product = $item->product;

                // OUT movement
                $inventoryService->postOut(
                    product: $product,
                    qty: $item->qty,
                    value: $item->unit_price,
                    sourceType: StockMovementSourceType::INVOICE,
                    sourceId: $invoice->id,
                    occurredAt: $invoice->issued_at,
                    meta: ['invoice_item_id' => $item->id], // ✅ use item id
                );

                // Refillable bottle handling
                if ($product->is_refillable) {
                    $bottleService->deliver(
                        shop: $invoice->shop,
                        product: $product,
                        qty: $item->qty,
                        invoice: $invoice,
                        occurredAt: $invoice->issued_at,
                    );

                    if ($item->returned_empty > 0) {
                        $bottleService->returned(
                            shop: $invoice->shop,
                            product: $product,
                            qty: $item->returned_empty,
                            invoice: $invoice,
                            occurredAt: $invoice->issued_at,
                        );
                    }

                    if ($item->damaged_lost > 0) {
                        $bottleService->damaged(
                            shop: $invoice->shop,
                            product: $product,
                            qty: $item->damaged_lost,
                            invoice: $invoice,
                            occurredAt: $invoice->issued_at,
                        );
                    }
                }
            }

            // Update shop refillable balance
            $bottleService->recalculateShopBalance($invoice->shop_id);

            return $invoice;
        });
    }
}
