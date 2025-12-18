<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\SparService;
use Illuminate\Http\Request;
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

            // Check if we got a valid response with person data
            if (isset($result['PersonsokningSvarspost'])) {
                $personData = $result['PersonsokningSvarspost'];
                
                // Transform the data to a more frontend-friendly format
                $transformedData = $this->transformPersonData($personData);

                return response()->json([
                    'success' => true,
                    'data' => $transformedData
                ], 200);
            }

            return response()->json([
                'success' => false,
                'error' => 'Ingen person hittades',
                'message' => 'Ingen person hittades med det angivna personnumret'
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'SPAR API fel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transform SPAR response data to a frontend-friendly format.
     *
     * @param array $personData
     * @return array
     */
    protected function transformPersonData(array $personData): array
    {
        $namn = $personData['Namn'] ?? [];
        $adress = $personData['Folkbokforingsadress']['SvenskAdress'] ?? [];
        $personDetaljer = $personData['Persondetaljer'] ?? [];
        $personId = $personData['PersonId'] ?? [];

        // Build full name from Fornamn and Efternamn
        $fornamn = $namn['Fornamn'] ?? '';
        $efternamn = $namn['Efternamn'] ?? '';
        $fullname = trim("$fornamn $efternamn");

        // If no constructed name, try Aviseringsnamn (but reverse it since it's "Lastname, Firstname")
        if (empty($fullname) && isset($namn['Aviseringsnamn'])) {
            $parts = explode(',', $namn['Aviseringsnamn']);
            if (count($parts) === 2) {
                $fullname = trim($parts[1]) . ' ' . trim($parts[0]);
            } else {
                $fullname = $namn['Aviseringsnamn'];
            }
        }

        return [
            'personnummer' => $personId['IdNummer'] ?? null,
            'typ' => $personId['Typ'] ?? null,
            'fullname' => $fullname,
            'fornamn' => $fornamn,
            'efternamn' => $efternamn,
            'mellannamn' => $namn['Mellannamn'] ?? null,
            'adress' => $adress['Utdelningsadress2'] ?? null,
            'postnummer' => $adress['PostNr'] ?? null,
            'postort' => $adress['Postort'] ?? null,
            'fodelsedatum' => $personDetaljer['Fodelsedatum'] ?? null,
            'kon' => $personDetaljer['Kon'] ?? null,
            'sekretessmarkering' => $personData['Sekretessmarkering'] ?? null,
            'skyddadFolkbokforing' => $personData['SkyddadFolkbokforing'] ?? null,
        ];
    }
}
