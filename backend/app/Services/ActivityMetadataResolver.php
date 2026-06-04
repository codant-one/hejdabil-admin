<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\SupplierActivity;
use Illuminate\Support\Collection;

class ActivityMetadataResolver
{
    public function enrichCollection(Collection $activities): Collection
    {
        return $activities->map(function (SupplierActivity $activity) {
            $metadata = $this->parseMetadata($activity->metadata);

            if ($metadata === null) {
                return $activity;
            }

            if (array_key_exists('old_values', $metadata)) {
                $metadata['old_values'] = $this->resolveValueMap($metadata['old_values']);
            }

            if (array_key_exists('new_values', $metadata)) {
                $metadata['new_values'] = $this->resolveValueMap($metadata['new_values']);
            }

            $activity->setAttribute('metadata', $metadata);

            return $activity;
        });
    }

    private function parseMetadata(mixed $metadata): ?array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (!is_string($metadata) || trim($metadata) === '') {
            return null;
        }

        $decodedMetadata = json_decode($metadata, true);

        return is_array($decodedMetadata) ? $decodedMetadata : null;
    }

    private function resolveValueMap(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        foreach ($values as $key => $value) {
            $values[$key] = $this->resolveFieldValue($key, $value);
        }

        return $values;
    }

    private function resolveFieldValue(string $key, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_string($value) && in_array(strtolower(trim($value)), ['null', 'undefined'], true)) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->resolveFieldValue($key, $item), $value);
        }

        if (!is_scalar($value)) {
            return $value;
        }

        return match ($key) {
            'agreement_type_id' => $this->resolveFromCatalog(CacheService::getAgreementTypes(), $value),
            'advance_id' => $this->resolveFromCatalog(CacheService::getAdvances(), $value, ['name', 'label', 'value']),
            'brand_id', 'brand_id_interchange' => $this->resolveFromCatalog(CacheService::getBrands(), $value),
            'car_body_id', 'car_body_id_interchange' => $this->resolveFromCatalog(CacheService::getCarBodies(), $value),
            'client_id' => $this->resolveFromCatalog(CacheService::getClients(), $value, ['fullname', 'name']),
            'client_type_id' => $this->resolveFromCatalog(CacheService::getClientTypes(), $value),
            'commission_type_id' => $this->resolveFromCatalog(CacheService::getCommissionTypes(), $value),
            'country_id' => $this->resolveFromCatalog(CacheService::getCountries(), $value),
            'currency_id', 'currency_purchase_id', 'currency_sale_id' => $this->resolveFromCatalog(CacheService::getCurrencies(), $value, ['name', 'code']),
            'fuel_id', 'fuel_id_interchange' => $this->resolveFromCatalog(CacheService::getFuels(), $value),
            'gearbox_id', 'gearbox_id_interchange' => $this->resolveFromCatalog(CacheService::getGearboxes(), $value),
            'guaranty_type_id' => $this->resolveFromCatalog(CacheService::getGuarantyTypes(), $value),
            'insurance_type_id' => $this->resolveFromCatalog(CacheService::getInsuranceTypes(), $value),
            'iva_id', 'iva_purchase_id', 'iva_sale_id', 'iva_purchase_id_interchange', 'iva_sale_id_interchange' => $this->resolveFromCatalog(CacheService::getIvas(), $value, ['name', 'value']),
            'model_id', 'model_id_interchange' => $this->resolveFromCatalog(CacheService::getCarModels(), $value, ['name', 'model', 'brand.name']),
            'offer_id' => $this->resolveOfferValue($value),
            'payment_type_id' => $this->resolveFromCatalog(CacheService::getPaymentTypes(), $value),
            'state_id' => $this->resolveFromCatalog(CacheService::getStates(), $value),
            default => $value,
        };
    }

    private function resolveFromCatalog(Collection $catalog, mixed $value, array $displayFields = ['name', 'label']): mixed
    {
        $resolvedItem = $catalog->first(function ($item) use ($value) {
            return (string) data_get($item, 'id') === (string) $value;
        });

        if (!$resolvedItem) {
            return $value;
        }

        foreach ($displayFields as $displayField) {
            $displayValue = data_get($resolvedItem, $displayField);

            if (is_scalar($displayValue) && trim((string) $displayValue) !== '') {
                return $displayValue;
            }
        }

        return $value;
    }

    private function resolveOfferValue(mixed $value): mixed
    {
        $offer = Offer::query()
            ->select(['id', 'reg_num'])
            ->find($value);

        return $offer?->reg_num ?: $value;
    }
}