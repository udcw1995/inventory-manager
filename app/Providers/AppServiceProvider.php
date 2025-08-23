<?php

namespace App\Providers;

use App\Models\Grn;
use App\Models\Invoice;
use App\Observers\GrnObserver;
use App\Observers\InvoiceObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Grn::observe(GrnObserver::class);
        Invoice::observe(InvoiceObserver::class);
    }
}
