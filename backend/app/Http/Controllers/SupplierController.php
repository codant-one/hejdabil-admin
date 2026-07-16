<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Http\Requests\SupplierSwishRequest;
use App\Http\Requests\UserRequest;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Carbon\Carbon;
use Throwable;

use Spatie\Permission\Middlewares\PermissionMiddleware;
use App\Events\ForceLogoutUserEvent;
use App\Jobs\SendEmailJob;

use App\Models\User;
use App\Models\UserRegisterToken;
use App\Models\Agreement;
use App\Models\Billing;
use App\Models\Payout;
use App\Models\Supplier;
use App\Models\UserDetails;
use App\Models\SupplierActivity;
use App\Models\Client;
use App\Models\SmsMessage;
use App\Models\Note;
use App\Models\Document;
use App\Models\Vehicle;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view suppliers|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create suppliers|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit suppliers|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete suppliers|administrator')->only(['destroy']);
    }

    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;
        
            $query = Supplier::with([
                        'user' => fn($u) => $u->select('id', 'name', 'last_name', 'email', 'avatar', 'full_profile', 'deleted_at')->withTrashed(),
                        'user.userDetail:user_id,logo,company,organization_number,phone,landline',
                        'creator:id,name,last_name,email,avatar',
                        'creator.userDetail:user_id,company',
                        'state:id,name'
                    ])
                    ->withTrashed()
                    ->clientsCount()
                    ->acceptedSmsCount()
                    ->whereNull('boss_id')
                    ->applyFilters(
                    $request->only([
                        'search',
                        'orderByField',
                        'orderBy',
                        'state_id'
                    ])
                );

            if ($limit == -1) {
                $allSuppliers = $query->get();
                $suppliers = new \Illuminate\Pagination\LengthAwarePaginator(
                    $allSuppliers,
                    $allSuppliers->count(),
                    max($allSuppliers->count(), 1),
                    1
                );
            } else {
                $suppliers = $query->paginate($limit);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'suppliers' => $suppliers,
                    'suppliersTotalCount' => $suppliers->total()
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
    public function store(SupplierRequest $request)
    {
        try {

            $password = Str::random(8);
            $request->merge(['password' => $password]);

            $supplier = Supplier::createSupplier($request);

            UserRegisterToken::updateOrCreate(
                ['user_id' => $supplier->user_id],
                ['token' => Str::random(60)]
            );

            $email = $supplier->user->email;
            $subject = 'Välkommen till Billogg - ditt konto är skapat';

            $data = [
                'title' => 'Välkommen till Billogg',
                'user' => $supplier->user->name . ' ' . $supplier->user->last_name,
                'email'=> $email,
                'password' => $password,
                'buttonLink' => env('APP_DOMAIN'),
                'icon' => asset('/images/users.png'),
            ];

            $supplier = Supplier::with(['user.userDetail'])->find($supplier->id);

            SupplierActivity::createActivity([
                'entity_id' => $supplier->id,
                'entity_type' => 'suppliers',
                'action_type' => 'create_supplier',
                'title' => 'Leverantör #'.$supplier->id.' '.$supplier->user->name.' '.$supplier->user->last_name.' tillagd',
                'description' => 'En ny leverantör har lagts till.',
                'icon' => 'custom-supplier',
                'route' => '/dashboard/admin/suppliers/'.$supplier->id,
                'metadata' => json_encode([
                    'supplier_id' => $supplier->id,
                    'new_values' => $request->only([
                        'name', 'last_name', 'email', 'company', 'organization_number', 'link',
                        'address', 'street', 'postal_code', 'phone', 'landline', 'swish',
                        'sms_sender', 'bank', 'account_number', 'user_id',
                        'creator_id', 'boss_id', 'order_id'
                    ])
                ])
            ]);
            
            // Enviar email de forma asíncrona usando Job
            try {
                SendEmailJob::dispatch(
                    'emails.auth.client_created',
                    $data,
                    $email,
                    $subject
                )->onQueue('emails');

                $message = 'send_email';
                $responseMail = 'E-post schemalagd för att skickas till leverantör.';
            } catch (\Exception $e){
                $message = 'error';
                $responseMail = $e->getMessage();
            } 

            return response()->json([
                'success' => true,
                'email_response' => $responseMail,
                'data' => [ 
                    'supplier' => Supplier::with(['user.userDetail'])->find($supplier->id)
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

    public function resendInvitation($id): JsonResponse
    {
        try {
            $supplier = Supplier::with(['user'])->where('id', $id)->first();

            if (!$supplier) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            }

            if (!$supplier->user) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Användaren hittades inte'
                ], 404);
            }

            $password = Str::random(8);
            $supplier->user->password = Hash::make($password);
            $supplier->user->save();

            UserRegisterToken::updateOrCreate(
                ['user_id' => $supplier->user_id],
                ['token' => Str::random(60)]
            );

            $email = $supplier->user->email;
            $subject = 'Välkommen till Billogg - ditt konto är skapat';

            $data = [
                'title' => 'Välkommen till Billogg',
                'user' => $supplier->user->name . ' ' . $supplier->user->last_name,
                'email' => $email,
                'password' => $password,
                'buttonLink' => env('APP_DOMAIN'),
                'icon' => asset('/images/users.png'),
            ];

            $responseMail = 'E-post schemalagd för att skickas till leverantör.';

            try {
                SendEmailJob::dispatch(
                    'emails.auth.client_created',
                    $data,
                    $email,
                    $subject
                )->onQueue('emails');
            } catch (\Exception $e) {
                $responseMail = $e->getMessage();

                return response()->json([
                    'success' => false,
                    'message' => 'mail_send_error',
                    'email_response' => $responseMail
                ], 500);
            }

            SupplierActivity::createActivity([
                'entity_id' => $supplier->id,
                'entity_type' => 'suppliers',
                'action_type' => 'resend_supplier_invitation',
                'title' => 'Inbjudan skickad igen till leverantör #'.$supplier->id.' '.$supplier->user->name.' '.$supplier->user->last_name,
                'description' => 'Inbjudan skickats på nytt till leverantören.',
                'icon' => 'custom-supplier',
                'route' => '/dashboard/admin/suppliers/'.$supplier->id,
                'metadata' => json_encode([
                    'supplier_id' => $supplier->id,
                    'email' => $supplier->user->email,
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Inbjudan skickad på nytt.',
                'email_response' => $responseMail,
                'data' => [
                    'supplier' => Supplier::with(['user.userDetail'])->find($supplier->id)
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

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $supplier = Supplier::with(['user.userDetail', 'state'])
                                ->withTrashed()
                                ->clientsCount()
                                ->find($id);

            if (!$supplier)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);

            return response()->json([
                'success' => true,
                'data' => [ 
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
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, $id): JsonResponse
    {
        try {

            $supplier = Supplier::with(['user.userDetail', 'creator.userDetail'])->find($id);
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);

            $fields = [
                'name', 'last_name', 'email', 'company', 'organization_number', 'link',
                'address', 'street', 'postal_code', 'phone', 'landline', 'swish',
                'sms_sender', 'bank', 'account_number', 'user_id',
                'creator_id', 'boss_id', 'order_id'
            ];

            $oldValues = $this->mapSupplierActivityValues($supplier, $fields);

            $supplier->updateSupplier($request, $supplier); 

            $supplier->refresh()->load(['user.userDetail']);
            $newValues = $this->mapSupplierActivityValues($supplier, $fields);

            SupplierActivity::createActivity([
                'entity_id' => $supplier->id,
                'entity_type' => 'suppliers',
                'action_type' => 'update_supplier',
                'title' => 'Uppgifterna för #'.$supplier->id.' '.$supplier->user->name.' '.$supplier->user->last_name.' har uppdaterats.',
                'description' => 'Leverantören har uppdaterats.',
                'icon' => 'custom-supplier',
                'route' => '/dashboard/admin/suppliers/'.$supplier->id,
                'metadata' => json_encode([
                    'supplier_id' => $supplier->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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

            $supplier = Supplier::with(['user', 'state'])->find($id);
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);

            $deletionSummary = $this->buildDeletionSummary($supplier);
            $supplierNotificationRecipient = $supplier->user;

            SupplierActivity::createActivity([
                'entity_id' => $supplier->id,
                'entity_type' => 'suppliers',
                'action_type' => 'delete_supplier',
                'title' => 'Leverantör #'.$supplier->id.' '.$supplier->user?->name.' '.$supplier->user?->last_name.' borttagen',
                'description' => 'Leverantören har avaktiverats.',
                'icon' => 'custom-supplier',
                'route' => '/dashboard/admin/suppliers/'.$supplier->id,
                'metadata' => json_encode([
                    'supplier_id' => $supplier->id,
                    'deletion_summary' => $deletionSummary,
                ])
            ]);

            $supplier->deleteSupplier($id);
            $this->sendSupplierDeactivationEmail($supplierNotificationRecipient);

            $supplier = Supplier::with(['user', 'state'])->withTrashed()->find($id);

            $message = 'Leverantör borttagen!';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [ 
                    'supplier' => $supplier,
                    'deletion_mode' => 'soft',
                    'deletion_summary' => $deletionSummary
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

    public function deletionInfo($id): JsonResponse
    {
        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->buildDeletionSummary($supplier)
            ], 200);
        } catch (\Illuminate\Database\QueryException $ex) {
            return response()->json([
                'success' => false,
                'message' => 'database_error',
                'exception' => $ex->getMessage()
            ], 500);
        }
    }

    private function buildDeletionSummary(Supplier $supplier): array
    {
        $associations = [
            'clients' => $supplier->clients()->count(),
            'billings' => $supplier->billings()->count(),
            'vehicles' => $supplier->vehicles()->count(),
            'agreements' => $supplier->agreements()->count(),
            'payouts' => $supplier->payouts()->count(),
            'documents' => $supplier->documents()->count(),
            'notes' => $supplier->notes()->count(),
        ];

        $totalAssociations = array_sum($associations);

        return [
            'can_force_delete' => false,
            'total_associations' => $totalAssociations,
            'associations' => $associations,
        ];
    }

    private function sendSupplierDeactivationEmail(?User $user): void
    {
        if (!$user || empty($user->email)) {
            return;
        }

        $subject = 'Ditt Billogg-konto har avaktiverats';

        $data = [
            'title' => 'Kontot har avaktiverats',
            'icon' => asset('/images/user-close.svg'),
        ];

        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        try {
            Mail::send(
                'emails.auth.supplier_deactivated',
                $data,
                function ($message) use ($user, $subject, $fromAddress, $fromName) {
                    if (!empty($fromAddress)) {
                        $message->from($fromAddress, $fromName);
                    }

                    $message->to($user->email)->subject($subject);
                }
            );
        } catch (Throwable $exception) {
            Log::warning('Failed to send supplier deactivation email.', [
                'supplier_user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function inactiveSupplierByEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'feedback' => 'params_validation_failed',
                'message' => $validator->errors()
            ], 400);
        }

        try {
            $inactiveSupplier = Supplier::withTrashed()
                ->with(['user' => function ($query) {
                    $query->withTrashed();
                }])
                ->whereNull('boss_id')
                ->whereNotNull('deleted_at')
                ->whereHas('user', function ($query) use ($request) {
                    $query->withTrashed()
                        ->where('email', strtolower($request->email))
                        ->whereNotNull('deleted_at');
                })
                ->first();

            if (!$inactiveSupplier || !$inactiveSupplier->user) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'inactive_supplier' => null
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'inactive_supplier' => [
                        'supplier_id' => $inactiveSupplier->id,
                        'user_id' => $inactiveSupplier->user->id,
                        'email' => $inactiveSupplier->user->email
                    ]
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

            $supplier = Supplier::onlyTrashed()->where('id', $id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            
            $supplier->activateSupplier($id);
            $supplier->refresh()->load(['user', 'state']);

            SupplierActivity::createActivity([
                'entity_id' => $supplier->id,
                'entity_type' => 'suppliers',
                'action_type' => 'activate_supplier',
                'title' => 'Leverantör #'.$supplier->id.' '.$supplier->user?->name.' '.$supplier->user?->last_name.' aktiverad',
                'description' => 'Leverantören har aktiverats.',
                'icon' => 'custom-supplier',
                'route' => '/dashboard/admin/suppliers/'.$supplier->id,
                'metadata' => json_encode([
                    'supplier_id' => $supplier->id,
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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

    public function swish(SupplierSwishRequest $request, $id)
    {
        try {

            $supplier = Supplier::with(['user'])->where('id', $id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);

            $oldValues = [
                'is_payout' => $supplier->is_payout,
                'payout_number' => $supplier->payout_number,
                'sms_sender' => $supplier->sms_sender,
            ];
            
            $supplier->updateSwishSettings($request, $id);
            $supplier->refresh();

            $newValues = [
                'is_payout' => $supplier->is_payout,
                'payout_number' => $supplier->payout_number,
                'sms_sender' => $supplier->sms_sender,
            ];

            SupplierActivity::createActivity([
                'entity_id' => $supplier->id,
                'entity_type' => 'suppliers',
                'action_type' => 'swish_supplier',
                'title' => 'Swish-inställningar för leverantör #'.$supplier->id.' '.$supplier->user?->name.' '.$supplier->user?->last_name.' uppdaterade',
                'description' => 'Swish-inställningarna har uppdaterats.',
                'icon' => 'custom-supplier',
                'route' => '/dashboard/admin/suppliers/'.$supplier->id,
                'metadata' => json_encode([
                    'supplier_id' => $supplier->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'payout_number' => $supplier->payout_number,
                    'sms_sender' => $supplier->sms_sender,
                    'is_payout' => $supplier->is_payout,
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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

    public function masterPassword(Request $request, $id)
    {
        try {
            $supplier = Supplier::where('id', $id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            
            $supplier->masterPassword($request, $id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'supplier' => $supplier
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

    public function getMasterPassword($id)
    {
        try {
            $supplier = Supplier::where('id', $id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte'
                ], 404);
            
            return response()->json([
                'success' => true,
                'data' => [ 
                    'master_password' => $supplier->master_password,
                    'csr_url' => $supplier->csr_url,
                    'key_url' => $supplier->key_url,
                    'pem_url' => $supplier->pem_url,
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

    public function users(Request $request): JsonResponse
    {
        try {
            $limit = $request->has('limit') ? $request->limit : 10;

            $query = Supplier::with(['user.roles.permissions','user.permissions', 'user.userDetail'])
                         ->when(Auth::user()->getRoleNames()[0] === 'Supplier', function ($query){
                            $query->where('boss_id', Auth::user()->supplier->id);
                         })
                         ->when(Auth::user()->getRoleNames()[0] === 'User', function ($query){
                            $query->where('boss_id', Auth::user()->supplier->boss->id)
                                  ->where('id', '!=', Auth::user()->supplier->id);
                         });

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($uq) use ($search) {
                        $uq->withTrashed()
                            ->where(function ($inner) use ($search) {
                                $inner->where('name', 'LIKE', '%' . $search . '%')
                                      ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                                      ->orWhere('email', 'LIKE', '%' . $search . '%')
                                      ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                            });
                    })
                    ->orWhereHas('user.userDetail', function ($dq) use ($search) {
                        $dq->where('personal_phone', 'LIKE', '%' . $search . '%');
                    });
                });
            }

            $query->applyFilters(
                $request->only([
                    'orderByField',
                    'orderBy'
                ])
            );

            $count = $query->count();

            $users = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'users' => $users,
                    'usersTotalCount' => $count
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

    public function reportUsers(Request $request): JsonResponse
    {
        try {
            $limit = $request->has('limit') ? (int) $request->limit : 10;
            $page = max(1, (int) $request->input('page', 1));
            $supplierId = $this->getCurrentSupplierId();

            $query = Supplier::with(['user.roles.permissions', 'user.permissions', 'user.userDetail'])
                ->where(function ($query) use ($supplierId) {
                    $query->where('id', $supplierId)
                        ->orWhere('boss_id', $supplierId);
                });

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($uq) use ($search) {
                    $uq->withTrashed()
                        ->where(function ($inner) use ($search) {
                            $inner->where('name', 'LIKE', '%' . $search . '%')
                                  ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                                  ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                        });
                });
            }

            $teamSuppliers = $query->applyFilters(
                    $request->only([
                        'orderByField',
                        'orderBy'
                    ])
                )
                ->orderBy('order_id')
                ->orderBy('id')
                ->get();

            $teamUserIds = $teamSuppliers
                ->pluck('user_id')
                ->filter()
                ->values()
                ->all();

            $billingCountsByUser = $this->getTeamDocumentCountsByUser(
                Billing::query()->where('supplier_id', $supplierId),
                $teamUserIds,
            );
            $payoutCountsByUser = $this->getTeamDocumentCountsByUser(
                Payout::query()->where('supplier_id', $supplierId),
                $teamUserIds,
            );
            $agreementCountsByUser = $this->getTeamDocumentCountsByUser(
                Agreement::query()->where('supplier_id', $supplierId),
                $teamUserIds,
            );

            $usersCollection = $teamSuppliers->map(function ($teamSupplier) use (
                $billingCountsByUser,
                $payoutCountsByUser,
                $agreementCountsByUser,
                $supplierId,
            ) {
                $user = $teamSupplier->user;
                $userId = $teamSupplier->user_id;
                $invoices = (int) ($billingCountsByUser->get($userId, 0));
                $swish = (int) ($payoutCountsByUser->get($userId, 0));
                $agreements = (int) ($agreementCountsByUser->get($userId, 0));

                return [
                    'id' => $teamSupplier->id,
                    'user_id' => $teamSupplier->user_id,
                    'is_boss' => $teamSupplier->id === $supplierId,
                    'order_id' => $teamSupplier->order_id,
                    'deleted_at' => $teamSupplier->deleted_at,
                    'user' => $user,
                    'name' => $user?->name,
                    'last_name' => $user?->last_name,
                    'email' => $user?->email,
                    'avatar' => $user?->avatar,
                    'user_detail' => $user?->userDetail,
                    'invoices' => $invoices,
                    'swish' => $swish,
                    'agreements' => $agreements,
                    'total_actions' => $invoices + $swish + $agreements,
                ];
            })
                ->sortByDesc('total_actions')
                ->values();

            $count = $usersCollection->count();
            $perPage = $limit === -1 ? max(1, $count) : max(1, $limit);

            $users = new \Illuminate\Pagination\LengthAwarePaginator(
                $usersCollection->forPage($page, $perPage)->values(),
                $count,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return response()->json([
                'success' => true,
                'data' => [ 
                    'users' => $users,
                    'usersTotalCount' => $count
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

    public function customerOverviewTeam(Request $request): JsonResponse
    {
        try {
            $limit = $request->has('limit') ? (int) $request->limit : 10;
            $page = max(1, (int) $request->input('page', 1));
            $requestedSupplierId = (int) $request->input('supplier_id', 0);
            $supplierId = $requestedSupplierId > 0
                ? $requestedSupplierId
                : $this->getCurrentSupplierId();

            if ($supplierId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'supplier_not_found',
                ], 422);
            }

            [$hasDateFilter, $dateFrom, $dateTo] = $this->resolveDateFilters($request);

            $filterStart = $hasDateFilter
                ? ($dateFrom ?: Carbon::today()->copy()->subMonthsNoOverflow(11)->startOfMonth())->copy()->startOfDay()
                : null;
            $filterEnd = $hasDateFilter
                ? ($dateTo ?: Carbon::today()->copy()->endOfDay())->copy()->endOfDay()
                : null;

            $teamSuppliers = Supplier::query()
                ->with(['user.userDetail'])
                ->where(function ($query) use ($supplierId) {
                    $query->where('id', $supplierId)
                        ->orWhere('boss_id', $supplierId);
                })
                ->whereNotNull('boss_id')
                ->whereNotNull('created_at')
                ->whereBetween('created_at', [
                    $filterStart->toDateTimeString(),
                    $filterEnd->toDateTimeString(),
                ])
                ->withTrashed()
                ->clientsCount()
                ->acceptedSmsCount($filterStart, $filterEnd)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $teamUserIds = $teamSuppliers
                ->pluck('user_id')
                ->filter()
                ->values()
                ->all();

            $billingCountsByUser = $this->getTeamDocumentCountsByUser(
                Billing::query()->where('supplier_id', $supplierId),
                $teamUserIds,
                $filterStart,
                $filterEnd,
            );
            $payoutCountsByUser = $this->getTeamDocumentCountsByUser(
                Payout::query()->where('supplier_id', $supplierId),
                $teamUserIds,
                $filterStart,
                $filterEnd,
            );
            $agreementCountsByUser = $this->getTeamDocumentCountsByUser(
                Agreement::query()->where('supplier_id', $supplierId),
                $teamUserIds,
                $filterStart,
                $filterEnd,
            );

            $orderedTeamMembers = $teamSuppliers->map(function ($teamSupplier) use (
                $billingCountsByUser,
                $payoutCountsByUser,
                $agreementCountsByUser,
                $supplierId,
            ) {
                $user = $teamSupplier->user;
                $userId = $teamSupplier->user_id;
                $invoices = (int) ($billingCountsByUser->get($userId, 0));
                $swish = (int) ($payoutCountsByUser->get($userId, 0));
                $agreements = (int) ($agreementCountsByUser->get($userId, 0));

                return [
                    'id' => $user?->id,
                    'supplier_id' => $teamSupplier->id,
                    'position' => $teamSupplier->position,
                    'is_boss' => $teamSupplier->id === $supplierId,
                    'name' => $user?->name,
                    'last_name' => $user?->last_name,
                    'email' => $user?->email,
                    'avatar' => $user?->avatar,
                    'user_detail' => $user?->userDetail,
                    'invoices' => $invoices,
                    'swish' => $swish,
                    'agreements' => $agreements,
                    'clients' => $teamSupplier?->client_count ?? 0,
                    'accepted_sms' => $teamSupplier?->sms_accepted_count ?? 0,
                    'total_actions' => $invoices + $swish + $agreements,
                    'created_at' => $teamSupplier?->created_at,
                ];
            })
                ->sortByDesc('total_actions')
                ->values()
                ->map(function ($teamMember) {
                    unset($teamMember['total_actions']);

                    return $teamMember;
                });

            $totalTeamMembers = $orderedTeamMembers->count();
            $teamMembers = $orderedTeamMembers;
            $lastPage = 1;

            if ($limit > 0) {
                $lastPage = max((int) ceil($totalTeamMembers / $limit), 1);
                $teamMembers = $orderedTeamMembers
                    ->forPage($page, $limit)
                    ->values();
            }

            $totalBillings = $this->getTeamDocumentTotalCount(
                Billing::query()->where('supplier_id', $supplierId),
                $filterStart,
                $filterEnd,
            );
            $totalPayouts = $this->getTeamDocumentTotalCount(
                Payout::query()->where('supplier_id', $supplierId),
                $filterStart,
                $filterEnd,
            );
            $totalAgreements = $this->getTeamDocumentTotalCount(
                Agreement::query()->where('supplier_id', $supplierId),
                $filterStart,
                $filterEnd,
            );

            $totalClients = $this->getTeamDocumentTotalCount(
                Client::query()->where('supplier_id', $supplierId),
                $filterStart,
                $filterEnd,
            );

            $totalVehiclesSold = $this->getTeamDocumentTotalCount(
                Vehicle::query()->where('supplier_id', $supplierId)->where('state_id', 12),
                $filterStart,
                $filterEnd,
            );

            $totalVehiclesStock = $this->getTeamDocumentTotalCount(
                Vehicle::query()->where('supplier_id', $supplierId)->where('state_id', "<>", 12),
                $filterStart,
                $filterEnd,
            );

            $totalSMS = $this->getTeamDocumentTotalCount(
                SmsMessage::query()->where('supplier_id', $supplierId)->where('billable_count', '>', 0),
                $filterStart,
                $filterEnd,
            );

            $totalNotes = $this->getTeamDocumentTotalCount(
                Note::query()->where('supplier_id', $supplierId),
                $filterStart,
                $filterEnd,
            );

            $totalDocuments = $this->getTeamDocumentTotalCount(
                Document::query()->where('supplier_id', $supplierId),
                $filterStart,
                $filterEnd,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'teamMembers' => $teamMembers,
                    'teamTotals' => [
                        'billings' => $totalBillings,
                        'payouts' => $totalPayouts,
                        'agreements' => $totalAgreements,
                        'clients' => $totalClients,
                        'vehicles_sold' => $totalVehiclesSold,
                        'vehicles_stock' => $totalVehiclesStock,
                        'sms' => $totalSMS,
                        'notes' => $totalNotes,
                        'documents' => $totalDocuments
                    ],
                    'pagination' => [
                        'total' => $totalTeamMembers,
                        'per_page' => $limit,
                        'current_page' => $limit > 0 ? $page : 1,
                        'last_page' => $lastPage,
                    ],
                    'dateRange' => [
                        'date_from' => $filterStart?->toDateString(),
                        'date_to' => $filterEnd?->toDateString(),
                    ],
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
     *
     * Store a newly created resource in storage.
     */
    public function addRelatedUser(UserRequest $request): JsonResponse
    {
        try{
            
            $order_id = Supplier::where('boss_id', Auth::user()->supplier->id)
                                ->max('order_id');

            $request->merge(['boss_id' => Auth::user()->supplier->id]);
            $request->merge(['order_id' => $order_id + 1]);
            $request->merge(['company' => ""]);
            $request->merge(['organization_number' => ""]);
            $request->merge(['address' => ""]);
            $request->merge(['phone' => ""]);
            $request->merge(['street' => ""]);
            $request->merge(['postal_code' => ""]);
            $request->merge(['bank' => ""]);
            $request->merge(['account_number' => ""]);

            $supplier = Supplier::createSupplier($request);

            $supplier->update([
                'position' => $request->position === 'null' ? null : $request->position
            ]);

            UserRegisterToken::updateOrCreate(
                ['user_id' => $supplier->user_id],
                ['token' => Str::random(60)]
            );

            $user = User::with(['userDetail', 'permissions'])->find($supplier->user_id);
            $user->syncPermissions($request->permissions);
            $user->givePermissionTo('view dashboard');
            $user->refresh()->load(['userDetail', 'permissions']);
            $supplier->refresh();

            $newValues = $this->mapRelatedSupplierUserActivityValues($user, $supplier);

            $logo = Auth::user()->userDetail ? Auth::user()->userDetail->logo_url : null;
            $email = $user->email;
            $subject = 'Välkommen till Billogg - ditt konto är skapat';
    
            $data = [
                'title' => 'Välkommen till Billogg',
                'user' => $user->name . ' ' . $user->last_name,
                'email'=> $email,
                'password' => $request->password,
                'buttonLink' => env('APP_DOMAIN'),
                'icon' => asset('/images/users.png'),
                'logo' => $logo
            ];
    
            // Send email asynchronously
            SendEmailJob::dispatch(
                'emails.auth.user_created',
                $data,
                $email,
                $subject
            ); 

            SupplierActivity::createActivity([
                'entity_id' => $supplier->id,
                'entity_type' => 'users',
                'action_type' => 'create_related_supplier_user',
                'title' => 'Användare '.$user->name.' '.$user->last_name.' tillagd till leverantörsteamet',
                'description' => 'En ny relaterad användare har lagts till.',
                'icon' => 'custom-supplier',
                'route' => '/dashboard/my-team?user_id='.$user->id,
                'metadata' => json_encode([
                    'supplier_id' => $supplier->id,
                    'new_values' => $newValues,
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user' => Supplier::with(['user.roles', 'user.userDetail'])->find($supplier->id)
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

    public function inactiveRelatedUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'feedback' => 'params_validation_failed',
                'message' => $validator->errors()
            ], 400);
        }

        try {
            $bossId = Auth::user()?->supplier?->id;

            if (!$bossId) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'inactive_user' => null
                    ]
                ], 200);
            }

            $inactiveSupplier = Supplier::withTrashed()
                ->with(['user' => function ($query) {
                    $query->withTrashed();
                }])
                ->where('boss_id', $bossId)
                ->whereNotNull('deleted_at')
                ->whereHas('user', function ($query) use ($request) {
                    $query->withTrashed()
                        ->where('email', $request->email)
                        ->whereNotNull('deleted_at');
                })
                ->first();

            if (!$inactiveSupplier || !$inactiveSupplier->user) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'inactive_user' => null
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'inactive_user' => [
                        'supplier_id' => $inactiveSupplier->id,
                        'user_id' => $inactiveSupplier->user->id,
                        'email' => $inactiveSupplier->user->email
                    ]
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
    public function deleteRelatedUser($id): JsonResponse
    {
        try {

            $user = User::with(['roles', 'userDetail', 'permissions'])->find($id);
        
            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found ' . $id,
                    'message' => 'Användaren hittades inte'
                ], 404);

            $supplier = Supplier::where('user_id', $user->id)->first();
        
            if (!$supplier)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Leverantören hittades inte ' . $user->id
                ], 404);

            $oldValues = $this->mapRelatedSupplierUserActivityValues($user, $supplier);

            SupplierActivity::createActivity([
                'entity_id' => $supplier->id,
                'entity_type' => 'users',
                'action_type' => 'delete_related_supplier_user',
                'title' => 'Användare '.$user->name.' '.$user->last_name.' borttagen från leverantörsteamet',
                'description' => 'En relaterad användare har avaktiverats.',
                'icon' => 'custom-supplier',
                'metadata' => json_encode([
                    'supplier_id' => $supplier->id,
                    'old_values' => $oldValues,
                ])
            ]);

            SupplierActivity::where('entity_id', $supplier->id)
                ->where('entity_type', 'users')
                ->update(['route' => null]);

            event(new ForceLogoutUserEvent($user->id));
            
            $supplier->deleteSupplier($supplier->id);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user' => $user
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
     * Update the specified resource in storage.
     */
    public function updateRelatedUser(Request $request, $id): JsonResponse
    {
        try {

            $user = User::with(['roles', 'userDetail', 'permissions'])->find($id);
        
            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Användaren hittades inte'
                ], 404);

            
            $request->merge(['roles' => [0 => "User"] ]);

            $supplier = Supplier::where('user_id', $user->id)->first();
            $oldValues = $this->mapRelatedSupplierUserActivityValues($user, $supplier);

            $user->updateUser($request, $user); 
            $user->syncPermissions($request->permissions);
            $user->givePermissionTo('view dashboard');

            $supplier = Supplier::where('user_id', $user->id)->first();

            $supplier->update([
                'position' => $request->position === 'null' ? null : $request->position
            ]);

            $user->refresh()->load(['permissions']);
            $user->loadMissing(['userDetail']);
            $supplier->refresh();

            $newValues = $this->mapRelatedSupplierUserActivityValues($user, $supplier);

            SupplierActivity::createActivity([
                'entity_id' => $supplier->id,
                'entity_type' => 'users',
                'action_type' => 'update_related_supplier_user',
                'title' => 'Användare '.$user->name.' '.$user->last_name.' uppdaterad i leverantörsteamet',
                'description' => 'En relaterad användare har uppdaterats.',
                'icon' => 'custom-supplier',
                'route' => '/dashboard/my-team?user_id='.$user->id,
                'metadata' => json_encode([
                    'supplier_id' => $supplier->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user' => $user
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
     * Update the specified resource in storage.
     */
    public function permissionsRelatedUser(Request $request, $id): JsonResponse
    {
        try {

            $user = User::with(['permissions', 'userDetail'])->find($id);
        
            if (!$user)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Användaren hittades inte'
                ], 404);

            $supplier = Supplier::where('user_id', $user->id)->first();
            $oldValues = $this->mapRelatedSupplierUserActivityValues($user, $supplier);

            $user->syncPermissions($request->permissions);
            $user->givePermissionTo('view dashboard');
            $user->refresh()->load(['permissions']);
            $user->loadMissing(['userDetail']);

            $newValues = $this->mapRelatedSupplierUserActivityValues($user, $supplier);

            SupplierActivity::createActivity([
                'entity_id' => $supplier?->id ?? $user->id,
                'entity_type' => 'users',
                'action_type' => 'update_related_supplier_user_permissions',
                'title' => 'Behörigheter uppdaterade för användare '.$user->name.' '.$user->last_name,
                'description' => 'Behörigheter för relaterad användare har uppdaterats.',
                'icon' => 'custom-supplier',
                'route' => '/dashboard/my-team',
                'metadata' => json_encode([
                    'supplier_id' => $supplier?->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'user' => $user
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

    private function getCurrentSupplierId(): int
    {
        $user = Auth::user();
        $role = $user->getRoleNames()[0] ?? null;
        $supplier = $user?->supplier;

        return match ($role) {
            'Supplier' => (int) ($supplier?->id ?? 0),
            'User' => (int) ($supplier?->boss_id ?? $supplier?->id ?? 0),
            default => (int) ($supplier?->id ?? 0),
        };
    }

    private function getTeamDocumentCountsByUser(
        $query,
        array $userIds,
        ?Carbon $filterStart = null,
        ?Carbon $filterEnd = null,
    )
    {
        if (empty($userIds)) {
            return collect();
        }

        $filteredQuery = clone $query;

        if ($filterStart && $filterEnd) {
            $filteredQuery = (clone $filteredQuery)
                ->whereNotNull('created_at')
                ->whereBetween('created_at', [
                    $filterStart->toDateTimeString(),
                    $filterEnd->toDateTimeString(),
                ]);
        }

        return (clone $filteredQuery)
            ->whereNotNull('user_id')
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');
    }

    private function getTeamDocumentTotalCount(
        $query,
        ?Carbon $filterStart = null,
        ?Carbon $filterEnd = null,
    ): int {
        $filteredQuery = clone $query;

        if ($filterStart && $filterEnd) {
            $filteredQuery = (clone $filteredQuery)
                ->whereNotNull('created_at')
                ->whereBetween('created_at', [
                    $filterStart->toDateTimeString(),
                    $filterEnd->toDateTimeString(),
                ]);
        }

        return (clone $filteredQuery)->count();
    }

    private function resolveDateFilters(Request $request): array
    {
        $hasDateFilter = $request->filled('date_from') || $request->filled('date_to');
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : null;

        if ($dateFrom && $dateTo && $dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [$hasDateFilter, $dateFrom, $dateTo];
    }

    private function mapSupplierActivityValues(Supplier $supplier, array $fields): array
    {
        $user = $supplier->user;
        $userDetail = $user?->userDetail;

        $values = [
            'name' => $user?->name,
            'last_name' => $user?->last_name,
            'email' => $user?->email,
            'company' => $userDetail?->company,
            'organization_number' => $userDetail?->organization_number,
            'link' => $userDetail?->link,
            'address' => $userDetail?->address,
            'street' => $userDetail?->street,
            'postal_code' => $userDetail?->postal_code,
            'phone' => $userDetail?->phone,
            'landline' => $userDetail?->landline,
            'swish' => $userDetail?->swish,
            'sms_sender' => $supplier->sms_sender,
            'bank' => $userDetail?->bank,
            'account_number' => $userDetail?->account_number,
            'user_id' => $supplier->user_id,
            'creator_id' => $supplier->creator_id,
            'boss_id' => $supplier->boss_id,
            'order_id' => $supplier->order_id,
        ];

        return collect($fields)
            ->unique()
            ->mapWithKeys(fn ($field) => [$field => $values[$field] ?? null])
            ->all();
    }

    private function mapRelatedSupplierUserActivityValues(User $user, ?Supplier $supplier): array
    {
        $userDetail = $user->userDetail;

        return [
            'name' => $user->name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'position' => $supplier?->position,
            'phone' => $userDetail?->personal_phone ?? $userDetail?->phone,
            'landline' => $userDetail?->personal_landline ?? $userDetail?->landline,
            'address' => $userDetail?->personal_address ?? $userDetail?->address,
            'permissions' => $user->permissions->pluck('name')->values()->all(),
        ];
    }

}
