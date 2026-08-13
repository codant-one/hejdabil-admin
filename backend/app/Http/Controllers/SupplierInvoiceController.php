<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Models\SupplierInvoice;
use App\Models\Supplier;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\UserDetails;
use App\Models\User;
use App\Models\Config;
use App\Models\Setting;
use App\Models\SupplierActivity;

use App\Jobs\SendEmailJob;
use App\Services\CacheService;
use App\Services\TwilioSms;

class SupplierInvoiceController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = (int) $request->input('limit', 10);

            // Avoid invalid per-page values (0/negative) in paginator calculations.
            if ($limit !== -1)
                $limit = max(1, $limit);
        
            // Build full query with relations for pagination
            $query = SupplierInvoice::with([
                'supplier' => function ($q) {
                    $q->select('id', 'user_id', 'boss_id', 'deleted_at')
                      ->withTrashed()
                      ->with(['user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'deleted_at')->withTrashed()]);
                },
                'state:id,name',
                'user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'avatar', 'deleted_at')->withTrashed(),
                'user.userDetail:user_id,avatar_id,logo'
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
                $allSupplierInvoices = $query->get();
                $perPage = max(1, $allSupplierInvoices->count());
                $supplierInvoices = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allSupplierInvoices,
                    $allSupplierInvoices->count(),
                    $perPage,
                    1
                );
            } else {
                $supplierInvoices = $query->paginate($limit);
            }
          
            $supplier = $request->supplier_id
                ? Supplier::with(['user' => fn($u) => $u->select('id', 'name', 'last_name', 'email')->withTrashed()])
                    ->withTrashed()
                    ->find($request->supplier_id)
                : null;

            $supplier->supplier_name = trim(($supplier->user->name ?? '') . ' ' . ($supplier->user->last_name ?? ''));

            return response()->json([
                'success' => true,
                'data' => [
                    'supplierInvoices' => $supplierInvoices,
                    'supplierInvoicesTotalCount' => $supplierInvoices->total(),
                    'supplier' => $supplier
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

      /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }

    public function updateState($id)
    {
        try {

            $billing = SupplierInvoice::find($id);
        
            if (!$billing)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
                ], 404);
            
            $oldStateId = $billing->state_id;
            $billing->state_id = ($billing->state_id === 4 || $billing->state_id === 8) ? 7 : 4;
            $billing->update();

           /* SupplierActivity::createActivity([
                'entity_id' => $billing->id,
                'entity_type' => 'supplier_invoices',
                'action_type' => 'update_supplier_invoice_state',
                'title' => 'Faktura #'.$billing->invoice_id.' - ' . ($billing->state_id == 7 ? 'betald' : 'obetald'),
                'description' => $billing->state_id == 7 ? 'Markerades som betald.' : 'Markerades som obetald.',
                'icon' => 'custom-facture',
                'route' => '/dashboard/admin/billings/'.$billing->id,
                'metadata' => json_encode([
                    'billing_id' => $billing->id,
                    'old_values' => ['state_id' => $oldStateId],
                    'new_values' => ['state_id' => $billing->state_id]
                ])
            ]);*/

            return response()->json([
                'success' => true,
                'data' => [ 
                    'billing' => $billing
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

    public function credit($id)
    {
        try {

            $billing = SupplierInvoice::find($id);
        
            if (!$billing)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
                ], 404);

            $billing = SupplierInvoice::createCredit($billing);

            if (!$billing)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
                ], 404);

           /* SupplierActivity::createActivity([
                'entity_id' => $billing->id,
                'entity_type' => 'billings',
                'action_type' => 'create_credit',
                'title' => 'Kreditfaktura #'.$billing->invoice_id.' - skapad',
                'description' => 'En kreditfaktura har skapats.',
                'icon' => 'custom-facture',
                'route' => '/dashboard/admin/billings/'.$billing->id,
                'metadata' => json_encode([
                    'billing_id' => $billing->id,
                    'new_values' => [
                        'credit_id' => $billing->credit_id,
                        'invoice_id' => $billing->invoice_id,
                        'state_id' => $billing->state_id,
                    ],
                ]),
            ]);*/

            return response()->json([
                'success' => true,
                'data' => [ 
                    'billing' => $billing
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

    public function reminder($id)
    {
        //
    }
}
