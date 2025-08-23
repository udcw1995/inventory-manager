<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodaySalesOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $todaySales = Invoice::whereDate('issued_at', Carbon::today())->sum('final_total');

        return [
            Stat::make("Today's Sales", number_format($todaySales, 2)),
        ];
    }
}
