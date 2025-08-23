<?php

namespace App\Filament\Widgets;

use App\Models\Shop;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopSellingShopsTable extends BaseWidget
{
    protected static ?string $heading = 'Top 10 Shops by Sales';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Shop::query()
            ->select('shops.id', 'shops.name', DB::raw('SUM(invoices.final_total) as total_sales'))
            ->join('invoices', 'shops.id', '=', 'invoices.shop_id')
            ->where('invoices.issued_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('shops.id', 'shops.name')
            ->orderByDesc('total_sales')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable(),
            TextColumn::make('total_sales')
                ->money('USD')
                ->sortable(),
        ];
    }
}
