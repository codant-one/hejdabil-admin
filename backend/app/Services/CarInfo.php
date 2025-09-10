<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class CarInfo
{
    protected $url;

    public function __construct(string $licensePlate)
    {
        $apiUrlBase = env('CAR_INFO_API_URL');
        $this->url = "{$apiUrlBase}/license-plate/S/{$licensePlate}";
    }

    public function getLicensePlate()
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Language' => 'sv',
            ])->get($this->url);

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
}
