<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgreementRequest;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

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
use App\Models\Guaranty;
use App\Models\GuarantyType;
use App\Models\InsuranceCompany;
use App\Models\InsuranceType;
use App\Models\Currency;
use App\Models\PaymentType;
use App\Models\Identification;
use App\Models\Advance;

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
                        'agreement_type',
                        'agreement_client',
                        'vehicle_interchange',
                        'vehicle_client.vehicle',
                        'supplier.user'
                    ])->applyFilters(
                        $request->only([
                            'search',
                            'orderByField',
                            'orderBy'
                        ])
                    );

            $count = $query->count();

            $agreements = ($limit == -1) ? $query->paginate($query->count()) : $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'agreements' => $agreements,
                    'agreementsTotalCount' => $count
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
                        'agreement_type',
                        'guaranty',
                        'guaranty_type',
                        'insurance_company',
                        'insurance_type',
                        'currency',
                        'iva',
                        'payment_types',
                        'vehicle_interchange.model.brand',
                        'vehicle_interchange.carbody',
                        'vehicle_interchange.iva_purchase',
                        'agreement_client',
                        'vehicle_client.vehicle.model.brand',
                        'vehicle_client.vehicle.fuel',
                        'vehicle_client.vehicle.gearbox',
                        'vehicle_client.vehicle.payment.payment_types',
                        'supplier.user'
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

    public function info() {

        try {

            if (Auth::user()->getRoleNames()[0] === 'Supplier') {
                $clients = Client::where('supplier_id', Auth::user()->supplier->id)->get();
            } else {
                $clients = Client::all();
            }

            $agreement_id = Agreement::whereNull('supplier_id')->count();
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
                    'guaranties'  => Guaranty::all(),
                    'guarantyTypes'  => GuarantyType::all(),
                    'insuranceCompanies'  => InsuranceCompany::all(),
                    'insuranceTypes'  => InsuranceType::all(),
                    'currencies'  => Currency::all(),
                    'paymentTypes'  => PaymentType::all(),
                    'agreementTypes' => AgreementType::all(),
                    'agreement_id' => $agreement_id,
                    'vehicles' => $vehicles,
                    'clients' => $clients,
                    'client_types' => ClientType::all(),
                    'identifications' => Identification::all(),
                    'advances' => Advance::all()
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

            $data = [
                'user' => $agreement->agreement_client->fullname,
                'text' => 'Vi hoppas att detta meddelande får dig att må bra. <br> Vänligen notera att vi har genererat en ny avtal i ditt namn med följande uppgifter:',
                'agreement' => $agreement,
                'text_info' => 'Bifogat finns avtalan i PDF-format. Du kan ladda ner och granska den när som helst. <br> Om du har några frågor eller behöver mer information, tveka inte att kontakta oss.',
                'buttonText' => 'Nedladdningar',
                'pdfFile' => asset('storage/'.$agreement->file)
            ];

            if($request->emailDefault === true) {
                $clientEmail = $agreement->agreement_client->email;
                $subject = 'Din avtal #'. $agreement->agreement_id . ' är tillgänglig';
                    
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
