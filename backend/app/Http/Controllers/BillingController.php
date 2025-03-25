<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Billing;
use App\Models\Supplier;
use App\Models\Client;
use App\Models\Invoice;

class BillingController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view billing|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create billing|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit billing|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete billing|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Billing::with(['supplier' => function($query) {
                                $query->withTrashed()->with(['user' => function($query) {
                                    $query->withTrashed();
                                }]);
                            }, 'client' => function($query) {
                                $query->withTrashed();
                            }, 'state'])
                           ->applyFilters(
                                $request->only([
                                    'search',
                                    'orderByField',
                                    'orderBy',
                                    'supplier_id',
                                    'client_id',
                                    'state_id'
                                ])
                            );

            $count = $query->count();

            $billings = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);
            $suppliers = Supplier::with(['user' => function($query) {
                $query->withTrashed();
            }])->withTrashed()->get();
            $clients = Client::when(
                Auth::check() && Auth::user()->hasRole('Supplier'), function ($query) {
                    return $query->where('supplier_id', Auth::user()->supplier->id);
                }
            )->withTrashed()->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'suppliers' => $suppliers,
                    'clients' => $clients,
                    'billings' => $billings,
                    'billingsTotalCount' => $count
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
        try {

            $billing = Billing::createBilling($request);

            return response()->json([
                'success' => true,
                'billing' => $billing
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

            $billing = 
                Billing::with(['supplier' => function($query) {
                    $query->withTrashed()->with(['user' => function($query) {
                        $query->withTrashed();
                    }]);
                }, 'client' => function($query) {
                    $query->withTrashed();
                }, 'state'])->find($id);

            if (!$billing)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Invoice not found'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'billing' => $billing
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
    public function update(Request $request, $id): JsonResponse
    {
        try {

            $billing = Billing::with(['supplier', 'client'])->find($id);
        
            if (!$billing)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Invoice not found'
                ], 404);

            $billing->updateBilling($request, $billing); 
            
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $billing = Billing::find($id);
        
            if (!$billing)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Invoice not found'
                ], 404);
            
            $billing->deleteBilling($id);

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

    public function updateState($id)
    {
        try {

            $billing = Billing::find($id);
        
            if (!$billing)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Invoice not found'
                ], 404);
            
            $billing->state_id = 7;
            $billing->update();

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

    public function all(Request $request): JsonResponse
    {
        try {

            $clients = Client::when(
                    Auth::check() && Auth::user()->hasRole('Supplier'), function ($query) {
                        return $query->where('supplier_id', Auth::user()->supplier->id);
                    }
            )->get();

            $invoice_id = Billing::whereNull('supplier_id')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'suppliers' => Supplier::with(['user', 'billings'])->get(),
                    'clients' => $clients,
                    'invoices' => Invoice::all(),
                    'invoice_id' => $invoice_id
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

    public function sendMails(Request $request, $id)
    {
        try {

            $billing = Billing::with(['client'])->find($id);
            $billing->is_sent = 1;
            $billing->save();

            $data = [
                'user' => $billing->client->fullname,
                'text' => 'We hope this message finds you well. <br> Please be advised that we have generated a new invoice in your name with the following details:',
                'billing' => $billing,
                'text_info' => 'Please find attached the invoice in PDF format. You can download and review it at any time. <br> If you have any questions or need more information, please do not hesitate to contact us.',
                'buttonText' => 'Download',
                'pdfFile' => asset('storage/'.$billing->file)
            ];

            if($request->emailDefault === true) {
                $clientEmail = $billing->client->email;
                $subject = 'Your invoice #'. $billing->invoice_id . ' is available';
                    
                try {
                    \Mail::send(
                        'emails.invoices.notifications'
                        , $data
                        , function ($message) use ($clientEmail, $subject) {
                            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                            $message->to($clientEmail)->subject($subject);
                    });

                } catch (\Exception $e){
                    Log::info("Error mail => ". $e);
                }
            }

            foreach($request->emails as $email) {

                $subject = 'Your invoice #'. $billing->invoice_id . ' is available';
                    
                try {
                    \Mail::send(
                        'emails.invoices.notifications'
                        , $data
                        , function ($message) use ($email, $subject) {
                            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                            $message->to($email)->subject($subject);
                    });

                } catch (\Exception $e){
                    Log::info("Error mail => ". $e);
                }
            }

            return response()->json([
                'success' => true
            ]);

        } catch(\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error '.$ex->getMessage(),
                'exception' => $ex->getMessage()
            ], 500);
        }
    }
}
