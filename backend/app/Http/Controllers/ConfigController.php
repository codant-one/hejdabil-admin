<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use App\Models\Config;

class ConfigController extends Controller
{

    public function featured($slug): JsonResponse
    {
        $config = Config::getByKey($slug) ?? ['value' => '[]'];

        return response()->json([
            'config' => $config
        ]);
    }

    public function featured_update(Request $request, $slug): JsonResponse
    {
        $config = Config::getByKey($slug);

        if (!$config) {
            $config = new Config;
            $config->key = 'featured_' . $slug;
        }

        $config->value = json_encode($request->input('value'));
        $config->save();

        return response()->json([
            'config' => $config
        ]);
    }

    public function featured_logo_update(Request $request, $slug): JsonResponse
    {
        
        $config = Config::getByKey($slug);

        if (!$config) {
            $config = new Config;
            $config->key = 'featured_' . $slug;
        }

        if ($request->hasFile('logo')) {
            $image = $request->file('logo');
            $info = Config::getByKey($slug) ?? ['value' => '[]'];

            $path = 'logos/';

            $file_data = uploadFile($image, $path, $info->value['logo'] ?? null);

            $config->value = json_encode(['logo' => $file_data['filePath']]);
            $config->save();
        } else {
            $config->value = null;
            $config->save();
        }

        return response()->json([
            'config' => $config
        ]);
    }

    public function featured_signature_update(Request $request, $slug): JsonResponse
    {
        $config = Config::getByKey($slug);

        if (!$config) {
            $config = new Config;
            $config->key = 'featured_' . $slug;
        }

        // Buscamos un archivo con el nombre 'img_signature'
        if ($request->hasFile('img_signature')) {
            $image = $request->file('img_signature');
            $info = Config::getByKey($slug) ?? ['value' => '[]'];
            
            // Guardamos en una carpeta diferente
            $path = 'img_signatures/'; 

            // Obtenemos la ruta de la firma anterior para poder borrarla
            $previous_signature_path = $info->value['img_signature'] ?? null;

            $file_data = uploadFile($image, $path, $previous_signature_path);

            // Guardamos el JSON con la clave 'img_signature'
            $config->value = json_encode(['img_signature' => $file_data['filePath']]);
            $config->save();
        } else {
            // Opcional: si se envía sin archivo, se podría borrar la firma
            $config->value = json_encode(['img_signature' => null]);
            $config->save();
        }

        return response()->json([
            'config' => $config
        ]);
    }

}
