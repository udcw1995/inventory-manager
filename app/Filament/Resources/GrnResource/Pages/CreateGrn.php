<?php

namespace App\Filament\Resources\GrnResource\Pages;

use App\Enums\StockMovementSourceType;
use App\Filament\Resources\GrnResource;
use App\Services\InventoryService;
use App\Services\NumberGeneratorService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateGrn extends CreateRecord
{
    protected static string $resource = GrnResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $numberGeneratorService = app(NumberGeneratorService::class);
        $data['code'] = $numberGeneratorService->next(\App\Enums\DocumentType::GRN);
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $grn = static::getModel()::create($data);

            $inventoryService = app(InventoryService::class);

            foreach ($grn->items as $item) {
                $inventoryService->postIn(
                    product: $item->product,
                    qty: $item->qty,
                    value: $item->unit_cost,
                    sourceType: StockMovementSourceType::GRN,
                    sourceId: $grn->id,
                    occurredAt: $grn->delivered_at,
                    meta: ['grn_item_id' => $item->id], // ⚡ fix here: should be item id not grn id
                );
            }

            return $grn;
        });
    }
}
