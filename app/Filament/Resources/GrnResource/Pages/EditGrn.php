<?php

namespace App\Filament\Resources\GrnResource\Pages;

use App\Enums\StockMovementSourceType;
use App\Filament\Resources\GrnResource;
use App\Models\Grn;
use App\Services\InventoryService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditGrn extends EditRecord
{
    protected static string $resource = GrnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function (Model $record) {
                    DB::transaction(function () use ($record) {
                        $inventoryService = app(InventoryService::class);

                        // Reverse all movements associated with this GRN
                        foreach ($record->items as $item) {
                            $movements = $record->stockMovements()->where('source_id', $record->id)->where('product_id', $item->product_id)->get();
                            foreach ($movements as $movement) {
                                $inventoryService->reverseMovement($movement->id);
                            }
                        }

                        $record->delete();
                    });
                }),
            Actions\Action::make('print')
                ->label('Print GRN')
                // ->url(fn (Grn $record) => route('print.grn', $record))
                ->openUrlInNewTab()
                ->icon('heroicon-o-printer'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $inventoryService = app(InventoryService::class);

            // Reverse all existing stock movements
            foreach ($record->items as $item) {
                $movements = $record->stockMovements()
                    ->where('source_id', $record->id)
                    ->where('product_id', $item->product_id)
                    ->get();

                foreach ($movements as $movement) {
                    $inventoryService->reverseMovement($movement->id);
                }
            }

            // Update the GRN itself (Filament will also update items via the relationship)
            $record->update($data);

            // Re-query fresh relations after update
            $record->refresh();

            // Post new movements
            foreach ($record->items as $item) {
                $inventoryService->postIn(
                    product: $item->product,
                    qty: $item->qty,
                    value: $item->unit_cost,
                    sourceType: StockMovementSourceType::GRN,
                    sourceId: $record->id,
                    occurredAt: $record->delivered_at,
                    meta: ['grn_item_id' => $item->id],
                );
            }

            return $record;
        });
    }
}
