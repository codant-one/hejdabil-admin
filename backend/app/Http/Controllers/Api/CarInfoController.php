<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CarInfoController extends Controller
{
    /**
     * Busca la información de un vehículo por su placa en la API de Car.info.
     */
    public function lookupByLicensePlate($licensePlate)
    {
        $apiUrlBase = env('CAR_INFO_API_URL');

        if (!$apiUrlBase) {
            Log::error('CAR_INFO_API_URL no está configurada en el archivo .env');
            return response()->json(['error' => 'La integración con el servicio de vehículos no está configurada.'], 500);
        }

        $fullApiUrl = "{$apiUrlBase}/license-plate/S/{$licensePlate}";

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-Language' => 'sv', // Pide la respuesta en sueco
            ])->get($fullApiUrl);

            // Reenvía la respuesta de Car.info directamente.
            // Si la API de Car.info respondió con un error (ej. 404),
            // tu backend responderá con ese mismo error y cuerpo.
            return response($response->body(), $response->status())
                  ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            Log::error("Excepción al contactar API Car.info para placa {$licensePlate}: " . $e->getMessage());
            return response()->json(['error' => 'Error de conexión con el servicio de vehículos.'], 503);
        }
    }
}