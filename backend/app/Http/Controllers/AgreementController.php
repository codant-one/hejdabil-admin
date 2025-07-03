<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use Spatie\Permission\Middlewares\PermissionMiddleware;

use App\Models\Agreement;
use App\Models\Vehicle;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\CarBody;
use App\Models\Gearbox;
use App\Models\Iva;
use App\Models\Fuel;
use App\Models\DocumentType;
use App\Models\State;
use App\Models\Client;
use App\Models\AgreementClient;
use App\Models\Guaranty;
use App\Models\GuarantyType;
use App\Models\InsuranceCompany;
use App\Models\InsuranceType;
use App\Models\Currency;
use App\Models\PaymentType;

class AgreementController extends Controller
{
    public function __construct()
    {
        $this->middleware(PermissionMiddleware::class . ':view stock|administrator')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':create stock|administrator')->only(['store']);
        $this->middleware(PermissionMiddleware::class . ':edit stock|administrator')->only(['update']);
        $this->middleware(PermissionMiddleware::class . ':delete stock|administrator')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $limit = $request->has('limit') ? $request->limit : 10;

            $query = Agreement::with([
                        'vehicle.model.brand', 
                        'vehicle.state', 
                        'vehicle.iva',
                        'vehicle.costs',
                        'vehicle.carbody',
                        'vehicle.gearbox',
                        'vehicle.fuel',
                        'agreementType',
                        'vehicle_id',
                        'guaranty',
                        'guarantyType',
                        'insuranceCompany',
                        'insuranceType',
                        'insuranceAgent',
                        'currency',
                        'iva',
                        'paymentType',
                        'vehicleInterchange',
                        'client',
                        'supplier.user'
                    ])->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'guaranty_id',
                            'guaranty_type_id',
                            'insurance_company_id',
                            'insurance_type_id',
                            'currency_id',
                            'payment_type_id'
                        ])
                    );

            $count = $query->count();

            $vehicles = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'agreements' => $agreement,
                    'agreementsTotalCount' => $count,
                    'brands' => Brand::all(),
                    'models' => CarModel::with(['brand'])->get(),
                    'gearboxes' => Gearbox::all(),
                    'carbodies'  => CarBody::all(),
                    'ivas'  => Iva::all(),
                    'fuels'  => Fuel::all(),
                    'states'  => State::all(),
                    'guaranties'  => Guaranty::all(),
                    'guarantytypes'  => GuarantyType::all(),
                    'insuranceCompanies'  => InsuranceCompany::all(),
                    'insuranceTypes'  => InsuranceType::all(),
                    'currencies'  => Currency::all(),
                    'paymenttypes'  => PaymentType::all()
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

            //FIXME: Arreglar guardar Client, Vehicle, Vehicle_Interchange.
            //Preguntar que documentos se guardan y en donde?

            $client = ($request->saveClient == 1) ? Client::createClient($request) : Client::find($request->client_id);
            
            if (!$request->has("client_id") || $request->client_id <= 0){
                $request->request->add(['reason_occupation'=>null]);
            }

            //************************************************************* */

            $agreement = Agreement::createAgreement($request);

            return response()->json([
                'success' => true,
                'data' => [ 
                    'agreement' => Agreement::find($agreement->id)
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

            $agreement = Agreement::with([
                        'vehicle.model.brand', 
                        'vehicle.state', 
                        'vehicle.iva',
                        'vehicle.costs',
                        'vehicle.carbody',
                        'vehicle.gearbox',
                        'vehicle.fuel',
                        'agreementType',
                        'vehicle_id',
                        'guaranty',
                        'guarantyType',
                        'insuranceCompany',
                        'insuranceType',
                        'insuranceAgent',
                        'currency',
                        'iva',
                        'paymentType',
                        'vehicleInterchange',
                        'client',
                        'supplier.user'
                    ])->find($id);

            if (!$agreement)
                return response()->json([
                    'sucess' => false,
                    'feedback' => 'not_found',
                    'message' => 'Avtalet hittades inte'
                ], 404);

            if (Auth::user()->getRoleNames()[0] === 'Supplier') {
                $clients = Client::where('supplier_id', Auth::user()->supplier->id)->get();
            } else {
                $clients = Client::all();
            }

            return response()->json([
                'success' => true,
                'data' => [ 
                    'agreement' => $agreement,
                    'brands' => Brand::all(),
                    'models' => CarModel::with(['brand'])->get(),
                    'gearboxes' => Gearbox::all(),
                    'carbodies'  => CarBody::all(),
                    'ivas'  => Iva::all(),
                    'fuels'  => Fuel::all(),
                    'states'  => State::all(),
                    'guaranties'  => Guaranty::all(),
                    'guarantytypes'  => GuarantyType::all(),
                    'insuranceCompanies'  => InsuranceCompany::all(),
                    'insuranceTypes'  => InsuranceType::all(),
                    'currencies'  => Currency::all(),
                    'paymenttypes'  => PaymentType::all(),
                    'clients' => $clients
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
     public function update(AgreementRequest $request, $id): JsonResponse
    {
        try {
            $agreement = Agreement::find($id);
        
            if (!$agreement)
                return response()->json([
                    'success' => false,
                    'feedback' => 'not_found',
                    'message' => 'Avtalet hittades inte'
                ], 404);

            $agreement->updateAgreement($request, $agreement); 

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
}
