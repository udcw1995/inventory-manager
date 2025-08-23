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

            // Validate stock on hand
            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                $currentStock = $inventoryService->stockOnHand($product);
                if ($item['qty'] > $currentStock) {
                    throw ValidationException::make(['items' => ['Product ' . $product->name . ' has insufficient stock. Available: ' . $currentStock]]);
                }
            }

            $invoice = static::getModel()::create($data);

            foreach ($data['items'] as $item) {
                $invoice->items()->create($item);

                $product = Product::find($item['product_id']);

                // Inventory OUT movement for sold quantities
                $inventoryService->postOut(
                    product: $product,
                    qty: $item['qty'],
                    value: $item['unit_price'],
                    sourceType: StockMovementSourceType::INVOICE,
                    sourceId: $invoice->id,
                    occurredAt: $invoice->issued_at,
                    meta: ['invoice_item_id' => $invoice->id],
                );

                // Bottle movements for refillable products
                if ($product->is_refillable) {
                    // DELIVERED = sum of refillable qty
                    $bottleService->deliver(
                        shop: $invoice->shop,
                        product: $product,
                        qty: $item['qty'],
                        invoice: $invoice,
                        occurredAt: $invoice->issued_at,
                    );

                    // RETURNED = sum of returned_empty
                    if ($item['returned_empty'] > 0) {
                        $bottleService->returned(
                            shop: $invoice->shop,
                            product: $product,
                            qty: $item['returned_empty'],
                            invoice: $invoice,
                            occurredAt: $invoice->issued_at,
                        );
                    }

                    // DAMAGED = sum of damaged_lost
                    if ($item['damaged_lost'] > 0) {
                        $bottleService->damaged(
                            shop: $invoice->shop,
                            product: $product,
                            qty: $item['damaged_lost'],
                            invoice: $invoice,
                            occurredAt: $invoice->issued_at,
                        );
                    }
                }
            }

            // Recalculate shops.refillable_balance
            $bottleService->recalculateShopBalance($invoice->shop_id);

            return $invoice;
        });
    }
}
