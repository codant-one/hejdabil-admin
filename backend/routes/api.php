<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Testing\TestingController;

use App\Http\Controllers\Auth\{
    AuthController,
    PasswordResetController
};

use App\Http\Controllers\{
    UsersController,
    RoleController,
    PermissionController,
    UserMenuController,
    ProxyController,
    DashboardController,
    SupplierController,
    ClientController,
    TypeController,
    InvoiceController,
    BillingController,
    BodysCarController,
    BrandController,
    EquipmentsListController,
    GearboxController,
    IvaController,
    ModelController

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

//Private Endpoints
Route::group(['middleware' => ['cors','jwt'] ], function(){
     
    //Resources 
    Route::apiResource('users', UsersController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('permissions', PermissionController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('types', TypeController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('billings', BillingController::class);
    Route::apiResource('bodiescar', BodysCarController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('models', ModelController::class);
    Route::apiResource('equipments', EquipmentsListController::class);
    Route::apiResource('gearboxes', GearboxController::class);
    Route::apiResource('iva', IvaController::class);

    /* DASHBOARD */
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    //Users
    Route::group(['prefix' => 'users'], function () {
        Route::get('user/online', [UsersController::class, 'getOnline']);
        Route::post('update/password/{id}', [UsersController::class, 'updatePasswordUser']);
        Route::post('update/password', [UsersController::class, 'updatePassword']);
        Route::post('update/profile',  [UsersController::class, 'updateProfile']);
        Route::get('user/profile',  [UsersController::class, 'getProfile']);
        Route::post('update/supplier', [UsersController::class, 'updateSupplier']);
        Route::post('update/supplier/logo', [UsersController::class, 'updateLogo']);
    });

    //Roles
    Route::group(['prefix' => 'roles'], function () {
        Route::get('role/all', [RoleController::class, 'all']);
    });

    //Permissions
    Route::group(['prefix' => 'permissions'], function () {
        Route::get('permission/all', [PermissionController::class, 'all']);
    });

    //Menu
    Route::group(['prefix' => 'menu'], function () {
        Route::get('/', [UserMenuController::class, 'index']);
        Route::post('/add', [UserMenuController::class, 'store']);
        Route::post('/update', [UserMenuController::class, 'update']);
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
    });

});

//Public Endpoints
Route::get('reminder', [TestingController::class , 'reminder'])->name('reminder');
Route::get('emails', [TestingController::class , 'emails'])->name('emails');
Route::get('pdfs', [TestingController::class , 'pdfs'])->name('pdfs');

//PROXY
Route::get('/proxy-image',[ProxyController::class, 'getImage']);