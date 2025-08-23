<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DailySalesChart extends ChartWidget
{
    protected static ?string $heading = 'Daily Sales (Last 30 Days)';

    protected static ?int $sort = 7;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $data = Invoice::select(DB::raw('DATE(issued_at) as date'), DB::raw('SUM(final_total) as total_sales'))
            ->where('issued_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = collect(range(0, 29))->map(fn ($i) => Carbon::now()->subDays(29 - $i)->format('Y-m-d'))->toArray();
        $salesByDate = $data->pluck('total_sales', 'date')->toArray();

        $datasets = [
            [
                'label' => 'Sales',
                'data' => array_map(fn ($date) => $salesByDate[$date] ?? 0, $dates),
            ],
        ];

        return [
            'datasets' => $datasets,
            'labels' => array_map(fn ($date) => Carbon::parse($date)->format('M d'), $dates),
        ];
    }
}
