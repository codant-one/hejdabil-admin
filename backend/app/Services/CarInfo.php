<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

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

            return $response->json();

        } catch (RequestException $e) {
            return [
                'data' => $e->response ? $e->response->json() : null,
                'success' => false,
            ];
        } catch (\Exception $e) {
            return [
                'data' => 'Fel vid anslutning till fordonsservicen.',
                'success' => false,
            ];
        }
    }

    /**
     * @deprecated Usa getByLicensePlate() en su lugar
     */
    public function getLicensePlate()
    {
        // Método mantenido por compatibilidad, pero ya no se usa
        return [
            'data' => 'Este método está deprecado. Usa getByLicensePlate().',
            'success' => false,
        ];
    }
}
