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
     * Buscar vehículo por matrícula (license plate)
     */
    public function getByLicensePlate(string $licensePlate)
    {
        $url = "{$this->baseUrl}/license-plate/S/{$licensePlate}/";
        return $this->makeRequest($url);
    }

    /**
     * Buscar vehículo por VIN
     */
    public function getByVin(string $vin)
    {
        $url = "{$this->baseUrl}/vin/S/{$vin}/";
        return $this->makeRequest($url);
    }

    /**
     * Realizar la petición HTTP a la API
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

            // Si la respuesta es exitosa, procesamos y mapeamos los datos
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
     * Procesar y mapear la respuesta de la API a los campos de nuestra aplicación
     * 
     * Campos de la API:
     * - brand: Marca del vehículo
     * - model: Modelo del vehículo  
     * - generation: Generación
     * - chassis: Tipo de carrocería (SUV, Sedan, etc.) -> mapea a car_body_id
     * - engine_type: Tipo de combustible (Bensin, Diesel, etc.) -> mapea a fuel_id
     * - model_year: Año del modelo
     * - vin: Número de chasis/VIN
     */
    protected function processResponse(array $data): array
    {
        $result = $data['result'] ?? [];
        
        // Mapear fuel_id basado en engine_type
        $fuelId = $this->mapFuelType($result['engine_type'] ?? null);
        
        // Mapear car_body_id basado en chassis (que en la API es el body type)
        $carBodyId = $this->mapCarBodyType($result['chassis'] ?? null);
        
        // Mapear brand_id y model_id
        $brandData = $this->mapBrandAndModel(
            $result['brand'] ?? null,
            $result['model'] ?? null
        );

        // Mapear gearbox_id basado en attributes
        $gearboxId = $this->mapGearboxType($result['attributes'] ?? []);

        $latestInspection = $this->mapLatestInspection($result['latest_inspection'] ?? null);
        
        // Agregar los IDs mapeados al resultado
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
        // El VIN es el número de chasis real
        $data['result']['chassis_number'] = $result['vin'] ?? null;
        $data['result']['color'] = $result['basic_color'] ?? null;

        return $data;
    }

    /**
     * Mapear el tipo de combustible de la API al fuel_id de nuestra DB
     */
    protected function mapFuelType(?string $engineType): ?int
    {
        if (!$engineType) {
            return null;
        }

        $engineTypeLower = strtolower($engineType);

        // Mapeo de engine_type a labels de fuel en nuestra DB
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

        // Buscar coincidencia en el mapeo
        foreach ($fuelMapping as $key => $label) {
            if (str_contains($engineTypeLower, $key)) {
                $fuel = Fuel::firstOrCreate(
                    ['label' => $label],
                    ['label' => $label, 'name' => ucfirst($label)]
                );
                return $fuel->id;
            }
        }

        // Intentar búsqueda directa por nombre
        $fuel = Fuel::whereRaw('LOWER(name) LIKE ?', ['%' . $engineTypeLower . '%'])->first();
        
        // Si no se encuentra, crear un nuevo registro con el nombre original
        if (!$fuel) {
            $fuel = Fuel::create([
                'label' => strtolower(str_replace(' ', '_', $engineType)),
                'name' => ucfirst($engineType)
            ]);
        }
        
        return $fuel->id;
    }

    /**
     * Mapear el tipo de carrocería de la API al car_body_id de nuestra DB
     * En la API, el campo "chassis" contiene el tipo de carrocería (SUV, Sedan, etc.)
     */
    protected function mapCarBodyType(?string $bodyType): ?int
    {
        if (!$bodyType) {
            return null;
        }

        $bodyTypeLower = strtolower($bodyType);

        // Mapeo de body_type a nombres de car_body en nuestra DB
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

        // Buscar coincidencia en el mapeo
        foreach ($bodyMapping as $key => $name) {
            if (str_contains($bodyTypeLower, $key)) {
                $carBody = CarBody::firstOrCreate(
                    ['name' => $name],
                    ['name' => $name]
                );
                return $carBody->id;
            }
        }

        // Intentar búsqueda directa por nombre
        $carBody = CarBody::whereRaw('LOWER(name) LIKE ?', ['%' . $bodyTypeLower . '%'])->first();
        
        // Si no se encuentra, crear un nuevo registro con el nombre original
        if (!$carBody) {
            $carBody = CarBody::create([
                'name' => ucfirst($bodyType)
            ]);
        }
        
        return $carBody->id;
    }

    /**
     * Mapear marca y modelo de la API a brand_id y model_id de nuestra DB
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

        // Buscar la marca
        $brand = Brand::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($brandName) . '%'])->first();
        
        if ($brand) {
            $result['brand_id'] = $brand->id;

            // Si encontramos la marca, buscar el modelo
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
     * Mapear la caja de cambios desde los attributes de la API
     */
    protected function mapGearboxType(array $attributes): ?int
    {
        // Buscar el atributo de växellåda (caja de cambios)
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
     * Mapear la última inspección desde la API
     * Retorna un array con los campos: type, date, km (dividido entre 10), next_inspection
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
