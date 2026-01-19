<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\SparService;
use Illuminate\Support\Facades\Log;
use Exception;

class PersonInfoController extends Controller
{
    protected SparService $sparService;

    public function __construct(SparService $sparService)
    {
        $this->sparService = $sparService;
    }

    /**
     * Lookup a person by their personal identity number (personnummer).
     *
     * @param string $personId
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookupByPersonId(string $personId)
    {
        try {
            // Clean the personId (remove dashes and spaces)
            $cleanedPersonId = preg_replace('/[\s\-]/', '', $personId);

            // Validate format (should be 10 or 12 digits)
            if (!preg_match('/^\d{10,12}$/', $cleanedPersonId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ogiltigt personnummer format',
                    'message' => 'Personnummer måste vara 10 eller 12 siffror'
                ], 422);
            }

            $result = $this->sparService->searchPerson($cleanedPersonId);

            $personData = $this->extractFirstPersonRecord($result);

            // Check if we got a valid response with person data
            if (!empty($personData) && is_array($personData)) {
                
                // Transform the data to a more frontend-friendly format
                $transformedData = $this->transformPersonData($personData);

                return response()->json([
                    'success' => true,
                    'data' => $transformedData
                ], 200);
            }

            if ((bool) config('services.spar.debug', false)) {
                $clean = preg_replace('/\D+/', '', $cleanedPersonId) ?? '';
                $masked = (strlen($clean) > 4)
                    ? (substr($clean, 0, 2) . str_repeat('*', max(0, strlen($clean) - 4)) . substr($clean, -2))
                    : '****';

                Log::info('SPAR lookup returned no person record', [
                    'personId_masked' => $masked,
                    'result_top_keys' => array_keys($result),
                    'result_excerpt' => mb_substr(json_encode($result, JSON_UNESCAPED_UNICODE), 0, 800),
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Ingen person hittades',
                'message' => 'Ingen person hittades med det angivna personnumret'
            ], 404);

        } catch (Exception $e) {
            if (config('app.debug')) {
                Log::warning('SPAR lookup failed', [
                    'personId' => $personId,
                    'message' => $e->getMessage(),
                ]);
            }

            $msg = $e->getMessage();
            $status = 500;
            if (str_contains($msg, 'SOAP Fault') || str_contains($msg, 'SPAR HTTP error')) {
                // Error del servicio upstream (SPAR) o de comunicación.
                $status = 502;
            }

            // Undantag (respuesta válida, pero SPAR rechaza por estado/permisos/configuración)
            if (str_contains($msg, 'SPAR Undantag')) {
                // Caso típico en prod: "Client.EjAktivSlutK" => "Slutkunden är inte aktiv i SPAR"
                $status = str_contains($msg, 'Client.EjAktivSlutK') ? 403 : 422;
            }

            return response()->json([
                'success' => false,
                'error' => 'SPAR API fel',
                'message' => $e->getMessage()
            ], $status);
        }
    }

    protected function extractFirstPersonRecord(array $result): ?array
    {
        // Variantes observadas/posibles según schemas/serialización:
        // - PersonsokningSvarspost (directo)
        // - PersonsokningSvarspostLista -> PersonsokningSvarspost
        // - PersonsokningSvarspostLista (cuando solo hay un item)

        $candidate = $result['PersonsokningSvarspost']
            ?? data_get($result, 'PersonsokningSvarspostLista.PersonsokningSvarspost')
            ?? ($result['PersonsokningSvarspostLista'] ?? null);

        if (empty($candidate)) {
            return null;
        }

        // Si vienen múltiples, tomar el primero.
        if (is_array($candidate) && array_is_list($candidate)) {
            $first = $candidate[0] ?? null;
            return is_array($first) ? $first : null;
        }

        return is_array($candidate) ? $candidate : null;
    }

    /**
     * Transform SPAR response data to a frontend-friendly format.
     *
     * @param array $personData
     * @return array
     */
    protected function transformPersonData(array $personData): array
    {
        $text = static function ($value): ?string {
            if (is_array($value)) {
                if (array_key_exists('_text', $value)) {
                    $raw = $value['_text'];
                    return is_string($raw) ? $raw : null;
                }
                return null;
            }

            return is_string($value) ? $value : (is_numeric($value) ? (string) $value : null);
        };

        $namn = $personData['Namn'] ?? [];
        
        // Handle Folkbokforingsadress which can come in two formats:
        // 1. Direct: ['SvenskAdress' => [...]]
        // 2. Array list: [0 => ['SvenskAdress' => [...]], 1 => [...]]
        $folkbokforing = $personData['Folkbokforingsadress'] ?? [];
        if (!empty($folkbokforing) && array_is_list($folkbokforing)) {
            // If it's a numeric indexed array, take the first element
            $adress = $folkbokforing[0]['SvenskAdress'] ?? [];
        } else {
            // Otherwise, access SvenskAdress directly
            $adress = $folkbokforing['SvenskAdress'] ?? [];
        }
        
        $personDetaljer = $personData['Persondetaljer'] ?? [];
        $personId = $personData['PersonId'] ?? [];

        // Build full name from Fornamn and Efternamn
        $fornamn = $text($namn['Fornamn'] ?? '') ?? '';
        $efternamn = $text($namn['Efternamn'] ?? '') ?? '';
        $fullname = trim("{$fornamn} {$efternamn}");

        // If no constructed name, try Aviseringsnamn (but reverse it since it's "Lastname, Firstname")
        $aviseringsnamn = $text($namn['Aviseringsnamn'] ?? null);
        if (empty($fullname) && !empty($aviseringsnamn)) {
            $parts = explode(',', $aviseringsnamn);
            if (count($parts) === 2) {
                $fullname = trim($parts[1]) . ' ' . trim($parts[0]);
            } else {
                $fullname = $aviseringsnamn;
            }
        }

        return [
            'personnummer' => $text($personId['IdNummer'] ?? null),
            'typ' => $text($personId['Typ'] ?? null),
            'fullname' => $fullname,
            'fornamn' => $fornamn,
            'efternamn' => $efternamn,
            'mellannamn' => $text($namn['Mellannamn'] ?? null),
            'adress' => $text($adress['Utdelningsadress2'] ?? null),
            'postnummer' => $text($adress['PostNr'] ?? null),
            'postort' => $text($adress['Postort'] ?? null),
            'fodelsedatum' => $text($personDetaljer['Fodelsedatum'] ?? null),
            'kon' => $text($personDetaljer['Kon'] ?? null),
            'sekretessmarkering' => $text($personData['Sekretessmarkering'] ?? null),
            'skyddadFolkbokforing' => $text($personData['SkyddadFolkbokforing'] ?? null),
        ];
    }
}
