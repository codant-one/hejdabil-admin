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
            'App\Models\Brand' => 'brands.all',
            'App\Models\CarModel' => 'car_models.all',
            'App\Models\Gearbox' => 'gearboxes.all',
            'App\Models\CarBody' => 'carbodies.all',
            'App\Models\Iva' => 'ivas.all',
            'App\Models\Fuel' => 'fuels.all',
            'App\Models\State' => 'states.all',
            'App\Models\GuarantyType' => 'guaranty_types.all',
            'App\Models\InsuranceType' => 'insurance_types.all',
            'App\Models\Currency' => 'currencies.all',
            'App\Models\PaymentType' => 'payment_types.all',
            'App\Models\AgreementType' => 'agreement_types.all',
            'App\Models\ClientType' => 'client_types.all',
            'App\Models\Identification' => 'identifications.all',
            'App\Models\Advance' => 'advances.all',
            'App\Models\CommissionType' => 'commission_types.all',
            'App\Models\DocumentType' => 'document_types.all',
            'App\Models\Supplier' => 'suppliers.active',
        ];

        if (isset($cacheMap[$modelClass])) {
            CacheService::clearCache($cacheMap[$modelClass]);
        }
    }
}
