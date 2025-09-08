<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;

use App\Services\CarInfo;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CarInfoController extends Controller
{
    
    public function lookupByLicensePlate($licensePlate)
    {
        $carInfoApi = new CarInfo($licensePlate);
        $result = $carInfoApi->getLicensePlate();

        return response()->json(
            $result,
            $result['status'] ?? 200 // usa el status que venga del service, default 200
        );
    }
}