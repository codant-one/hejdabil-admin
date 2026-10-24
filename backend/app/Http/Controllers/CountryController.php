<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountryRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Country;
use App\Services\CacheService;
use App\Models\SupplierActivity;

class CountryController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view countries|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create countries|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit countries|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete countries|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Country::with(['state:id,name'])
                            ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'state_id'
                                ])
                            );

            if ($limit == -1) {
                $allCountries = $query->get();
                $countries = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allCountries,
                    $allCountries->count(),
                    max($allCountries->count(), 1),
                    1
                );
            } else {
                $countries = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'countries' => $countries,
                    'countriesTotalCount' => $countries->total()
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
              'success' => false,
              'message' => 'database_error',
              'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CountryRequest $request)
    {
        try {

            $country = Country::createCountry($request);

            if ($request->hasFile('flag')) {
                $file = $request->file('flag');
                $path = 'flags/';

                $file_data = uploadFileWithOriginalName($file, $path, $country->flag);

                $country->flag = $file_data['filePath'];
                $country->update();
            } 

            CacheService::clearCache('countries.all');

            SupplierActivity::createActivity([
                'entity_id' => $country->id,
                'entity_type' => 'countries',
                'action_type' => 'create_country',
                'title' => 'Land #'.$country->id.' '.$country->name.' tillagt',
                'description' => 'Ett nytt land har lagts till.',
                'icon' => 'mdi-flag-outline',
                'route' => null, //'/dashboard/admin/countries/'.$country->id,
                'metadata' => json_encode([
                    'country_id' => $country->id,
                    'new_values' => $request->only([
                        'name',
                        'iso',
                        'iso3',
                        'numcode',
                        'phonecode',
                        'phone_digits'
                    ])
                ])
            ]);


            return response()->json([
                'success' => true,
                'data' => [ 
                    'country' => Country::find($country->id)
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $country = Country::find($id);

            if (!$country)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Land hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'country' => $country
                ]
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CountryRequest $request, $id): JsonResponse
    {
        try {
            $country = Country::find($id);
        
            if ($request->hasFile('flag')) {
                $file = $request->file('flag');
                $path = 'flags/';

                $file_data = uploadFileWithOriginalName($file, $path, $country->flag);

                $country->flag = $file_data['filePath'];
                $country->update();
            } 
            
            if (!$country)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Land hittades inte'
                ], 404);
            
            $oldValues = $country->only(['name', 'iso', 'iso3', 'numcode', 'phonecode', 'phone_digits']);

            $country->updateCountry($request, $country); 

            CacheService::clearCache('countries.all');

            SupplierActivity::createActivity([
                'entity_id' => $country->id,
                'entity_type' => 'countries',
                'action_type' => 'update_country',
                'title' => 'Land #'.$country->id.' '.$country->name.' uppdaterad',
                'description' => 'Ett land har uppdaterats.',
                'icon' => 'mdi-flag-outline',
                'route' => null, //'/dashboard/admin/countries/'.$country->id,
                'metadata' => json_encode([
                    'country_id' => $country->id,
                    'old_values' => $oldValues,
                    'new_values' => $request->only([
                        'name', 'iso', 'iso3', 'numcode', 'phonecode', 'phone_digits'
                    ])
                ])
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [ 
                    'country' => $country
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $country = Country::find($id);
        
            if (!$country)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Land hittades inte'
                ], 404);
            
            $oldValues = $country->only(['name', 'iso', 'iso3', 'numcode', 'phonecode', 'phone_digits']);
            
            $country->deleteCountry($id);

            CacheService::clearCache('countries.all');

            SupplierActivity::createActivity([
                'entity_id' => $country->id,
                'entity_type' => 'countries',
                'action_type' => 'delete_country',
                'title' => 'Land #'.$country->id.' '.$country->name.' borttagen',
                'description' => 'Landet har tagits bort.',
                'icon' => 'mdi-flag-outline',
                'route' => null, //'/dashboard/admin/countries/'.$country->id,
                'metadata' => json_encode([
                    'country_id' => $country->id,
                    'old_values' => $oldValues
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'country' => $country
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    public function updateState($id)
    {
        try {

            $country = Country::find($id);
        
            if (!$country)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Land hittades inte'
                ], 404);
            
            $country->state_id = $country->state_id === 1 ? 2 : 1;
            $country->update();

            CacheService::clearCache('countries.all');

            SupplierActivity::createActivity([
                'entity_id' => $country->id,
                'entity_type' => 'countries',
                'action_type' => $country->state_id === 2 ? 'activate_country' : 'deactivate_country',
                'title' => 'Land #'.$country->id.' '.$country->name.' '.($country->state_id === 2 ? 'aktiverad' : 'inaktiverad'),
                'description' => 'Landet har '.($country->state_id === 2 ? 'aktiverats' : 'inaktiverats').'.',
                'icon' => 'mdi-flag-outline',
                'route' => null, //'/dashboard/admin/countries/'.$country->id,
                'metadata' => json_encode([
                    'country_id' => $country->id
                ])
            ]);

            SupplierActivity::where('entity_id', $country->id)
                ->where('entity_type', 'countries')
                ->update(['route' => null]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'country' => $country
                ]
            ], 200);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }
}
