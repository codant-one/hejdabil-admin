<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Services\CompanyInfo;
use Illuminate\Http\Request;

class CompanyInfoController extends Controller
{
    public function lookupByOrgNumber($orgNumber)
    {
        // Instanciamos el servicio
        $companyService = new CompanyInfo();
        
        // Llamamos al método
        $result = $companyService->getCompanyInfo($orgNumber);

        // Retornamos JSON
        if ($result['success']) {
            return response()->json($result['data'], 200);
        }

        return response()->json([
            'error' => $result['message'] ?? 'Error desconocido',
            'details' => $result['data'] ?? null
        ], $result['status'] ?? 500);
    }
}