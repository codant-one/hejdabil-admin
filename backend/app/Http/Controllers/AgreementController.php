<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgreementRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Agreement;
use App\Models\AgreementType;
use App\Models\Vehicle;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\CarBody;
use App\Models\Gearbox;
use App\Models\Iva;
use App\Models\Fuel;
use App\Models\State;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\AgreementClient;
use App\Models\VehicleClient;
use App\Models\GuarantyType;
use App\Models\InsuranceType;
use App\Models\Currency;
use App\Models\PaymentType;
use App\Models\Identification;
use App\Models\Advance;
use App\Models\CommissionType;
use App\Models\Supplier;
use App\Models\Commission;
use App\Models\Offer;
use App\Models\User;;
use App\Models\UserDetails;
use App\Models\Config;

class AgreementController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view agreements|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create agreements|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit agreements|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete agreements|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;

            $query = Agreement::with([
                        'token' => function($query) {
                            $query->with(['histories' => function($q) {
                                $q->orderBy('created_at', 'asc');
                            }]);
                        },
                        'offer',
                        'commission.vehicle',
                        'agreement_type',
                        'agreement_client',
                        'vehicle_interchange',
                        'vehicle_client.vehicle',
                        'supplier' => function ($q) {
                            $q->withTrashed()->with(['user' => fn($u) => $u->withTrashed()]);
                        },
                        'user.userDetail'
                    ])->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'orderBy',
                            'supplier_id',
                            'agreement_type_id',
                            'client_id'
                        ])
                    );

            $count = $query->count();

            $agreements = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'agreements' => $agreements,
                    'agreementsTotalCount' => $count,
                    'suppliers' => Supplier::with(['user.userDetail', 'billings'])->whereNull('boss_id')->get(),
                    'agreementTypes' => AgreementType::all()
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
    public function store(AgreementRequest $request)
    {
        try {

            return DB::transaction(function () use ($request) {
                $agreement = Agreement::createAgreement($request);

                // Create initial token with 'created' status when document is created
                $signingToken = Str::uuid()->toString();
                $token = $agreement->token()->create([
                    'signing_token' => $signingToken,
                    'recipient_email' => null, // Will be set when signature is requested
                    'token_expires_at' => now()->addDays(30),
                    'signature_status' => 'created',
                    'placement_x' => Agreement::coordinates($agreement, 'x'),
                    'placement_y' => Agreement::coordinates($agreement, 'y'),
                    'placement_page' => 1, // Default to page 1
                    'signature_alignment' => 'left',
                ]);

                // Log 'created' event when document is created
                \App\Models\TokenHistory::logEvent(
                    tokenId: $token->id,
                    eventType: \App\Models\TokenHistory::EVENT_CREATED,
                    description: 'Avtal skapat i systemet',
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                    metadata: [
                        'agreement_id' => $agreement->id,
                        'agreement_title' => $agreement->title,
                        'user_id' => $agreement->user_id,
                    ]
                );

                return response()->json([
                    'success' => true,
                    'data' => [ 
                        'agreement' => Agreement::find($agreement->id)
                    ]
                ]);
            });

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

            $agreement = Agreement::with([
                        'agreement_type',
                        'guaranty_type',
                        'insurance_type',
                        'currency',
                        'iva',
                        'offer',
                        'commission.vehicle',
                        'commission.client',
                        'payment_types',
                        'vehicle_interchange.model.brand',
                        'vehicle_interchange.carbody',
                        'vehicle_interchange.iva_purchase',
                        'agreement_client',
                        'vehicle_client.vehicle.model.brand',
                        'vehicle_client.vehicle.fuel',
                        'vehicle_client.vehicle.gearbox',
                        'vehicle_client.vehicle.payment.payment_types',
                        'supplier.user',
                        'token' => function($query) {
                            $query->with(['histories' => function($q) {
                                $q->orderBy('created_at', 'asc');
                            }]);
                        }
                    ])->find($id);

            if (!$agreement)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Avtalet hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'agreement' => $agreement
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

            $agreement = Agreement::with([
                'agreement_client',
                'vehicle_client'
            ])->find($id);
        
            if (!$agreement)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Avtalet hittades inte'
                ], 404);
            
            return DB::transaction(function () use ($request, $agreement) {
                $agreement->updateAgreement($request, $agreement); 

                return response()->json([
                    'success' => true,
                    'data' => [ 
                        'agreement' => $agreement
                    ]
                ], 200);
            });

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

            $agreement = Agreement::find($id);
        
            if (!$agreement)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Avtalet hittades inte'
                ], 404);
            

            $agreement->deleteAgreement($id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'agreement' => $agreement
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

    public function info() {

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

            $agreement_id = Agreement::whereNull('supplier_id')->count();
            $commission_id = Commission::whereNull('supplier_id')->count();
            $offer_id = Offer::whereNull('supplier_id')->count();
            $vehicles = 
                Vehicle::with(['model.brand'])
                       ->where([
                            ['user_id', Auth::user()->id], 
                            ['state_id', 10]
                       ])->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'brands' => Brand::all(),
                    'models' => CarModel::with(['brand'])->get(),
                    'gearboxes' => Gearbox::all(),
                    'carbodies'  => CarBody::all(),
                    'ivas'  => Iva::all(),
                    'fuels'  => Fuel::all(),
                    'states'  => State::all(),
                    'guarantyTypes'  => GuarantyType::all(),
                    'insuranceTypes'  => InsuranceType::all(),
                    'currencies'  => Currency::all(),
                    'paymentTypes'  => PaymentType::all(),
                    'agreementTypes' => AgreementType::all(),
                    'agreement_id' => $agreement_id,
                    'commission_id' => $commission_id,
                    'offer_id' => $offer_id,
                    'vehicles' => $vehicles,    
                    'clients' => $clients,
                    'client_types' => ClientType::all(),
                    'identifications' => Identification::all(),
                    'advances' => Advance::all(),
                    'commission_types' => CommissionType::all()
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

            $agreement = Agreement::with([
                'agreement_client',
            ])->find($id);

            if ($agreement->supplier && is_null($agreement->supplier->boss_id)) {//supplier
                $user = UserDetails::with(['user'])->find($agreement->supplier->user_id);
                $company = $user->user->userDetail;
                $company->email = $user->user->email;
                $company->name = $user->user->name;
                $company->last_name = $user->user->last_name;
            } else if ($agreement->supplier && !is_null($agreement->supplier->boss_id)) {//user
                $user = User::with(['userDetail', 'supplier.boss.user.userDetail'])->find($agreement->supplier->user_id);
                $company = $user->supplier->boss->user->userDetail;
                $company->email = $user->supplier->boss->user->email;
                $company->name = $user->supplier->boss->user->name;
                $company->last_name = $user->supplier->boss->user->last_name;
            } else { //Admin
                $configCompany = Config::getByKey('company') ?? ['value' => '[]'];
                $configLogo    = Config::getByKey('logo')    ?? ['value' => '[]'];
                $configSignature   = Config::getByKey('signature')    ?? ['value' => '[]'];

                // Extract the "value" supporting array or object
                $getValue = function ($cfg) {
                    if (is_array($cfg)) 
                        return $cfg['value'] ?? '[]';
                    if (is_object($cfg) && isset($cfg->value))
                        return $cfg->value;
                    return '[]';
                };
                
                $companyRaw = $getValue($configCompany);
                $logoRaw    = $getValue($configLogo);
                $signatureRaw    = $getValue($configSignature);

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
                $signatureObj    = $decodeSafe($signatureRaw);
                
                $company->logo = $logoObj->logo ?? null;
                $company->img_signature = $signatureObj->img_signature ?? null;
            }

            $logo = Auth::user()->userDetail ? Auth::user()->userDetail->logo_url : null;

            $data = [
                'company' => $company,
                'logo' => $logo,
                'title' => 'Nytt avtal',
                'icon' => asset('/images/agreements-two.png'),
                'user' => $agreement->agreement_client->fullname,
                'text' => 'Vi hoppas att detta meddelande får dig att må bra. <br> Vänligen notera att vi har genererat en ny avtal i ditt namn med följande uppgifter:',
                'agreement' => $agreement,
                'pdfFile' => asset('storage/'.$agreement->file)
            ];

            if($request->emailDefault === true) {
                $clientEmail = $agreement->agreement_client->email;
                $subject = 'Nytt avtal från ' . $company->company;
                    
                try {
                    \Mail::send(
                        'emails.agreements.notifications'
                        , $data
                        , function ($message) use ($clientEmail, $subject, $agreement) {
                            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                            $message->to($clientEmail)->subject($subject);

                            $pathToFile = storage_path('app/public/' . $agreement->file);
                            if (file_exists($pathToFile)) {
                                $message->attach($pathToFile, [
                                    'as' => Str::replaceFirst('pdfs/', '', $agreement->file),
                                    'mime' => 'application/pdf',
                                ]);
                            }
                    });

                } catch (\Exception $e){
                    Log::info("Error mail => ". $e);
                }
            }

            foreach($request->emails as $email) {

                $subject = 'Din avtal #'. $agreement->agreement_id . ' är tillgänglig';
                    
                try {
                    \Mail::send(
                        'emails.agreements.notifications'
                        , $data
                        , function ($message) use ($email, $subject, $agreement) {
                            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                            $message->to($email)->subject($subject);

                            $pathToFile = storage_path('app/public/' . $agreement->file);
                            if (file_exists($pathToFile)) {
                                $message->attach($pathToFile, [
                                    'as' => Str::replaceFirst('pdfs/', '', $agreement->file),
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

}
