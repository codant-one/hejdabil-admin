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
use App\Models\UserDetails;
use App\Models\User;
use App\Models\Config;
use App\Jobs\SendEmailJob;
use App\Services\CacheService;

class BillingController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view billings|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create billings|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit billings|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete billings|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            // Build base query for aggregates with same filters/role rules as listing
            $baseQuery = Billing::query()->applyFilters(
                $request->only([
                    'search',
                    'supplier_id',
                    'client_id',
                    'state_id'
                ])
            );
            
            // Get aggregates without order/limit
            $aggregates = (clone $baseQuery)->selectRaw('
                SUM(total + amount_discount) as total_sum,
                SUM(amount_tax) as total_tax,
                SUM(subtotal) as total_neto
            ')->first();
        
            // Build full query with relations for pagination
            $query = Billing::with([
                'supplier' => function ($q) {
                    $q->select('id', 'user_id', 'boss_id', 'deleted_at')
                      ->withTrashed()
                      ->with(['user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'deleted_at')->withTrashed()]);
                },
                'client' => fn($q) => $q->select('id', 'fullname', 'email', 'deleted_at')->withTrashed(),
                'state:id,name',
                'user:id,name,last_name,email,avatar',
                'user.userDetail:user_id,logo'
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
            
            if ($limit == -1) {
                $allBillings = $query->get();
                $billings = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allBillings,
                    $allBillings->count(),
                    $allBillings->count(),
                    1
                );
            } else {
                $billings = $query->paginate($limit);
            }
          
            return response()->json([
                'success' => true,
                'data' => [
                    'billings' => $billings,
                    'billingsTotalCount' => $billings->total(),
                    'totalSum' => number_format($aggregates->total_sum ?? 0, 2),
                    'totalTax' => number_format($aggregates->total_tax ?? 0, 2),
                    'totalNeto' => number_format($aggregates->total_neto ?? 0, 2)
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
                'billing' => Billing::with('state')->find($billing->id)
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
                    'supplier.user.userDetail',
                    'supplier.billings',
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
                    'billing' => Billing::with('state')->find($billing->id)
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
                    'suppliers' => CacheService::getActiveSuppliers(),
                    'clients' => $clients,
                    'invoices' => CacheService::getInvoices(),
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

            $billing = Billing::with(['client', 'supplier.user.userDetail'])->find($id);
            $billing->is_sent = 1;
            $billing->save();

            if (Auth::user()->getRoleNames()[0] === 'Supplier') {
                $user = UserDetails::with(['user'])->find(Auth::user()->id);
                $company = $user->user->userDetail;
                $company->email = $user->user->email;
            } else if (Auth::user()->getRoleNames()[0] === 'User') {
                $user = User::with(['userDetail', 'supplier.boss.user.userDetail'])->find(Auth::user()->id);
                $company = $user->supplier->boss->user->userDetail;
                $company->email = $user->supplier->boss->user->email;
            } else { //Admin
                $configCompany = Config::getByKey('company') ?? ['value' => '[]'];
                $configLogo    = Config::getByKey('logo')    ?? ['value' => '[]'];
                
                // Extraer el "value" soportando array u object
                $getValue = function ($cfg) {
                    if (is_array($cfg)) 
                        return $cfg['value'] ?? '[]';
                    if (is_object($cfg) && isset($cfg->value))
                        return $cfg->value;
                    return '[]';
                };
                
                $companyRaw = $getValue($configCompany);
                $logoRaw    = $getValue($configLogo);
                
                $decodeSafe = function ($raw) {
                    $decoded = json_decode($raw);

                    if (is_string($decoded))
                        $decoded = json_decode($decoded);
                
                    if (!is_object($decoded)) 
                        $decoded = (object) [];
                
                    return $decoded;
                };
                
                $company = $decodeSafe($companyRaw);
                $logoObj    = $decodeSafe($logoRaw);
                
                $company->logo = $logoObj->logo ?? null;
            }

            $logo = Auth::user()->userDetail ? Auth::user()->userDetail->logo_url : null;

            $data = [
                'company' => $company,
                'user' => $billing->client->fullname,
                'text' => 'Vi hoppas att detta meddelande får dig att må bra. <br> Vänligen notera att vi har genererat en ny faktura i ditt namn med följande uppgifter:',
                'billing' => $billing,
                'text_info' => 'Bifogat finns fakturan i PDF-format. Du kan ladda ner och granska den när som helst. <br> Om du har några frågor eller behöver mer information, tveka inte att kontakta oss.',
                'buttonText' => 'Ladda ner faktura',
                'pdfFile' => asset('storage/'.$billing->file),
                'title' => 'Ny faktura',
                'icon' => asset('/images/invoices.png'),
                'logo' => $logo
            ];

            if($request->emailDefault === true) {
                $clientEmail = $billing->client->email;
                $subject = 'Ny faktura från ' . $company->company;
                
                $pathToFile = storage_path('app/public/' . $billing->file);
                $attachments = null;
                if (file_exists($pathToFile)) {
                    $attachments = [[
                        'path' => $pathToFile,
                        'as' => Str::replaceFirst('pdfs/', '', $billing->file),
                        'mime' => 'application/pdf'
                    ]];
                }
                    
                // Send email asynchronously with attachments
                SendEmailJob::dispatch(
                    'emails.invoices.notifications',
                    $data,
                    $clientEmail,
                    $subject,
                    null,
                    null,
                    $attachments
                );
            }

            foreach($request->emails as $email) {

                $subject = 'Din faktura #'. $billing->invoice_id . ' är tillgänglig';
                
                $pathToFile = storage_path('app/public/' . $billing->file);
                $attachments = null;
                if (file_exists($pathToFile)) {
                    $attachments = [[
                        'path' => $pathToFile,
                        'as' => Str::replaceFirst('pdfs/', '', $billing->file),
                        'mime' => 'application/pdf'
                    ]];
                }
                    
                // Send email asynchronously with attachments
                SendEmailJob::dispatch(
                    'emails.invoices.notifications',
                    $data,
                    $email,
                    $subject,
                    null,
                    null,
                    $attachments
                );
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
