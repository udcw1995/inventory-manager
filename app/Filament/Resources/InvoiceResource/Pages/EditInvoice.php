<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Enums\BottleMovementType;
use App\Enums\StockMovementSourceType;
use App\Filament\Resources\InvoiceResource;
use App\Models\Product;
use App\Services\BottleService;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    DB::transaction(function () use ($record) {
                        $inventoryService = app(InventoryService::class);
                        $bottleService = app(BottleService::class);

                        // Reverse all stock movements associated with this Invoice
                        foreach ($record->items as $item) {
                            $movements = $record->stockMovements()->where('source_id', $record->id)->where('product_id', $item->product_id)->get();
                            foreach ($movements as $movement) {
                                $inventoryService->reverseMovement($movement->id);
                            }
                        }

                        // Reverse all bottle movements associated with this Invoice
                        foreach ($record->bottleMovements as $movement) {
                            // For bottle movements, we don't have a direct reverse method like stock movements.
                            // We need to create an opposite movement to balance it out.
                            // This is a simplified approach. A more robust solution might involve a dedicated reverse method in BottleService.
                            if ($movement->type === BottleMovementType::DELIVERED) {
                                $bottleService->returned($record->shop, $movement->product, $movement->quantity, $record, $movement->occurred_at);
                            } elseif ($movement->type === BottleMovementType::RETURNED) {
                                $bottleService->deliver($record->shop, $movement->product, $movement->quantity, $record, $movement->occurred_at);
                            } elseif ($movement->type === BottleMovementType::DAMAGED) {
                                // Damaged items are removed from balance, so reversing means adding them back
                                $bottleService->deliver($record->shop, $movement->product, $movement->quantity, $record, $movement->occurred_at);
                            }
                        }

                        $record->delete();
                        $bottleService->recalculateShopBalance($record->shop_id);
                    });
                }),
            Actions\Action::make('print')
                ->label('Print Invoice')
                ->url(fn (Invoice $record) => route('print.invoice', $record))
                ->openUrlInNewTab()
                ->icon('heroicon-o-printer'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $inventoryService = app(InventoryService::class);
            $bottleService = app(BottleService::class);

            // Reverse previous stock movements
            foreach ($record->items as $item) {
                $movements = $record->stockMovements()->where('source_id', $record->id)->where('product_id', $item->product_id)->get();
                foreach ($movements as $movement) {
                    $inventoryService->reverseMovement($movement->id);
                }
            }

            // Reverse previous bottle movements
            foreach ($record->bottleMovements as $movement) {
                if ($movement->type === BottleMovementType::DELIVERED) {
                    $bottleService->returned($record->shop, $movement->product, $movement->quantity, $record, $movement->occurred_at);
                } elseif ($movement->type === BottleMovementType::RETURNED) {
                    $bottleService->deliver($record->shop, $movement->product, $movement->quantity, $record, $movement->occurred_at);
                } elseif ($movement->type === BottleMovementType::DAMAGED) {
                    $bottleService->deliver($record->shop, $movement->product, $movement->quantity, $record, $movement->occurred_at);
                }
            }

            // Delete old items and create new ones
            $record->items()->delete();
            foreach ($data['items'] as $item) {
                $record->items()->create($item);
            }

            // Update Invoice details
            $record->update($data);

            // Validate stock on hand for new quantities
            foreach ($data['items'] as $item) {
                $product = Product::find($item['product_id']);
                $currentStock = $inventoryService->stockOnHand($product);
                if ($item['qty'] > $currentStock) {
                    throw ValidationException::make(['items' => ['Product ' . $product->name . ' has insufficient stock. Available: ' . $currentStock]]);
                }
            }

            // Post new stock movements
            foreach ($record->items as $item) {
                $product = Product::find($item->product_id);

                $inventoryService->postOut(
                    product: $product,
                    qty: $item->qty,
                    value: $item->unit_price,
                    sourceType: StockMovementSourceType::INVOICE,
                    sourceId: $record->id,
                    occurredAt: $record->issued_at,
                    meta: ['invoice_item_id' => $item->id],
                );

                // Bottle movements for refillable products
                if ($product->is_refillable) {
                    // DELIVERED = sum of refillable qty
                    $bottleService->deliver(
                        shop: $record->shop,
                        product: $product,
                        qty: $item->qty,
                        invoice: $record,
                        occurredAt: $record->issued_at,
                    );

                    // RETURNED = sum of returned_empty
                    if ($item->returned_empty > 0) {
                        $bottleService->returned(
                            shop: $record->shop,
                            product: $product,
                            qty: $item->returned_empty,
                            invoice: $record,
                            occurredAt: $record->issued_at,
                        );
                    }

                    // DAMAGED = sum of damaged_lost
                    if ($item->damaged_lost > 0) {
                        $bottleService->damaged(
                            shop: $record->shop,
                            product: $product,
                            qty: $item->damaged_lost,
                            invoice: $record,
                            occurredAt: $record->issued_at,
                        );
                    }
                }
            }

            // Recalculate shops.refillable_balance
            $bottleService->recalculateShopBalance($record->shop_id);

            return $record;
        });
    }
}
