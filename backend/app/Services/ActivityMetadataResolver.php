<?php

namespace App\Services;

use Illuminate\Support\Collection;

use App\Models\Offer;
use App\Models\SupplierActivity;
use App\Models\Commission;

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
            'client_type_id', 'purchase_client_type_id', 'sale_client_type_id' => $this->resolveFromCatalog(CacheService::getClientTypes(), $value),
            'commission_type_id' => $this->resolveFromCatalog(CacheService::getCommissionTypes(), $value),
            'country_id', 'purchase_country_id', 'sale_country_id' => $this->resolveFromCatalog(CacheService::getCountries(), $value),
            'currency_id', 'currency_purchase_id', 'currency_sale_id', 'currency_purchase_id_interchange', 'currency_sale_id_interchange' => $this->resolveFromCatalog(CacheService::getCurrencies(), $value, ['name', 'code']),
            'fuel_id', 'fuel_id_interchange' => $this->resolveFromCatalog(CacheService::getFuels(), $value),
            'gearbox_id', 'gearbox_id_interchange' => $this->resolveFromCatalog(CacheService::getGearboxes(), $value),
            'guaranty_type_id' => $this->resolveFromCatalog(CacheService::getGuarantyTypes(), $value),
            'insurance_type_id' => $this->resolveFromCatalog(CacheService::getInsuranceTypes(), $value),
            'iva_id', 'iva_purchase_id', 'iva_sale_id', 'iva_purchase_id_interchange', 'iva_sale_id_interchange' => $this->resolveFromCatalog(CacheService::getIvas(), $value, ['name', 'value']),
            'model_id', 'model_id_interchange' => $this->resolveFromCatalog(CacheService::getCarModels(), $value, ['name', 'model', 'brand.name']),
            'offer_id' => $this->resolveOfferValue($value),
            'commission_id' => $this->resolveCommissionValue($value),
            'payment_type_id' => $this->resolveFromCatalog(CacheService::getPaymentTypes(), $value),
            'payout_state_id' => $this->resolveFromCatalog(CacheService::getPayoutStates(), $value),
            'user_id' => $this->resolveUserValue($value),
            'state_id', 'state_id_interchange' => $this->resolveFromCatalog(CacheService::getStates(), $value),
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

    private function resolveUserValue(mixed $value): mixed
    {
        $resolvedUser = CacheService::getUsers()->first(function ($user) use ($value) {
            return (string) data_get($user, 'id') === (string) $value;
        });

        if (!$resolvedUser) {
            return $value;
        }

        $fullName = trim(implode(' ', array_filter([
            data_get($resolvedUser, 'name'),
            data_get($resolvedUser, 'last_name'),
        ], fn ($namePart) => is_scalar($namePart) && trim((string) $namePart) !== '')));

        if ($fullName !== '') {
            return $fullName;
        }

        $email = data_get($resolvedUser, 'email');

        return is_scalar($email) && trim((string) $email) !== '' ? $email : $value;
    }

    private function resolveOfferValue(mixed $value): mixed
    {
        $offer = Offer::query()
            ->with(['model.brand', 'fuel', 'gearbox', 'carbody'])
            ->find($value);

        if (!$offer) {
            return $value;
        }

        return [
            'id' => $offer->id,
            'offer_id' => $offer->offer_id,
            'reg_num' => $offer->reg_num,
            'car_name' => $offer->car_name,
            'brand_name' => $offer->model?->brand?->name,
            'model_name' => $offer->model?->name,
            'year' => $offer->year,
            'color' => $offer->color,
            'mileage' => $offer->mileage,
            'generation' => $offer->generation,
            'car_body' => $offer->carbody?->name,
            'purchase_date' => $offer->date,
            'chassis' => $offer->chassis,
            'control_inspection' => $offer->control_inspection,
            'fuel_name' => $offer->fuel?->name,
            'gearbox_name' => $offer->gearbox?->name,
            'engine' => $offer->engine,
            'number_keys' => $offer->number_keys,
            'service_book' => $offer->service_book,
            'summer_tire' => $offer->summer_tire,
            'winter_tire' => $offer->winter_tire,
            'dist_belt' => $offer->dist_belt,
            'last_service' => $offer->last_service,
            'last_service_date' => $offer->last_service_date,
            'last_dist_belt' => $offer->last_dist_belt,
            'last_dist_belt_date' => $offer->last_dist_belt_date,
            'comment' => $offer->comment,
        ];
    }

    private function resolveCommissionValue(mixed $value): mixed
    {
        $commission = Commission::query()->find($value);

        if (!$commission) {
            return $value;
        }

        return [
            'id' => $commission->id,
            'commission_type_id' => $commission->commission_type_id,
            'commission_id' => $commission->commission_id,
            'commission_fee' => $commission->commission_fee,
            'start_date' => $commission->start_date,
            'end_date' => $commission->end_date,
            'outstanding_debt' => $commission->outstanding_debt,
            'remaining_debt' => $commission->remaining_debt,
            'residual_debt' => $commission->residual_debt,
            'paid_bank' => $commission->paid_bank,
            'selling_price' => $commission->selling_price,
            'payment_days' => $commission->payment_days,
            'payment_description' => $commission->payment_description
        ];
    }
}