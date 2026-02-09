<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
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

class CacheService
{
    /**
     * Cache duration in seconds (1 hora)
     */
    const CACHE_DURATION = 3600;

    /**
     * Get all brands with cache
     */
    public static function getBrands()
    {
        return Cache::remember('brands.all', self::CACHE_DURATION, function () {
            return Brand::all();
        });
    }

    /**
     * Get all car models with brand relationship
     */
    public static function getCarModels()
    {
        return Cache::remember('car_models.all', self::CACHE_DURATION, function () {
            return CarModel::with(['brand'])->get();
        });
    }

    /**
     * Get all gearboxes
     */
    public static function getGearboxes()
    {
        return Cache::remember('gearboxes.all', self::CACHE_DURATION, function () {
            return Gearbox::all();
        });
    }

    /**
     * Get all car bodies
     */
    public static function getCarBodies()
    {
        return Cache::remember('carbodies.all', self::CACHE_DURATION, function () {
            return CarBody::all();
        });
    }

    /**
     * Get all IVAs
     */
    public static function getIvas()
    {
        return Cache::remember('ivas.all', self::CACHE_DURATION, function () {
            return Iva::all();
        });
    }

    /**
     * Get all fuels
     */
    public static function getFuels()
    {
        return Cache::remember('fuels.all', self::CACHE_DURATION, function () {
            return Fuel::all();
        });
    }

    /**
     * Get all states
     */
    public static function getStates()
    {
        return Cache::remember('states.all', self::CACHE_DURATION, function () {
            return State::all();
        });
    }

    /**
     * Get all guaranty types
     */
    public static function getGuarantyTypes()
    {
        return Cache::remember('guaranty_types.all', self::CACHE_DURATION, function () {
            return GuarantyType::all();
        });
    }

    /**
     * Get all insurance types
     */
    public static function getInsuranceTypes()
    {
        return Cache::remember('insurance_types.all', self::CACHE_DURATION, function () {
            return InsuranceType::all();
        });
    }

    /**
     * Get all currencies
     */
    public static function getCurrencies()
    {
        return Cache::remember('currencies.all', self::CACHE_DURATION, function () {
            return Currency::all();
        });
    }

    /**
     * Get all payment types
     */
    public static function getPaymentTypes()
    {
        return Cache::remember('payment_types.all', self::CACHE_DURATION, function () {
            return PaymentType::all();
        });
    }

    /**
     * Get all agreement types
     */
    public static function getAgreementTypes()
    {
        return Cache::remember('agreement_types.all', self::CACHE_DURATION, function () {
            return AgreementType::all();
        });
    }

    /**
     * Get all client types
     */
    public static function getClientTypes()
    {
        return Cache::remember('client_types.all', self::CACHE_DURATION, function () {
            return ClientType::all();
        });
    }

    /**
     * Get all identifications
     */
    public static function getIdentifications()
    {
        return Cache::remember('identifications.all', self::CACHE_DURATION, function () {
            return Identification::all();
        });
    }

    /**
     * Get all advances
     */
    public static function getAdvances()
    {
        return Cache::remember('advances.all', self::CACHE_DURATION, function () {
            return Advance::all();
        });
    }

    /**
     * Get all commission types
     */
    public static function getCommissionTypes()
    {
        return Cache::remember('commission_types.all', self::CACHE_DURATION, function () {
            return CommissionType::all();
        });
    }

    /**
     * Get all document types
     */
    public static function getDocumentTypes()
    {
        return Cache::remember('document_types.all', self::CACHE_DURATION, function () {
            return DocumentType::all();
        });
    }

    /**
     * Get active suppliers with relationships (cache por 5 minutos)
     */
    public static function getActiveSuppliers()
    {
        return Cache::remember('suppliers.active', 300, function () {
            return Supplier::with(['user.userDetail', 'billings'])
                ->whereNull('boss_id')
                ->get();
        });
    }

    /**
     * Clear all catalog cache
     */
    public static function clearCatalogCache()
    {
        $keys = [
            'brands.all',
            'car_models.all',
            'gearboxes.all',
            'carbodies.all',
            'ivas.all',
            'fuels.all',
            'states.all',
            'guaranty_types.all',
            'insurance_types.all',
            'currencies.all',
            'payment_types.all',
            'agreement_types.all',
            'client_types.all',
            'identifications.all',
            'advances.all',
            'commission_types.all',
            'document_types.all',
            'suppliers.active',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear specific cache key
     */
    public static function clearCache($key)
    {
        Cache::forget($key);
    }

    /**
     * Get all catalog data at once (useful for forms)
     */
    public static function getAllCatalogData()
    {
        return [
            'brands' => self::getBrands(),
            'models' => self::getCarModels(),
            'gearboxes' => self::getGearboxes(),
            'carbodies' => self::getCarBodies(),
            'ivas' => self::getIvas(),
            'fuels' => self::getFuels(),
            'states' => self::getStates(),
            'guarantyTypes' => self::getGuarantyTypes(),
            'insuranceTypes' => self::getInsuranceTypes(),
            'currencies' => self::getCurrencies(),
            'paymentTypes' => self::getPaymentTypes(),
            'agreementTypes' => self::getAgreementTypes(),
            'clientTypes' => self::getClientTypes(),
            'identifications' => self::getIdentifications(),
            'advances' => self::getAdvances(),
            'commissionTypes' => self::getCommissionTypes(),
        ];
    }
}
