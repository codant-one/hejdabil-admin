<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProxyController extends Controller
{
    public function getImage(Request $request) {

        $client = new \GuzzleHttp\Client();
    
        $response = $client->get($request->url, [
            'verify' => false
        ]);
        
        $imageData = $response->getBody()->getContents();

        return response($imageData, 200, [
            'Content-Type' => $response->getHeaderLine('Content-Type'),
            'Content-Length' => strlen($imageData),
        ]);        
    }

}
