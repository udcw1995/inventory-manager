<?php

namespace App\Filament\Widgets;

use App\Models\Shop;
use Filament\Widgets\ChartWidget;

class RefillableBottleBalancesChart extends ChartWidget
{
    protected static ?string $heading = 'Refillable Bottle Balances by Shop';

    protected static ?int $sort = 8;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $shops = Shop::all();

        return [
            'datasets' => [
                [
                    'label' => 'Balance',
                    'data' => $shops->pluck('refillable_balance'),
                ],
            ],
            'labels' => $shops->pluck('name'),
        ];
    }
}
