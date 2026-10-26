<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Invoice;
use App\Models\SupplierActivity;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view invoices|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create invoices|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit invoices|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete invoices|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Invoice::with(['type:id,name'])
                           ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            if ($limit == -1) {
                $allInvoices = $query->get();
                $invoices = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allInvoices,
                    $allInvoices->count(),
                    max($allInvoices->count(), 1),
                    1
                );
            } else {
                $invoices = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'invoices' => $invoices,
                    'invoicesTotalCount' => $invoices->total()
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
    public function store(InvoiceRequest $request)
    {
        try {

            $invoice = Invoice::createInvoice($request);

            SupplierActivity::createActivity([
                'entity_id' => $invoice->id,
                'entity_type' => 'invoices',
                'action_type' => 'create_invoice',
                'title' => 'Faktura #'.$invoice->id.' '.$invoice->name.' tillagd',
                'description' => 'En ny faktura har lagts till.',
                'icon' => 'custom-attribut',
                'route' => null, //'/dashboard/admin/invoices/'.$invoice->id,
                'metadata' => json_encode([
                    'invoice_id' => $invoice->id,
                    'new_values' => $request->only([
                        'name', 'description', 'type_id'
                    ])
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'invoice' => Invoice::with(['type'])->find($invoice->id)
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

            $invoice = Invoice::with(['type'])->find($id);

            if (!$invoice)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'invoice' => $invoice
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
    public function update(InvoiceRequest $request, $id): JsonResponse
    {
        try {
            $invoice = Invoice::with(['type'])->find($id);

            $oldValues = $invoice->only(['name', 'description', 'type_id']);
        
            if (!$invoice)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
                ], 404);

            $invoice->updateInvoice($request, $invoice); 

            SupplierActivity::createActivity([
                'entity_id' => $invoice->id,
                'entity_type' => 'invoices',
                'action_type' => 'update_invoice',
                'title' => 'Faktura #'.$invoice->id.' '.$invoice->name.' uppdaterad',
                'description' => 'En faktura har uppdaterats.',
                'icon' => 'custom-attribut',
                'route' => null, //'/dashboard/admin/invoices/'.$invoice->id,
                'metadata' => json_encode([
                    'invoice_id' => $invoice->id,
                    'old_values' => $oldValues,
                    'new_values' => $request->only([
                        'name', 'description', 'type_id'
                    ])
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'invoice' => $invoice
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

            $invoice = Invoice::find($id);
        
            if (!$invoice)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
                ], 404);
            
            $oldValues = $invoice->only(['name', 'description', 'type_id']);

            $invoice->deleteInvoice($id);

            SupplierActivity::createActivity([
                'entity_id' => $invoice->id,
                'entity_type' => 'invoices',
                'action_type' => 'delete_invoice',
                'title' => 'Faktura #'.$invoice->id.' '.$invoice->name.' borttagen',
                'description' => 'Fakturan har tagits bort.',
                'icon' => 'custom-attribut',
                'route' => null, //'/dashboard/admin/invoices/'.$invoice->id,
                'metadata' => json_encode([
                    'invoice_id' => $invoice->id,
                    'old_values' => $oldValues
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'invoice' => $invoice
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
