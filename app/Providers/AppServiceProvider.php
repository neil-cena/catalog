<?php

namespace App\Providers;

use App\Domain\Catalog\CatalogService;
use App\Domain\Catalog\PriceResolver;
use App\Domain\Catalog\VisibilityResolver;
use App\Repositories\JsonCatalogRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JsonCatalogRepository::class, fn () => new JsonCatalogRepository(
            productsPath:  database_path('seed-data/products.json'),
            customersPath: database_path('seed-data/customers.json'),
        ));

        $this->app->singleton(CatalogService::class, fn ($app) => new CatalogService(
            $app->make(JsonCatalogRepository::class),
            new VisibilityResolver(),
            new PriceResolver(),
        ));
    }

    public function boot(): void {}
}
