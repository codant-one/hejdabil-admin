<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Billing;
use App\Models\VehicleClient;
use App\Models\Agreement;
use App\Models\Client;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view clients|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create clients|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit clients|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete clients|administrator')->only(['destroy']);
        $this->middleware(PermissionMiddleware::class . ':view clients|administrator')->only(['pendingItems']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Client::with([
                        'supplier:id,user_id,boss_id',
                        'supplier.user:id,name,last_name,email',
                        'user:id,name,last_name,email,avatar',
                        'user.userDetail:user_id,logo',
                        'state:id,name'
                    ])->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'orderBy',
                            'supplier_id',
                            'state_id'
                        ])
                    );

            if ($limit == -1) {
                $allClients = $query->get();
                $clients = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allClients,
                    $allClients->count(),
                    $allClients->count(),
                    1
                );
            } else {
                $clients = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'clients' => $clients,
                    'clientsTotalCount' => $clients->total()
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
    public function store(ClientRequest $request)
    {
        try {

            $client = Client::createClient($request);

            $order_id = Client::where('supplier_id', $client->supplier_id)
                            ->withTrashed()
                            ->latest('order_id')
                            ->first()
                            ->order_id ?? 0;

            $client->order_id = $order_id + 1;
            $client->update();

            return response()->json([
                'success' => true,
                'data' => [ 
                    'client' => Client::with(['supplier.user', 'user.userDetail'])->find($client->id)
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

            $client = Client::with(['supplier.user'])->withTrashed()->find($id);

            // Cantidad de vehículos vendidos (se mantiene como conteo)
            $carsForSale = VehicleClient::where('client_id', $id)
                ->where('type', 1)
                ->count();

            // Cantidad de vehículos comprados (se mantiene como conteo)
            $carsPurchased = VehicleClient::where('client_id', $id)
                ->where('type', 2)
                ->count();

            $totalBilling = number_format(
                Billing::whereNotNull('id')
                        ->applyFilters(['client_id' => $id])
                        ->sum(DB::raw('total + amount_discount')),
            2);

            $totalPending = number_format(
                Billing::where('state_id', 4)
                        ->applyFilters(['client_id' => $id])
                        ->sum(DB::raw('total + amount_discount')),
            2);

            $totalExpired = number_format(
                Billing::where('state_id', 8)
                        ->applyFilters(['client_id' => $id])
                        ->sum(DB::raw('total + amount_discount')),
            2);

            if (!$client)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kunden hittades inte'
                ], 404);

            $client->carsForSale = $carsForSale;
            $client->carsPurchased = $carsPurchased;
            $client->totalBilling = $totalBilling;
            $client->totalPending = $totalPending;
            $client->totalExpired = $totalExpired;

            return response()->json([
                'success' => true,
                'data' => [ 
                    'client' => $client
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
    public function update(ClientRequest $request, $id): JsonResponse
    {
        try {
            $client = Client::with(['supplier.user', 'user.userDetail'])->find($id);
        
            if (!$client)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kunden hittades inte'
                ], 404);

            $client->updateClient($request, $client); 

            return response()->json([
                'success' => true,
                'data' => [ 
                    'client' => $client
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

            $client = Client::find($id);
        
            if (!$client)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kunden hittades inte'
                ], 404);

            $hasPendingInvoices = Billing::whereIn('state_id', [4, 8])
                ->applyFilters(['client_id' => $id])
                ->exists();

            $hasOpenAgreements = Agreement::where(function ($query) use ($id) {
                $query->whereHas('agreement_client.client', function ($subQuery) use ($id) {
                    $subQuery->where('id', $id);
                })
                ->orWhereHas('vehicle_client.client', function ($subQuery) use ($id) {
                    $subQuery->where('id', $id);
                });
            })
            ->where(function ($query) {
                $query->doesntHave('token')
                    ->orWhereHas('token', function ($tokenQuery) {
                        $tokenQuery->whereRaw("LOWER(signature_status) != 'signed'");
                    });
            })
            ->exists();

            if ($hasOpenAgreements || $hasPendingInvoices)
                return response()->json([
                    'success' => false,
                    'feedback' => 'client_has_open_items_pending',
                    'message' => 'Kunden kan inte raderas'
                ], 400);
            
            $client->deleteClient($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'client' => $client
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

    public function activate($id)
    {
        try {

            $client = Client::onlyTrashed()->where('id', $id)->first();
        
            if (!$client)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kunden hittades inte'
                ], 404);
            
            $client->activateClient($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'client' => $client
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

    public function pendingItems($id): JsonResponse
    {
        try {
            $client = Client::find($id);

            if (!$client)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Kunden hittades inte'
                ], 404);

            $pendingInvoices = Billing::whereIn('state_id', [4, 8])
                ->applyFilters(['client_id' => $id])
                ->select('id', 'invoice_id', 'invoice_date', 'due_date', 'total', 'amount_discount', 'state_id')
                ->orderByDesc('id')
                ->get();

            $openAgreements = Agreement::with([
                    'agreement_type:id,name',
                    'offer:id,offer_id',
                    'commission:id,commission_id',
                    'token:id,agreement_id,signature_status'
                ])
                ->where(function ($query) use ($id) {
                    $query->whereHas('agreement_client.client', function ($subQuery) use ($id) {
                        $subQuery->where('id', $id);
                    })
                    ->orWhereHas('vehicle_client.client', function ($subQuery) use ($id) {
                        $subQuery->where('id', $id);
                    });
                })
                ->where(function ($query) {
                    $query->doesntHave('token')
                        ->orWhereHas('token', function ($tokenQuery) {
                            $tokenQuery->whereRaw("LOWER(signature_status) != 'signed'");
                        });
                })
                ->select('id', 'agreement_id', 'agreement_type_id', 'offer_id', 'commission_id', 'file', 'created_at')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_invoices' => $pendingInvoices,
                    'open_agreements' => $openAgreements,
                ]
            ]);

        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }
}
