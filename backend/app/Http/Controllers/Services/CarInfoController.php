<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;

use App\Services\CarInfo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CarInfoController extends Controller
{
    protected $carInfoService;

    public function __construct(CarInfo $carInfoService)
    {
        $this->carInfoService = $carInfoService;
    }

    /**
     * Buscar vehículo por matrícula
     */
    public function lookupByLicensePlate($licensePlate)
    {
        $result = $this->carInfoService->getByLicensePlate($licensePlate);

        return response()->json(
            $result,
            $result['status'] ?? 200
        );
    }

    /**
     * Buscar vehículo por VIN
     */
    public function lookupByVin($vin)
    {
        $result = $this->carInfoService->getByVin($vin);

        return response()->json(
            $result,
            $result['status'] ?? 200
        );
    }
}