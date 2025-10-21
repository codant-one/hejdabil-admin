<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        
            $query = Billing::with([
                'supplier' => function ($q) {
                    $q->withTrashed()->with(['user' => fn($u) => $u->withTrashed()]);
                },
                'client' => fn($q) => $q->withTrashed(),
                'state',
                'user.userDetail'
            ])->applyFilters(
                $request->only([
                    'search',
                    'orderByField',
                    'orderBy',
                    'supplier_id',
                    'client_id',
                    'state_id'
                ])
            );

            $totalSum =  number_format($query->sum(DB::raw('total + amount_discount')), 2);
            $totalTax =  number_format($query->sum('amount_tax'), 2);
            $totalNeto = number_format($query->sum('subtotal'), 2);
            
            $count = $query->count();

            $billings = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);
          
            return response()->json([
                'success' => true,
                'data' => [
                    'billings' => $billings,
                    'billingsTotalCount' => $count,
                    'totalSum' => $totalSum,
                    'totalTax' => $totalTax,
                    'totalNeto' => $totalNeto
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
                Billing::with([
                    'supplier' => function($query) {
                        $query->withTrashed()
                            ->with('billings')
                            ->with(['user' => function($query) {
                                $query->withTrashed();
                            }]);
                    },
                    'client' => function($query) {
                        $query->withTrashed();
                    }, 
                    'state',
                    'user.userDetail'
                ])->find($id);

            if (!$billing)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
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
                    'message' => 'Fakturan hittades inte'
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
                    'message' => 'Fakturan hittades inte'
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
                    'message' => 'Fakturan hittades inte'
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
            )->when(
                    Auth::check() && Auth::user()->hasRole('User'), function ($query) {
                        return $query->where('supplier_id', Auth::user()->supplier->boss_id);
                    }
            )->get();

            $invoice_id = Billing::whereNull('supplier_id')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'suppliers' => Supplier::with(['user.userDetail', 'billings'])->whereNull('boss_id')->get(),
                    'clients' => $clients,
                    'invoices' => Invoice::all(),
                    'invoice_id' => $invoice_id,
                    'billings' => Billing::whereNull('supplier_id')->get()
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

    public function credit($id)
    {
        try {

            $billing = Billing::find($id);
        
            if (!$billing)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
                ], 404);
            
            $billing = Billing::createCredit($billing);

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
        try {

            $billing = Billing::find($id);
        
            if (!$billing)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Fakturan hittades inte'
                ], 404);
            
            $billing = Billing::createReminder($billing);

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

    public function sendMails(Request $request, $id)
    {
        try {

            $billing = Billing::with(['client', 'supplier.user'])->find($id);
            $billing->is_sent = 1;
            $billing->save();

            $data = [
                'user' => $billing->client->fullname,
                'text' => 'Vi hoppas att detta meddelande får dig att må bra. <br> Vänligen notera att vi har genererat en ny faktura i ditt namn med följande uppgifter:',
                'billing' => $billing,
                'text_info' => 'Bifogat finns fakturan i PDF-format. Du kan ladda ner och granska den när som helst. <br> Om du har några frågor eller behöver mer information, tveka inte att kontakta oss.',
                'buttonText' => 'Nedladdningar',
                'pdfFile' => asset('storage/'.$billing->file)
            ];

            if($request->emailDefault === true) {
                $clientEmail = $billing->client->email;
                $subject = 'Din faktura #'. $billing->invoice_id . ' är tillgänglig';
                    
                try {
                    \Mail::send(
                        'emails.invoices.notifications'
                        , $data
                        , function ($message) use ($clientEmail, $subject, $billing) {
                            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                            $message->to($clientEmail)->subject($subject);

                            $pathToFile = storage_path('app/public/' . $billing->file);
                            if (file_exists($pathToFile)) {
                                $message->attach($pathToFile, [
                                    'as' => Str::replaceFirst('pdfs/', '', $billing->file),
                                    'mime' => 'application/pdf',
                                ]);
                            }
                    });

                } catch (\Exception $e){
                    Log::info("Error mail => ". $e);
                }
            }

            foreach($request->emails as $email) {

                $subject = 'Din faktura #'. $billing->invoice_id . ' är tillgänglig';
                    
                try {
                    \Mail::send(
                        'emails.invoices.notifications'
                        , $data
                        , function ($message) use ($email, $subject, $billing) {
                            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                            $message->to($email)->subject($subject);

                            $pathToFile = storage_path('app/public/' . $billing->file);
                            if (file_exists($pathToFile)) {
                                $message->attach($pathToFile, [
                                    'as' => Str::replaceFirst('pdfs/', '', $billing->file),
                                    'mime' => 'application/pdf',
                                ]);
                            }
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

    public function info() {

        try {

            $suppliers = Supplier::with(['user' => fn($q) => $q->withTrashed()])
            ->whereNull('boss_id')
            ->withTrashed()
            ->get();
        
            $clients = Client::when(
                Auth::check() && Auth::user()->hasRole('Supplier'), function ($query) {
                    return $query->where('supplier_id', Auth::user()->supplier->id);
                }
            )->when(
                Auth::check() && Auth::user()->hasRole('User'), function ($query) {
                    return $query->where('supplier_id', Auth::user()->supplier->boss_id);
                }
            )->withTrashed()->get();

            $sum = number_format(Billing::whereNotNull('id')->applyFilters([])->sum(DB::raw('total + amount_discount')), 2);
            $tax = number_format(Billing::whereNotNull('id')->applyFilters([])->sum('amount_tax'), 2);
            $totalPending = number_format(Billing::where('state_id', 4)->applyFilters([])->sum(DB::raw('total + amount_discount')), 2);
            $totalPaid = number_format(Billing::whereIn('state_id', [7, 9])->applyFilters([])->sum(DB::raw('total + amount_discount')), 2);
            $totalExpired = number_format(Billing::where('state_id', 8)->applyFilters([])->sum(DB::raw('total + amount_discount')), 2);
            $pendingTax = number_format(Billing::where('state_id', 4)->applyFilters([])->sum('amount_tax'), 2);
            $paidTax = number_format(Billing::whereIn('state_id', [7, 9])->applyFilters([])->sum('amount_tax'), 2);
            $expiredTax = number_format(Billing::where('state_id', 8)->applyFilters([])->sum('amount_tax'), 2);

            return response()->json([
                'success' => true,
                'data' => [
                    'suppliers' => $suppliers,
                    'clients' => $clients,
                    'sum' => $sum,
                    'tax' => $tax,
                    'totalPending' => $totalPending,
                    'totalPaid' => $totalPaid,
                    'totalExpired' => $totalExpired,
                    'pendingTax' => $pendingTax,
                    'paidTax' => $paidTax,
                    'expiredTax' => $expiredTax
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
}
