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
use App\Services\CacheService;
use App\Jobs\SendEmailJob;

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
use App\Models\SupplierActivity;
use App\Models\User;;
use App\Models\UserDetails;
use App\Models\Config;
use App\Models\Notification;

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
                            $query->select('id', 'agreement_id', 'signing_token', 'recipient_email', 'signature_status', 'token_expires_at')
                                  ->with(['histories' => function($q) {
                                      $q->select('id', 'token_id', 'event_type', 'description', 'created_at')
                                        ->orderBy('created_at', 'asc');
                                  }]);
                        },
                        'offer:id,offer_id,reg_num,price',
                        'commission:id,commission_id,commission_type_id,commission_fee,selling_price',
                        'commission.vehicle:id,commission_id,reg_num,model_id',
                        'commission.client:id,fullname,email,phone',
                        'agreement_type:id,name',
                        'agreement_client:id,agreement_id,client_id,fullname,email,phone',
                        'agreement_client.client:id,fullname,deleted_at',
                        'vehicle_interchange:id,reg_num,model_id',
                        'vehicle_client:id,vehicle_id,client_id,fullname,email',
                        'vehicle_client.client:id,fullname,deleted_at',
                        'vehicle_client.vehicle:id,reg_num,model_id',
                        'supplier' => function ($q) {
                            $q->select('id', 'user_id', 'boss_id', 'deleted_at')
                              ->withTrashed()
                              ->with(['user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'deleted_at')->withTrashed()]);
                        },
                        'user' => function ($q) {
                                $q->select('id', 'name', 'last_name', 'email', 'avatar')
                                    ->withTrashed();
                        },
                        'user.userDetail:user_id,avatar_id,logo'
                    ])->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'orderBy',
                            'supplier_id',
                            'status',
                            'agreement_type_id',
                            'client_id'
                        ])
                    );

            if ($limit == -1) {
                $allAgreements = $query->get();
                $agreements = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allAgreements,
                    $allAgreements->count(),
                    $allAgreements->count(),
                    1
                );
            } else {
                $agreements = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'agreements' => $agreements,
                    'agreementsTotalCount' => $agreements->total(),
                    'suppliers' => CacheService::getActiveSuppliers(),
                    'agreementTypes' => CacheService::getAgreementTypes()
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

                SupplierActivity::createActivity([
                    'entity_id' => $agreement->id,
                    'entity_type' => 'agreements',
                    'action_type' => 'create_agreement',
                    'title' => $this->agreementActivityTitle($agreement, ' - skapat'),
                    'description' => 'Ett nytt avtal har skapats.',
                    'icon' => 'custom-contract',
                    'route' => $this->agreementActivityRoute($agreement->id),
                    'metadata' => json_encode([
                        'agreement_id' => $agreement->id,
                        'new_values' => $request->only($this->agreementActivityRequestFields())
                    ])
                ]);

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

            $oldValues = $this->agreementActivityStoredValues($agreement);
            
            return DB::transaction(function () use ($request, $agreement, $oldValues) {
                $agreement->updateAgreement($request, $agreement); 

                SupplierActivity::createActivity([
                    'entity_id' => $agreement->id,
                    'entity_type' => 'agreements',
                    'action_type' => 'update_agreement',
                    'title' => $this->agreementActivityTitle($agreement, '- uppdaterat'),
                    'description' => 'Ett avtal har uppdaterats.',
                    'icon' => 'custom-contract',
                    'route' => $this->agreementActivityRoute($agreement->id),
                    'metadata' => json_encode([
                        'agreement_id' => $agreement->id,
                        'old_values' => $oldValues,
                        'new_values' => $request->only($this->agreementActivityRequestFields())
                    ])
                ]);

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

            $oldValues = $this->agreementActivityStoredValues($agreement);

            SupplierActivity::createActivity([
                'entity_id' => $agreement->id,
                'entity_type' => 'agreements',
                'action_type' => 'delete_agreement',
                'title' => $this->agreementActivityTitle($agreement, ' - borttaget'),
                'description' => 'Avtalet togs bort.',
                'icon' => 'custom-contract',
                'metadata' => json_encode([
                    'agreement_id' => $agreement->id,
                    'old_values' => $oldValues
                ])
            ]);

            SupplierActivity::where('entity_id', $agreement->id)
                ->where('entity_type', 'agreements')
                ->update(['route' => null]);
            
            //Delete notifications related to the vehicle's stock edit page to prevent access to deleted vehicle through notifications
            Notification::deleteNotificationsByRoute('agreements?file_id='.$id);

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

            $user = Auth::user();
            $isSupplier = Auth::check() && $user->hasRole('Supplier');
            $isUser = Auth::check() && $user->hasRole('User');

            $supplier_id = match (true) {
                $isSupplier => $user->supplier?->id,
                $isUser => $user->supplier?->boss_id,
                default => null,
            };

            $vehicles = 
                Vehicle::with(['model.brand'])
                       ->where([
                            ['supplier_id', $supplier_id], 
                            ['state_id', 10]
                       ])->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'brands' => CacheService::getBrands(),
                    'models' => CacheService::getCarModels(),
                    'gearboxes' => CacheService::getGearboxes(),
                    'carbodies'  => CacheService::getCarBodies(),
                    'ivas'  => CacheService::getIvas(),
                    'fuels'  => CacheService::getFuels(),
                    'states'  => CacheService::getStates(),
                    'guarantyTypes'  => CacheService::getGuarantyTypes(),
                    'insuranceTypes'  => CacheService::getInsuranceTypes(),
                    'currencies'  => CacheService::getCurrencies(),
                    'paymentTypes'  => CacheService::getPaymentTypes(),
                    'agreementTypes' => CacheService::getAgreementTypes(),
                    'agreement_id' => $agreement_id,
                    'commission_id' => $commission_id,
                    'offer_id' => $offer_id,
                    'vehicles' => $vehicles,    
                    'clients' => $clients,
                    'client_types' => CacheService::getClientTypes(),
                    'identifications' => CacheService::getIdentifications(),
                    'advances' => CacheService::getAdvances(),
                    'commission_types' => CacheService::getCommissionTypes(),
                    'countries' => CacheService::getCountries()
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
                'token',
            ])->find($id);

            if (!$agreement)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Avtalet hittades inte'
                ], 404);

            if($agreement->supplier_id === null) {
                //Admin
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
                $logo = $company->logo ? asset('storage/' . $company->logo) : null;
            } else {
                $user = UserDetails::with(['user'])->where('user_id', $agreement->supplier->user_id)->first();
                $company = $user->user->userDetail;
                $company->email = $user->user->email;
                $company->name = $user->user->name;
                $company->last_name = $user->user->last_name;
                $logo = $user->user->userDetail->logo_url ?? null;
            }

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
                
                $pathToFile = storage_path('app/public/' . $agreement->file);
                $attachments = null;
                if (file_exists($pathToFile)) {
                    $attachments = [[
                        'path' => $pathToFile,
                        'as' => Str::replaceFirst('pdfs/', '', $agreement->file),
                        'mime' => 'application/pdf'
                    ]];
                }
                    
                // Send email asynchronously with attachments
                SendEmailJob::dispatch(
                    'emails.agreements.notifications',
                    $data,
                    $clientEmail,
                    $subject,
                    null,
                    null,
                    $attachments
                );
            }

            foreach($request->emails as $email) {

                $subject = 'Din avtal #'. $agreement->agreement_id . ' är tillgänglig';
                
                $pathToFile = storage_path('app/public/' . $agreement->file);
                $attachments = null;
                if (file_exists($pathToFile)) {
                    $attachments = [[
                        'path' => $pathToFile,
                        'as' => Str::replaceFirst('pdfs/', '', $agreement->file),
                        'mime' => 'application/pdf'
                    ]];
                }
                    
                // Send email asynchronously with attachments
                SendEmailJob::dispatch(
                    'emails.agreements.notifications',
                    $data,
                    $email,
                    $subject,
                    null,
                    null,
                    $attachments
                );
            }

            SupplierActivity::createActivity([
                'entity_id' => $agreement->id,
                'entity_type' => 'agreements',
                'action_type' => 'send_agreement',
                'title' => $this->agreementActivityTitle($agreement, ' - skickat'),
                'description' => 'Avtalet skickades via e-post.',
                'icon' => 'custom-contract',
                'route' => $this->agreementActivityRoute($agreement->id),
                'metadata' => json_encode([
                    'agreement_id' => $agreement->id,
                    'emailDefault' => $request->emailDefault,
                    'emails' => $request->emails,
                    'agreement_client_email' => $agreement->agreement_client?->email,
                    'signature_status' => $agreement->token?->signature_status,
                ])
            ]);

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

    private function agreementActivityRoute(int $agreementId): string
    {
        return '/dashboard/admin/agreements?file_id='.$agreementId;
    }

    private function agreementActivityTitle(Agreement $agreement, string $suffix): string
    {
        $typeName = $this->agreementActivityTypeName($agreement);
        $identifier = $this->agreementActivityIdentifier($agreement);

        return trim($typeName.' '.$identifier.' '.$suffix);
    }

    private function agreementActivityTypeName(Agreement $agreement): string
    {
        $agreement->loadMissing('agreement_type');

        return $agreement->agreement_type?->name ?? 'Avtal';
    }

    private function agreementActivityIdentifier(Agreement $agreement): string
    {
        $agreement->loadMissing([
            'offer',
            'commission.vehicle',
            'vehicle_client.vehicle',
        ]);

        return match ((int) $agreement->agreement_type_id) {
            4 => $agreement->offer?->reg_num ?? (string) $agreement->agreement_id,
            3 => $agreement->commission?->vehicle?->reg_num ?? (string) $agreement->agreement_id,
            default => $agreement->vehicle_client?->vehicle?->reg_num ?? (string) $agreement->agreement_id,
        };
    }

    private function agreementActivityRequestFields(): array
    {
        return [
            'agreement_type_id', 'vehicle_client_id', 'vehicle_interchange_id', 'guaranty_type_id',
            'insurance_type_id', 'currency_id', 'payment_type_id', 'iva_id',
            'offer_id', 'offerId', 'commission_id', 'commissionId', 'agreement_id',
            'sale_date', 'residual_debt', 'residual_price', 'fair_value',
            'price', 'iva_sale_amount', 'iva_sale_exclusive', 'iva_purchase_amount',
            'iva_purchase_exclusive', 'discount', 'registration_fee', 'total_sale',
            'advance_id', 'vehicle_payment_id', 'middle_price', 'payment_type',
            'payment_received', 'payment_method_forcash', 'installment_amount', 'installment_contract_upon_delivery',
            'guaranty', 'guaranty_description', 'insurance_company', 'insurance_company_description',
            'payment_description', 'terms_other_conditions', 'terms_other_information', 'client_id',
            'client_type_id', 'country_id', 'fullname', 'email',
            'organization_number', 'address', 'street', 'postal_code',
            'phone', 'reg_num', 'brand_id', 'model_id',
            'model', 'year', 'color', 'chassis',
            'car_name', 'engine', 'mileage', 'vehicle_id',
            'purchase_date', 'purchase_price', 'iva_purchase_id', 'gearbox_id',
            'number_keys', 'service_book', 'summer_tire', 'winter_tire',
            'fuel_id', 'comments', 'comment', 'car_body_id',
            'generation', 'control_inspection', 'last_service', 'last_service_date',
            'dist_belt', 'last_dist_belt', 'last_dist_belt_date', 'interchange',
            'reg_num_interchange', 'brand_id_interchange', 'model_id_interchange', 'model_interchange',
            'car_body_id_interchange', 'iva_purchase_id_interchange', 'year_interchange', 'color_interchange',
            'purchase_price_interchange', 'chassis_interchange', 'car_name_interchange', 'engine_interchange',
            'mileage_interchange', 'generation_interchange', 'control_inspection_interchange', 'fuel_id_interchange',
            'gearbox_id_interchange', 'number_keys_interchange', 'service_book_interchange', 'summer_tire_interchange',
            'winter_tire_interchange', 'dist_belt_interchange', 'last_dist_belt_interchange', 'last_dist_belt_date_interchange',
            'last_service_interchange', 'last_service_date_interchange', 'comments_interchange', 'purchase_date_interchange',
            'is_loan', 'loan_amount', 'lessor', 'remaining_amount',
            'settled_by', 'bank', 'account', 'description',
            'type', 'save_client', 'commission_type_id', 'commission_fee',
            'start_date', 'end_date', 'outstanding_debt', 'remaining_debt',
            'paid_bank', 'selling_price', 'payment_days'
        ];
    }

    private function agreementActivityStoredValues(Agreement $agreement): array
    {
        $agreement->loadMissing('agreement_client');

        $agreementValues = $agreement->only([
            'agreement_type_id', 'vehicle_client_id', 'vehicle_interchange_id', 'guaranty_type_id',
            'insurance_type_id', 'currency_id', 'payment_type_id', 'iva_id',
            'offer_id', 'commission_id', 'agreement_id', 'sale_date',
            'residual_debt', 'residual_price', 'fair_value', 'price',
            'iva_sale_amount', 'iva_sale_exclusive', 'iva_purchase_amount', 'iva_purchase_exclusive',
            'discount', 'registration_fee', 'total_sale', 'advance_id',
            'vehicle_payment_id', 'middle_price', 'payment_type', 'payment_received',
            'payment_method_forcash', 'installment_amount', 'installment_contract_upon_delivery', 'guaranty',
            'guaranty_description', 'insurance_company', 'insurance_company_description', 'payment_description',
            'terms_other_conditions', 'terms_other_information'
        ]);

        $clientFields = [
            'client_id', 'client_type_id', 'country_id', 'fullname', 'email',
            'organization_number', 'address', 'street', 'postal_code', 'phone'
        ];

        $clientValues = $agreement->agreement_client
            ? $agreement->agreement_client->only($clientFields)
            : array_fill_keys($clientFields, null);

        return array_merge($agreementValues, $clientValues);
    }

}
