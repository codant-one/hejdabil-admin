<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Invoice;

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
        
            $query = Invoice::with(['type'])
                           ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy'
                                ])
                            );

            $count = $query->count();

            $invoices = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'invoices' => $invoices,
                    'invoicesTotalCount' => $count
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
        
            if (!$invoice)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
                ], 404);

            $invoice->updateInvoice($request, $invoice); 

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
            
            $invoice->deleteInvoice($id);

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
