<?php

namespace App\Observers;

use App\Services\CacheService;

class CatalogObserver
{
    /**
     * Handle the "created" event.
     */
    public function created($model): void
    {
        $this->clearRelevantCache($model);
    }

    /**
     * Handle the "updated" event.
     */
    public function updated($model): void
    {
        $this->clearRelevantCache($model);
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted($model): void
    {
        $this->clearRelevantCache($model);
    }

    /**
     * Clear relevant cache based on model type
     */
    protected function clearRelevantCache($model): void
    {
        $modelClass = get_class($model);
        
        $cacheMap = [
            'App\Models\Brand' => ['brands.all'],
            'App\Models\CarModel' => ['car_models.all'],
            'App\Models\Gearbox' => ['gearboxes.all'],
            'App\Models\CarBody' => ['carbodies.all'],
            'App\Models\Iva' => ['ivas.all'],
            'App\Models\Fuel' => ['fuels.all'],
            'App\Models\State' => ['states.all', 'states.vehicles'],
            'App\Models\GuarantyType' => ['guaranty_types.all'],
            'App\Models\InsuranceType' => ['insurance_types.all'],
            'App\Models\Currency' => ['currencies.all', 'currencies.active'],
            'App\Models\PaymentType' => ['payment_types.all'],
            'App\Models\AgreementType' => ['agreement_types.all'],
            'App\Models\ClientType' => ['client_types.all'],
            'App\Models\Identification' => ['identifications.all'],
            'App\Models\Advance' => ['advances.all'],
            'App\Models\CommissionType' => ['commission_types.all'],
            'App\Models\DocumentType' => ['document_types.all'],
            'App\Models\Supplier' => ['suppliers.active'],
            'App\Models\Invoice' => ['invoices.all'],
            'App\Models\Client' => ['clients.all'],
            'Spatie\Permission\Models\Permission' => ['permissions.all'],
            'Spatie\Permission\Models\Role' => ['roles.all'],
        ];

        if (isset($cacheMap[$modelClass])) {
            $keys = $cacheMap[$modelClass];
            foreach ($keys as $key) {
                CacheService::clearCache($key);
            }
        }
    }
}
