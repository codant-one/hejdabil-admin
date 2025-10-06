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

}
