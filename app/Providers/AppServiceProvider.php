<?php

namespace App\Providers;

use App\Interfaces\ProductMapperInterface;
use App\Mappers\ProductMapper;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductMapperInterface::class, function() {
            return new ProductMapper();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
