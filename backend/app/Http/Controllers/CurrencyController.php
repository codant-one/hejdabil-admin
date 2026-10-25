<?php

namespace App\Http\Controllers;

use App\Http\Requests\CurrencyRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Currency;
use App\Models\SupplierActivity;

class CurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view currencies|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create currencies|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit currencies|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete currencies|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Currency::with(['state:id,name'])
                            ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'state_id'
                                ])
                            );

            if ($limit == -1) {
                $allCurrencies = $query->get();
                $currencies = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allCurrencies,
                    $allCurrencies->count(),
                    max($allCurrencies->count(), 1),
                    1
                );
            } else {
                $currencies = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'currencies' => $currencies,
                    'currenciesTotalCount' => $currencies->total()
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
    public function store(CurrencyRequest $request)
    {
        try {

            $currency = Currency::createCurrency($request);

            SupplierActivity::createActivity([
                'entity_id' => $currency->id,
                'entity_type' => 'currencies',
                'action_type' => 'create_currency',
                'title' => 'Valuta #'.$currency->id.' '.$currency->name.' tillagd',
                'description' => 'En ny valuta har lagts till.',
                'icon' => 'custom-pris-information',
                'route' => null, //'/dashboard/admin/currencies/'.$currency->id,
                'metadata' => json_encode([
                    'currency_id' => $currency->id,
                    'new_values' => $request->only([
                        'name', 'code', 'flag'
                    ])
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => Currency::find($currency->id)
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

            $currency = Currency::find($id);

            if (!$currency)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Valuta hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => $currency
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
    public function update(CurrencyRequest $request, $id): JsonResponse
    {
        try {
            $currency = Currency::find($id);

            $oldValues = $currency->only(['name', 'code', 'flag']);
        
            if (!$currency)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Valuta hittades inte'
                ], 404);

            $currency->updateCurrency($request, $currency); 

            SupplierActivity::createActivity([
                'entity_id' => $currency->id,
                'entity_type' => 'currencies',
                'action_type' => 'update_currency',
                'title' => 'Valuta #'.$currency->id.' '.$currency->name.' uppdaterad',
                'description' => 'En valuta har uppdaterats.',
                'icon' => 'custom-pris-information',
                'route' => null, //'/dashboard/admin/currencies/'.$currency->id,
                'metadata' => json_encode([
                    'currency_id' => $currency->id,
                    'old_values' => $oldValues,
                    'new_values' => $request->only([
                        'name', 'code', 'flag'
                    ])
                ])
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => $currency
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

            $currency = Currency::find($id);
        
            if (!$currency)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Valuta hittades inte'
                ], 404);

            $oldValues = $currency->only(['name', 'code', 'flag']);
            
            $currency->deleteCurrency($id);

            SupplierActivity::createActivity([
                'entity_id' => $currency->id,
                'entity_type' => 'currencies',
                'action_type' => 'delete_currency',
                'title' => 'Valuta #'.$currency->id.' '.$currency->name.' borttagen',
                'description' => 'Valutan har tagits bort.',
                'icon' => 'custom-pris-information',
                'metadata' => json_encode([
                    'currency_id' => $currency->id,
                    'old_values' => $oldValues
                ])
            ]);

            SupplierActivity::where('entity_id', $currency->id)
                ->where('entity_type', 'currencies')
                ->update(['route' => null]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => $currency
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

            $currency = Currency::find($id);
        
            if (!$currency)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Valuta hittades inte'
                ], 404);
            
            $currency->state_id = $currency->state_id === 1 ? 2 : 1;
            $currency->update();

            SupplierActivity::createActivity([
                'entity_id' => $currency->id,
                'entity_type' => 'currencies',
                'action_type' => $currency->state_id === 2 ? 'activate_currency' : 'deactivate_currency',
                'title' => 'Valuta #'.$currency->id.' '.$currency->name.' '.($currency->state_id === 2 ? 'aktiverad' : 'inaktiverad'),
                'description' => 'Valutan har '.($currency->state_id === 2 ? 'aktiverats' : 'inaktiverats').'.',
                'icon' => 'custom-pris-information',
                'route' => null, //'/dashboard/admin/currencies/'.$currency->id,
                'metadata' => json_encode([
                    'currency_id' => $currency->id
                ])
            ]);

            SupplierActivity::where('entity_id', $currency->id)
                ->where('entity_type', 'currencies')
                ->update(['route' => null]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'currency' => $currency
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
