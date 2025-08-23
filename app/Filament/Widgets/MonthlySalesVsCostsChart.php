<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class MonthlySalesVsCostsChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Sales vs Costs';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => [0, 0, 0, 0, 0, 0, 0],
                ],
                [
                    'label' => 'Costs',
                    'data' => [0, 0, 0, 0, 0, 0, 0],
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
        ];
    }
}
