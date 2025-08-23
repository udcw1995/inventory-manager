<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Services\InventoryService;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockProductsTable extends BaseWidget
{
    protected static ?string $heading = 'Low Stock Products';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        $threshold = 20; // Configurable threshold
        $inventoryService = app(InventoryService::class);

        // Get all products and filter them based on stock on hand
        $products = Product::all()->filter(function (Product $product) use ($inventoryService, $threshold) {
            return $inventoryService->stockOnHand($product) < $threshold;
        });

        // Return a query builder for the filtered products
        return Product::whereIn('id', $products->pluck('id'));
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('sku'),
            TextColumn::make('name'),
            TextColumn::make('flavor'),
            IconColumn::make('is_refillable')
                ->boolean(),
            TextColumn::make('stock_on_hand')
                ->getStateUsing(fn (Product $record): int => app(InventoryService::class)->stockOnHand($record)),
        ];
    }
}
