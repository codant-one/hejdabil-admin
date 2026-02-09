<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Observers\CatalogObserver;

// Models to observe
use App\Models\{
    Brand,
    CarModel,
    CarBody,
    Gearbox,
    Iva,
    Fuel,
    State,
    GuarantyType,
    InsuranceType,
    Currency,
    PaymentType,
    AgreementType,
    ClientType,
    Identification,
    Advance,
    CommissionType,
    DocumentType,
    Supplier
};

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
        Schema::defaultStringLength(191);

        // Register catalog observers for cache invalidation
        Brand::observe(CatalogObserver::class);
        CarModel::observe(CatalogObserver::class);
        CarBody::observe(CatalogObserver::class);
        Gearbox::observe(CatalogObserver::class);
        Iva::observe(CatalogObserver::class);
        Fuel::observe(CatalogObserver::class);
        State::observe(CatalogObserver::class);
        GuarantyType::observe(CatalogObserver::class);
        InsuranceType::observe(CatalogObserver::class);
        Currency::observe(CatalogObserver::class);
        PaymentType::observe(CatalogObserver::class);
        AgreementType::observe(CatalogObserver::class);
        ClientType::observe(CatalogObserver::class);
        Identification::observe(CatalogObserver::class);
        Advance::observe(CatalogObserver::class);
        CommissionType::observe(CatalogObserver::class);
        DocumentType::observe(CatalogObserver::class);
        Supplier::observe(CatalogObserver::class);
    }
}
