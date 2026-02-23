<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

use App\Http\Controllers\Testing\TestingController;

use App\Http\Controllers\Auth\{
    AuthController,
    PasswordResetController
};

use App\Http\Controllers\{
    UsersController,
    RoleController,
    PermissionController,
    ProxyController,
    DashboardController,
    SupplierController,
    ClientController,
    TypeController,
    InvoiceController,
    BillingController,
    CarBodyController,
    BrandController,
    EquipmentController,
    GearboxController,
    IvaController,
    ModelController,
    VehicleController,
    VehicleTaskController,
    VehicleDocumentController,
    NoteController,
    AgreementController,
    CurrencyController,
    SignatureController,
    ConfigController,
    DocumentController,
    PayoutController,
    NotificationController,
    CountryController,
};

use App\Http\Controllers\Services\{
    CarInfoController,
    SwishPayoutController,
    CompanyInfoController,
    PersonInfoController,
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group([
    'prefix' => 'auth',
    'middleware' => 'cors'
], function () {
    Route::post('login', [AuthController::class , 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register']);
    Route::post('sendInfo', [AuthController::class, 'sendInfo']);
    Route::get('find/{token}', [AuthController::class, 'find'])->name('find');
    Route::post('completed', [AuthController::class, 'completed'])->name('completed');
    Route::post('forgot-password', [PasswordResetController::class, 'forgot_password'])->name('forgot.password');
    Route::get('password/find/{token}', [PasswordResetController::class, 'find'])->name("find");
    Route::post('change', [PasswordResetController::class, 'change'])->name("change");

    Route::middleware('jwt')->group(function () {
        Route::post('2fa/validate', [AuthController::class, 'validate_double_factor_auth'])->name('2fa.validate');
        Route::post('logout', [AuthController::class , 'logout'])->name('logout');
        Route::post('me', [AuthController::class , 'me'])->name('me');
        Route::get('generateQR', [AuthController::class , 'generateQR'])->name('generateQR');
        Route::get('company', [AuthController::class , 'companyDetail'])->name('companyDetail');
    });
});

// Broadcasting Authentication (para canales privados de WebSocket)
Broadcast::routes(['middleware' => ['cors', 'jwt']]);

//Private Endpoints
Route::group(['middleware' => ['cors','jwt','throttle:crm_limit']], function(){
     
    //Resources 
    Route::apiResource('users', UsersController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('types', TypeController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('billings', BillingController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('models', ModelController::class);
    Route::apiResource('ivas', IvaController::class);
    Route::apiResource('car-bodies', CarBodyController::class);
    Route::apiResource('gearboxes', GearboxController::class);
    Route::apiResource('equipments', EquipmentController::class);
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('tasks', VehicleTaskController::class);
    Route::apiResource('documents', VehicleDocumentController::class);
    Route::apiResource('notes', NoteController::class);
    Route::apiResource('agreements', AgreementController::class);
    Route::apiResource('currencies', CurrencyController::class);
    Route::apiResource('payouts', PayoutController::class);
    Route::apiResource('countries', CountryController::class);

    /* DASHBOARD */
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    /* NOTIFICATIONS */
    Route::group(['prefix' => 'notifications'], function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    });

    //Users
    Route::group(['prefix' => 'users'], function () {
        Route::get('user/online', [UsersController::class, 'getOnline']);
        Route::post('update/password/{id}', [UsersController::class, 'updatePasswordUser']);
        Route::post('update/password', [UsersController::class, 'updatePassword']);
        Route::post('update/profile',  [UsersController::class, 'updateProfile']);
        Route::get('user/profile',  [UsersController::class, 'getProfile']);
        Route::post('update/company', [UsersController::class, 'updateCompany']);
        Route::post('update/company/logo', [UsersController::class, 'updateLogo']);
        Route::post('update/company/signature', [UsersController::class, 'updateSignature']);
        Route::get('/activate/{id}', [UsersController::class, 'activate']);
    });

    //Roles
    Route::group(['prefix' => 'roles'], function () {
        Route::get('role/all', [RoleController::class, 'all']);
    });

    //Permissions
    Route::group(['prefix' => 'permissions'], function () {
        Route::get('permission/all', [PermissionController::class, 'all']);
    });

    //Billing
    Route::group(['prefix' => 'billings'], function () {
        Route::get('/updateState/{id}', [BillingController::class, 'updateState']);
        Route::post('/sendMails/{id}', [BillingController::class, 'sendMails']);
        Route::get('/data/all', [BillingController::class, 'all']);
        Route::get('/credit/{id}', [BillingController::class, 'credit']);
        Route::get('/reminder/{id}', [BillingController::class, 'reminder']);
        Route::get('/info/all', [BillingController::class, 'info']);
    });

    //Suppliers
    Route::group(['prefix' => 'suppliers'], function () {
        Route::get('/activate/{id}', [SupplierController::class, 'activate']);
        Route::get('supplier/users', [SupplierController::class, 'users']);
        Route::post('supplier/inactive-user', [SupplierController::class, 'inactiveRelatedUser']);
        Route::post('supplier/adduser', [SupplierController::class, 'addRelatedUser']);
        Route::get('supplier/deleteuser/{id}', [SupplierController::class, 'deleteRelatedUser']);
        Route::post('supplier/updateuser/{id}', [SupplierController::class, 'updateRelatedUser']);
        Route::post('supplier/permissions/{id}', [SupplierController::class, 'permissionsRelatedUser']);
        Route::post('/swish/{id}', [SupplierController::class, 'swish']);
        Route::post('/master-password/{id}', [SupplierController::class, 'masterPassword']);
        Route::get('/master-password/{id}', [SupplierController::class, 'getMasterPassword']);
    });

    //Clients
    Route::group(['prefix' => 'clients'], function () {
        Route::get('/activate/{id}', [ClientController::class, 'activate']);
        Route::get('/pending-items/{id}', [ClientController::class, 'pendingItems']);
    });

    //Tasks
    Route::group(['prefix' => 'tasks'], function () {
        Route::get('type/{id}', [VehicleTaskController::class, 'updateType']);
        Route::post('comment', [VehicleTaskController::class, 'comment']);
        Route::put('comment/{id}', [VehicleTaskController::class, 'updateComment']);
        Route::delete('comment/{id}', [VehicleTaskController::class, 'deleteComment']);
    });

    //Notes
    Route::group(['prefix' => 'notes'], function () {
        Route::post('comment', [NoteController::class, 'comment']);
        Route::put('comment/{id}', [NoteController::class, 'updateComment']);
        Route::delete('comment/{id}', [NoteController::class, 'deleteComment']);
    });

    //Documents (Vehicle Documents)
    Route::group(['prefix' => 'documents'], function () {
        Route::post('send', [VehicleDocumentController::class, 'send']);
    });

    //Signable Documents (New module for signing documents)
    Route::group(['prefix' => 'signable-documents'], function () {
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::post('/send', [DocumentController::class, 'send']);
        Route::get('/{document}', [DocumentController::class, 'show']);
        Route::post('/{document}', [DocumentController::class, 'update']);
        Route::delete('/{document}', [DocumentController::class, 'destroy']);
        Route::get('/{document}/get-admin-preview-pdf', [DocumentController::class, 'getAdminPreviewPdf'])->name('documents.getAdminPreviewPdf');
        Route::post('/{document}/send-signature-request', [DocumentController::class, 'sendSignatureRequest'])->name('documents.sendSignatureRequest');
        Route::post('/{document}/resend-signature-request', [DocumentController::class, 'resendSignatureRequest'])->name('documents.resendSignatureRequest');
    });

    //Vehicles
    Route::group(['prefix' => 'vehicles'], function () {
        Route::post('send', [VehicleController::class, 'send']);
        Route::post('findByRegNum', [VehicleController::class, 'findByRegNum']);
    });

    //Currencies
    Route::group(['prefix' => 'currencies'], function () {
        Route::get('/updateState/{id}', [CurrencyController::class, 'updateState']);
    });

    //Countries
    Route::group(['prefix' => 'countries'], function () {
        Route::get('/updateState/{id}', [CountryController::class, 'updateState']);
    });

    //Agreement
    Route::group(['prefix' => 'agreements'], function () {
        Route::get('/info/all', [AgreementController::class, 'info']);
        Route::post('/sendMails/{id}', [AgreementController::class, 'sendMails']);
        Route::get('/{agreement}/get-admin-preview-pdf', [AgreementController::class, 'getAdminPreviewPdf'])->name('agreements.getAdminPreviewPdf');
        Route::post('/{agreement}/send-signature-request', [SignatureController::class, 'sendSignatureRequest'])->name('agreements.sendSignatureRequest');
        Route::post('/{agreement}/send-static-signature-request', [SignatureController::class, 'sendStaticSignatureRequest']);
        Route::get('/{agreement}/get-admin-preview-pdf', [SignatureController::class, 'getAdminPreviewPdf'])->name('agreements.getAdminPreviewPdf');
    });

    //Billing
    Route::group(['prefix' => 'payouts'], function () {
        Route::get('/info/all', [PayoutController::class, 'info']);
        Route::post('/{payout}/cancel', [PayoutController::class, 'cancel'])->name('payouts.cancel');
        Route::post('/{payout}/save-receipt-image', [PayoutController::class, 'saveReceiptImage'])->name('payouts.saveReceiptImage');
        Route::post('/send', [PayoutController::class, 'send']);
    });

    //Configs
    Route::get('featured/{slug}', [ConfigController::class, 'featured']);
    Route::post('featured/{slug}', [ConfigController::class, 'featured_update']);
    Route::post('featured/{slug}/logo', [ConfigController::class, 'featured_logo_update']);
    Route::post('featured/{slug}/signature', [ConfigController::class, 'featured_signature_update']); 
});

//Public Endpoints
Route::get('reminder', [TestingController::class , 'reminder'])->name('reminder');
Route::get('emails', [TestingController::class , 'emails'])->name('emails');
Route::get('pdfs', [TestingController::class , 'pdfs'])->name('pdfs');
Route::get('documents', [TestingController::class , 'documents'])->name('documents');
Route::get('vehicle', [TestingController::class , 'vehicle'])->name('vehicle');
Route::get('agreement', [TestingController::class , 'agreement'])->name('agreement');

// Public Signature Endpoints
Route::group(['prefix' => 'signatures', 'middleware' => ['cors']], function () {
    Route::post('/submit/{token}', [SignatureController::class, 'storeSignature'])->name('signatures.store');
    Route::get('/{token}/get-unsigned-pdf', [SignatureController::class, 'getUnsignedPdf'])->name('signatures.getUnsignedPdf');
    Route::get('/{token}/details', [SignatureController::class, 'getSignatureDetails'])->name('signatures.details');
    Route::get('/{token}/status', [SignatureController::class, 'getTokenStatus'])->name('signatures.status');
    Route::post('/{token}/log-view', [SignatureController::class, 'logView'])->name('signatures.logView');
    Route::get('/{token}/get-signed-pdf', [SignatureController::class, 'getSignedPdf'])->name('signatures.getSignedPdf');
});

//Notifications
Route::group(['prefix' => 'notifications', 'middleware' => ['cors']], function () {
    Route::post('/send', [NotificationController::class, 'send'])->name('notifications.send');
});

//PROXY
Route::get('/proxy-image',[ProxyController::class, 'getImage']);

//CAR INFO
Route::get('/cars/lookup/{licensePlate}', [CarInfoController::class, 'lookupByLicensePlate']);
Route::get('/cars/lookup-vin/{vin}', [CarInfoController::class, 'lookupByVin']);

//Swish Payout
Route::post('/swish/payout/callback', [SwishPayoutController::class, 'handle']);

// COMPANY INFO (Bolagsverket)
Route::get('/companies/lookup/{orgNumber}', [CompanyInfoController::class, 'lookupByOrgNumber']);

// PERSON INFO (SPAR - Statens Personadressregister)
Route::get('/persons/lookup/{personId}', [PersonInfoController::class, 'lookupByPersonId']);