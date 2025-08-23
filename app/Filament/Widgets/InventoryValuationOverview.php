<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Services\InventoryService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryValuationOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $inventoryService = app(InventoryService::class);
        $totalValuation = 0;

        foreach (Product::all() as $product) {
            $stockOnHand = $inventoryService->stockOnHand($product);
            $totalValuation += ($stockOnHand * $product->purchase_cost);
        }

        return [
            Stat::make('Inventory Valuation', number_format($totalValuation, 2)),
        ];
    }
}
