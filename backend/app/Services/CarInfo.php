<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;
use App\Models\Fuel;
use App\Models\CarBody;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Gearbox;

class CarInfo
{
    protected $baseUrl;
    protected $authIdentifier;
    protected $authKey;

    public function __construct()
    {
        $this->baseUrl = env('CAR_INFO_API_URL');
        $this->authIdentifier = env('CAR_INFO_AUTH_IDENTIFIER');
        $this->authKey = env('CAR_INFO_AUTH_KEY');
    }

    /**
     * Search for a vehicle by license plate
     */
    public function getByLicensePlate(string $licensePlate)
    {
        $url = "{$this->baseUrl}/license-plate/S/{$licensePlate}/";
        return $this->makeRequest($url);
    }

    /**
     * Search for a vehicle by VIN
     */
    public function getByVin(string $vin)
    {
        $url = "{$this->baseUrl}/vin/S/{$vin}/";
        return $this->makeRequest($url);
    }

    /**
     * Send the HTTP request to the API
     */
    protected function makeRequest(string $url)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Language' => 'sv',
                'x-auth-identifier' => $this->authIdentifier,
                'x-auth-key' => $this->authKey,
            ])->get($url);

            $data = $response->json();

            // If the response is successful, process and map the data
            if ($response->successful() && isset($data['success']) && $data['success'] && isset($data['result'])) {
                return $this->processResponse($data);
            }

            return $data;

        } catch (RequestException $e) {
            Log::error('CarInfo API RequestException', ['error' => $e->getMessage()]);
            return [
                'data' => $e->response ? $e->response->json() : null,
                'success' => false,
            ];
        } catch (\Exception $e) {
            Log::error('CarInfo API Exception', ['error' => $e->getMessage()]);
            return [
                'data' => 'Fel vid anslutning till fordonsservicen.',
                'success' => false,
            ];
        }
    }

    /**
     * Process and map the API response to the fields in our application
     * 
     * API fields:
     * - brand: Vehicle brand
     * - model: Vehicle model  
     * - generation: Generation
     * - chassis: Body type (SUV, Sedan, etc.) -> maps to car_body_id
     * - engine_type: Fuel type (Gasoline, Diesel, etc.) -> maps to fuel_id
     * - model_year: Model year
     * - vin: Chassis number/VIN
     */
    protected function processResponse(array $data): array
    {
        $result = $data['result'] ?? [];
        
        // Map fuel_id based on engine_type
        $fuelId = $this->mapFuelType($result['engine_type'] ?? null);
        
        // Map car_body_id based on the chassis (which is the body type in the API)
        $carBodyId = $this->mapCarBodyType($result['chassis'] ?? null);
        
        // Map brand_id and model_id
        $brandData = $this->mapBrandAndModel(
            $result['brand'] ?? null,
            $result['model'] ?? null
        );

        // Map gearbox_id based on attributes
        $gearboxId = $this->mapGearboxType($result['attributes'] ?? []);

        $latestInspection = $this->mapLatestInspection($result['latest_inspection'] ?? null);
        
        // Add the mapped IDs to the result
        $data['result']['fuel_id'] = $fuelId;
        $data['result']['mileage'] = $latestInspection['inspection_km'];
        $data['result']['control_inspection'] = $latestInspection['next_inspection'];
        $data['result']['car_body_id'] = $carBodyId;
        $data['result']['brand_id'] = $brandData['brand_id'];
        $data['result']['model_id'] = $brandData['model_id'];
        $data['result']['model_name'] = $result['model'] ?? null;
        $data['result']['brand_name'] = $result['brand'] ?? null;
        $data['result']['gearbox_id'] = $gearboxId;
        $data['result']['engine'] = $result['engine'] ?? null;
        $data['result']['car_name'] = $result['car_name'] ?? null;
        // The VIN is the actual chassis number
        $data['result']['chassis_number'] = $result['vin'] ?? null;
        $data['result']['color'] = $result['basic_color'] ?? null;

        return $data;
    }

    /**
     * Map the API fuel type to our DB fuel_id
     */
    protected function mapFuelType(?string $engineType): ?int
    {
        if (!$engineType) {
            return null;
        }

        $engineTypeLower = strtolower($engineType);

        // Map engine_type to fuel labels in our DB
        $fuelMapping = [
            'diesel' => 'diesel',
            'bensin' => 'gasoline',
            'petrol' => 'gasoline',
            'gasoline' => 'gasoline',
            'el' => 'electric',
            'electric' => 'electric',
            'hybrid' => 'hybrid_gasoline',
            'laddhybrid' => 'hybrid_gasoline',
            'plug-in hybrid' => 'hybrid_gasoline',
            'elhybrid' => 'hybrid_gasoline',
        ];

        // Search for a match in the mapping
        foreach ($fuelMapping as $key => $label) {
            if (str_contains($engineTypeLower, $key)) {
                $fuel = Fuel::firstOrCreate(
                    ['label' => $label],
                    ['label' => $label, 'name' => ucfirst($label)]
                );
                return $fuel->id;
            }
        }

        // Attempt direct search by name
        $fuel = Fuel::whereRaw('LOWER(name) LIKE ?', ['%' . $engineTypeLower . '%'])->first();
        
        // If not found, create a new record with the original name
        if (!$fuel) {
            $fuel = Fuel::create([
                'label' => strtolower(str_replace(' ', '_', $engineType)),
                'name' => ucfirst($engineType)
            ]);
        }
        
        return $fuel->id;
    }

    /**
     * Map the API body type to our DB car_body_id
     * In the API, the "chassis" field contains the body type (SUV, Sedan, etc.)
     */
    protected function mapCarBodyType(?string $bodyType): ?int
    {
        if (!$bodyType) {
            return null;
        }

        $bodyTypeLower = strtolower($bodyType);

        // Map body_type to car_body names in our DB
        $bodyMapping = [
            'sedan' => 'Sedan',
            'kombi' => 'Kombi',
            'halvkombi' => 'Halvkombi',
            'hatchback' => 'Halvkombi',
            'coupé' => 'Sportkupé',
            'coupe' => 'Sportkupé',
            'sportkupé' => 'Sportkupé',
            'suv' => 'SUV',
            'cab' => 'Cab',
            'cabriolet' => 'Cab',
            'convertible' => 'Cab',
            'minibuss' => 'Minibuss',
            'minivan' => 'Minibuss',
            'mpv' => 'Minibuss',
        ];

        // Search for a match in the mapping
        foreach ($bodyMapping as $key => $name) {
            if (str_contains($bodyTypeLower, $key)) {
                $carBody = CarBody::firstOrCreate(
                    ['name' => $name],
                    ['name' => $name]
                );
                return $carBody->id;
            }
        }

        // Attempt direct search by name
        $carBody = CarBody::whereRaw('LOWER(name) LIKE ?', ['%' . $bodyTypeLower . '%'])->first();
        
        // If not found, create a new record with the original name
        if (!$carBody) {
            $carBody = CarBody::create([
                'name' => ucfirst($bodyType)
            ]);
        }
        
        return $carBody->id;
    }

    /**
     * Map the API brand and model to our DB brand_id and model_id
     */
    protected function mapBrandAndModel(?string $brandName, ?string $modelName): array
    {
        $result = [
            'brand_id' => null,
            'model_id' => null,
        ];

        if (!$brandName) {
            return $result;
        }

        // Search for the brand
        $brand = Brand::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($brandName) . '%'])->first();
        
        if ($brand) {
            $result['brand_id'] = $brand->id;

            // If we find the brand, search for the model
            if ($modelName) {
                $model = CarModel::where('brand_id', $brand->id)
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($modelName) . '%'])
                    ->first();
                
                $result['model_id'] = $model?->id;
            }
        }

        return $result;
    }

    /**
     * Map the gearbox type from the API attributes
     */
    protected function mapGearboxType(array $attributes): ?int
    {
        // Search for the växellåda (gearbox) attribute
        foreach ($attributes as $attr) {
            if (isset($attr['id']) && $attr['id'] === 'g7' && isset($attr['attributes'])) {
                foreach ($attr['attributes'] as $gearAttr) {
                    $gearboxName = $gearAttr['name'] ?? null;
                    if ($gearboxName) {
                        $gearbox = Gearbox::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($gearboxName) . '%'])->first();
                        return $gearbox?->id;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Map the latest inspection from the API
     * Returns an array with the fields: type, date, km (divided by 10), next_inspection
     */
    protected function mapLatestInspection(?array $latestInspection): array
    {
        if (!$latestInspection || empty($latestInspection)) {
            return [
                'inspection_km' => null,
                'next_inspection' => null,
            ];
        }

        return [
            'inspection_km' => isset($latestInspection['km']) ? (int) ($latestInspection['km'] / 10) : null,
            'next_inspection' => $latestInspection['next_inspection'] ?? null,
        ];
    }
}
