<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class CompanyInfo
{
    protected $baseUrl;
    protected $tokenUrl;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->baseUrl = env('BOLAGSVERKET_API_URL');
        $this->tokenUrl = env('BOLAGSVERKET_TOKEN_URL');
        $this->clientId = env('BOLAGSVERKET_CLIENT_ID');
        $this->clientSecret = env('BOLAGSVERKET_CLIENT_SECRET');
    }

    /**
     * Obtiene la información de la empresa por número de organización.
     * @param string $orgNumber (Formato esperado: 556000-0000 o sin guion, lo limpiaremos)
     */
    public function getCompanyInfo(string $orgNumber)
    {
        // 1. Limpieza Estricta: La documentación dice "represented by 10 digits".
        // Quitamos guiones y espacios. Ej: "556016-0680" -> "5560160680"
        $cleanOrgNumber = preg_replace('/[^0-9]/', '', $orgNumber);

        try {
            $token = $this->getAccessToken();

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'No se pudo autenticar con Bolagsverket.',
                    'status' => 401
                ];
            }

            $url = "{$this->baseUrl}/organisationer";
            $requestId = (string) \Illuminate\Support\Str::uuid();

            // CORRECCIÓN BASADA EN TU CAPTURA DE "OrganisationerBegaran":
            // Clave exacta: 'identitetsbeteckning'
            // Valor: String de 10 dígitos
            $body = [
                'identitetsbeteckning' => $cleanOrgNumber
            ];

            Log::info('Bolagsverket Request:', ['url' => $url, 'body' => $body, 'id' => $requestId]);

            $response = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                    'X-Request-Id' => $requestId
                ])
                ->post($url, $body);

            if ($response->successful()) {
                $data = $response->json();
                
                // La respuesta suele venir dentro de un array "organisationer"
                if (!empty($data['organisationer']) && count($data['organisationer']) > 0) {
                    return [
                        'success' => true,
                        'data'    => $data['organisationer'][0],
                        'status'  => 200
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Empresa no encontrada.',
                        'status'  => 404
                    ];
                }
            }

            // Log de error
            Log::error('Bolagsverket Error Response:', $response->json());

            return [
                'success' => false,
                'data'    => $response->json(),
                'status'  => $response->status()
            ];

        } catch (\Exception $e) {
            Log::error("Error Bolagsverket API: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno de conexión.',
                'status'  => 500
            ];
        }
    }

    /**
     * Obtiene el Token de acceso usando Client Credentials Flow.
     * Almacena el token en Cache por 55 minutos (suelen durar 1 hora).
     */
    protected function getAccessToken()
    {
        return Cache::remember('bolagsverket_access_token', 3300, function () {
            try {
                $response = Http::asForm()->post($this->tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'vardefulla-datamangder:read'
                ]);

                if ($response->successful()) {
                    return $response->json()['access_token'];
                }

                Log::error('Bolagsverket Token Error: ' . $response->body());
                return null;
            } catch (\Exception $e) {
                Log::error('Bolagsverket Token Exception: ' . $e->getMessage());
                return null;
            }
        });
    }
}