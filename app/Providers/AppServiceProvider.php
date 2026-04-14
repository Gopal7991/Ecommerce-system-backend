<?php

namespace App\Providers;

use App\Models\{Order,ProductVariant };
use App\Observers\{OrderObserver, ProductVariantObserver};
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Order::observe(OrderObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
    }
}
