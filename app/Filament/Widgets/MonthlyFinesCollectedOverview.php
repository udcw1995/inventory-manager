<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MonthlyFinesCollectedOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyFines = Invoice::whereBetween('issued_at', [$startOfMonth, $endOfMonth])->sum('fines_total');

        return [
            Stat::make('Fines Collected This Month', number_format($monthlyFines, 2)),
        ];
    }
}
