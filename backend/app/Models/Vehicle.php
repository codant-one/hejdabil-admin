<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Models\CarModel;
use App\Models\Client;
use App\Models\VehicleClient;
use App\Models\Config;
use App\Models\UserDetails;
use App\Models\VehicleTask;
use App\Models\VehicleDocument;
use App\Models\TaskComment;

use Carbon\Carbon;
use PDF;

class Vehicle extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /**** Relationship ****/
    public function carbody(){
        return $this->belongsTo(CarBody::class, 'car_body_id', 'id');
    }  

    public function model(){
        return $this->belongsTo(CarModel::class, 'model_id', 'id');
    }

    public function gearbox(){
        return $this->belongsTo(Gearbox::class, 'gearbox_id', 'id');
    }

    public function iva_purchase(){
        return $this->belongsTo(Iva::class, 'iva_purchase_id', 'id');
    }

    public function iva_sale(){
        return $this->belongsTo(Iva::class, 'iva_sale_id', 'id');
    }

    public function currency_purchase(){
        return $this->belongsTo(Currency::class, 'currency_purchase_id', 'id');
    }

    public function currency_sale(){
        return $this->belongsTo(Currency::class, 'currency_sale_id', 'id');
    }

    public function fuel(){
        return $this->belongsTo(Fuel::class, 'fuel_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function state(){
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function tasks(){
        return $this->hasMany(VehicleTask::class, 'vehicle_id', 'id');
    }

    public function documents(){
        return $this->hasMany(VehicleDocument::class, 'vehicle_id', 'id');
    }

    public function client_sale(){
        return $this->hasOne(VehicleClient::class, 'vehicle_id', 'id')->where('type', 1);
    }

    public function client_purchase(){
        return $this->hasOne(VehicleClient::class, 'vehicle_id', 'id')->where('type', 2);
    }

    public function vehicle_interchange(){
        return $this->hasOne(Agreement::class, 'vehicle_interchange_id', 'id');
    }

    public function payment(){
        return $this->hasOne(VehiclePayment::class, 'vehicle_id', 'id');
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->whereHas('model.brand', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhereHas('model', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhereHas('user', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('name', 'LIKE', '%' . $search . '%')
                         ->orWhere('last_name', 'LIKE', '%' . $search . '%')
                         ->orWhere('email', 'LIKE', '%' . $search . '%')
                         ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ['%' . $search . '%']);
                });
            })
            ->orWhereHas('client_sale', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('fullname', 'LIKE', '%' . $search . '%')
                         ->orWhere('phone', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhereHas('client_purchase', function ($uq) use ($search) {
                $uq->where(function ($inner) use ($search) {
                    $inner->where('fullname', 'LIKE', '%' . $search . '%')
                         ->orWhere('phone', 'LIKE', '%' . $search . '%');
                });
            })
            ->orWhere('reg_num', 'LIKE', '%' . $search . '%')
            ->orWhere('color', 'LIKE', '%' . $search . '%')
            ->orWhere('year', 'LIKE', '%' . $search . '%');
        });
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $fields = explode(',', $orderByField);

        foreach ($fields as $field) {
            $query->orderByRaw('(IFNULL(' . trim($field) . ', id)) ' . $orderBy);
        }
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('supplier_id') !== null) {
            $query->where('supplier_id', $filters->get('supplier_id'));
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier') {
            $query->where('supplier_id', Auth::user()->supplier->id);
        } else if(Auth::check() && Auth::user()->getRoleNames()[0] === 'User') {
            $query->where('supplier_id', Auth::user()->supplier->boss_id);
        }

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('isSold') === '0') {
            $query->where('state_id', '<>', 12);
        }

        if ($filters->get('state_id') !== null) {
            $query->where('state_id', $filters->get('state_id'));
        }

        if ($filters->get('brand_id') !== null) {
            $query->whereHas('model.brand', function ($query) use ($filters) {
                $query->where('id', $filters->get('brand_id'));
            });
        }

        if ($filters->get('model_id') !== null) {
            $query->where('model_id', $filters->get('model_id'));
        }

        if ($filters->get('year') !== null) {
            $query->where('year', $filters->get('year'));
        }

        if ($filters->get('gearbox_id') !== null) {
            $query->where('gearbox_id', $filters->get('gearbox_id'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'order_id';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }
    }

    public function scopePaginateData($query, $limit) {
        if ($limit == 'all') {
            return collect(['data' => $query->get()]);
        }

        return $query->paginate($limit);
    }

    /**** Public methods ****/
    public static function createVehicle($request) {

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';
        $isUser = Auth::user()->getRoleNames()[0] === 'User';
        $supplier_id = match (true) {
            $isSupplier => Auth::user()->supplier->id,
            $isUser => Auth::user()->supplier->boss_id,
            $request->supplier_id === 'null' => null,
            default => $request->supplier_id,
        };

        // Si model_id es 0, crear nuevo modelo
        $model_id = null;
        if ($request->model_id === '0' && $request->model && $request->brand_id) {
            $model = CarModel::create([
                'name' => $request->model,
                'brand_id' => $request->brand_id
            ]);
            $model_id = $model->id;
        } elseif ($request->model_id && $request->model_id !== 'null' && $request->model_id !== '0') {
            $model_id = $request->model_id;
        }

        $vehicle = self::create([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
            'reg_num' => $request->reg_num,
            'chassis' => $request->chassis === 'null' ? null : $request->chassis,
            'year' => $request->year === 'null' ? null : $request->year,
            'generation' => $request->generation === 'null' ? null : $request->generation,
            'model_id' => $model_id,
            'car_body_id' => ($request->car_body_id && $request->car_body_id !== 'null') ? $request->car_body_id : null,
            'fuel_id' => ($request->fuel_id && $request->fuel_id !== 'null') ? $request->fuel_id : null,
            'gearbox_id' => ($request->gearbox_id && $request->gearbox_id !== 'null') ? $request->gearbox_id : null,
            'color' => $request->color === 'null' ? null : $request->color,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'control_inspection' => $request->control_inspection === 'null' ? null : $request->control_inspection,
            'purchase_date' => $request->purchase_date === 'null' ? now()->format('Y-m-d') : $request->purchase_date
        ]);
        
        $vehicle = self::with(['user', 'model.brand', 'state', 'iva_purchase'])->find($vehicle->id);
        $name = $vehicle->reg_num;

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

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        PDF::loadView('pdfs.vehicle', compact('vehicle', 'company'))->save(storage_path('app/public/pdfs').'/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf');

        $vehicle->file = 'pdfs/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf';
        $vehicle->update();

        //Se registran las tareas y documentos asociados al vehículo
        $allTasks = json_decode($request->tasks);
        if ($allTasks && is_array($allTasks)) {
            foreach ($allTasks as $task) {
                $vehicleTask = VehicleTask::create([
                    'vehicle_id' => $vehicle->id,
                    'user_id' => Auth::user()->id,
                    'measure' => $task->measure ?? '',
                    'description' => $task->description ?? null,
                    'is_cost' => $task->is_cost ?? 0,
                    'cost' => $task->cost ?? 0,
                    'start_date' => $task->start_date ?? null,
                    'end_date' => $task->end_date ?? null,
                ]);

                if ($task->comments && is_array($task->comments)) {
                    foreach ($task->comments as $comment) {
                        TaskComment::create([
                            'vehicle_task_id' => $vehicleTask->id,
                            'user_id' => Auth::user()->id,
                            'comment' => $comment->comment ?? '',
                        ]);
                    }

                }
            }
        }

        if ($request->documents && is_array($request->documents)) {
            foreach ($request->documents as $document) {
                $vehicleDocument = VehicleDocument::create([
                    'vehicle_id' => $vehicle->id,
                    'user_id' => Auth::user()->id,
                    'document_type_id' => $document['document_type_id'] ?? '',
                    'reference' => $document['reference'] ?? null
                ]);

                if ($document['file']) {
                    $doc = VehicleDocument::with(['user'])->find($vehicleDocument->id);
                    $name = $doc->user->name . ' ' . $doc->user->last_name;
                    $image = $document['file'];

                    $path = 'vehicles/' . Str::slug($name) . '/';

                    $file_data = uploadFileWithOriginalName($image, $path);

                    $doc->file = $file_data['filePath'];
                    $doc->update();
                }
            }
        }



        return $vehicle;
    }

    public static function updateVehicle($request, $vehicle) {

        if($request->model_id === '0') {//other model
            $model = CarModel::create([
                'name' => $request->model,
                'brand_id' => $request->brand_id
            ]);

            $model_id = $model->id;
        } else 
            $model_id = $request->model_id === 'null' ? null : $request->model_id;

        $vehicle->update([
            'reg_num' => $request->reg_num,
            'model_id' => $model_id,
            'car_body_id' => $request->car_body_id === 'null' ? null : $request->car_body_id,
            'gearbox_id' => $request->gearbox_id === 'null' ? null : $request->gearbox_id,
            'iva_purchase_id' => $request->iva_purchase_id === 'null' ? null : $request->iva_purchase_id,
            'currency_purchase_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'currency_sale_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'fuel_id' => $request->fuel_id === 'null' ? null : $request->fuel_id,
            'state_id' => $request->state_id === 'null' ? 10 : $request->state_id,
            'mileage' => $request->mileage === 'null' ? null : $request->mileage,
            'generation' => $request->generation === 'null' ? null : $request->generation,
            'year' => $request->year === 'null' ? null : $request->year,
            'control_inspection' => $request->control_inspection === 'null' ? null : $request->control_inspection,
            'color' => $request->color === 'null' ? null : $request->color,
            'purchase_price' => $request->purchase_price === 'null' ? null : $request->purchase_price,
            'purchase_date' => $request->purchase_date === 'null' ? null : $request->purchase_date,
            'number_keys' => $request->number_keys === 'null' ? null : $request->number_keys,
            'service_book' => ( $request->service_book === 'null' || empty($request->service_book) ) ? 0 : $request->service_book,
            'summer_tire' => ( $request->summer_tire === 'null' || empty($request->summer_tire) ) ? 0 : $request->summer_tire,
            'winter_tire' => ( $request->winter_tire === 'null' || empty($request->winter_tire) ) ? 0 : $request->winter_tire,
            'last_service' => $request->last_service === 'null' ? null : $request->last_service,
            'last_service_date' => $request->last_service_date === 'null' ? null : $request->last_service_date,
            'dist_belt' => ( $request->dist_belt === 'null' || empty($request->dist_belt) ) ? 0 : $request->dist_belt,
            'last_dist_belt' => $request->last_dist_belt === 'null' ? null : $request->last_dist_belt,
            'last_dist_belt_date' => $request->last_dist_belt_date === 'null' ? null : $request->last_dist_belt_date,
            'comments' => $request->comments === 'null' ? null : $request->comments,
            'chassis' => $request->chassis === 'null' ? null : $request->chassis,
            'sale_price' => $request->sale_price === 'null' ? null : $request->sale_price,
            'sale_date' => $request->sale_date === 'null' ? null : $request->sale_date,
            'iva_sale_id' => $request->iva_sale_id === 'null' ? null : $request->iva_sale_id,
            'sale_comments' => $request->sale_comments === 'null' ? null : $request->sale_comments,
            'iva_sale_amount' => $request->iva_sale_amount === 'null' ? null : $request->iva_sale_amount,
            'iva_sale_exclusive' => $request->iva_sale_exclusive === 'null' ? null : $request->iva_sale_exclusive,
            'iva_purchase_amount' => $request->iva_purchase_amount === 'null' ? null : $request->iva_purchase_amount,
            'iva_purchase_exclusive' => $request->iva_purchase_exclusive === 'null' ? null : $request->iva_purchase_exclusive,
            'total_sale' => $request->total_sale === 'null' ? null : $request->total_sale,
            'discount' => $request->discount === 'null' ? null : $request->discount,
            'registration_fee' => $request->registration_fee === 'null' ? null : $request->registration_fee
        ]);

        $vehicle = self::with(['supplier.user', 'user', 'model.brand', 'state', 'iva_purchase',  'client_purchase', 'client_sale'])->find($vehicle->id);
        $name = $vehicle->reg_num;

        if(!$vehicle->client_purchase && $request->type === '2') {
            $request->request->add([
                'vehicle_id' => $vehicle->id
            ]);
            
            VehicleClient::createClient($request);
        } else if($vehicle->client_purchase && $request->type === '2') {
            VehicleClient::updateClient($request, $vehicle->client_purchase);     
        }

         if($vehicle->supplier_id === null) {
            //Admin
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
        } else {
            $user = UserDetails::with(['user'])->find($vehicle->supplier->user_id);
            $company = $user->user->userDetail;
            $company->email = $user->user->email;
        }

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        PDF::loadView('pdfs.vehicle', compact('vehicle', 'company'))->save(storage_path('app/public/pdfs').'/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf');

        $vehicle->file = 'pdfs/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf';
        $vehicle->update();

        return $vehicle;
    }

    public static function sendVehicle($request, $vehicle) {

        $vehicle->update([
            'state_id' => 12,           
            'chassis' => $request->chassis === 'null' ? null : $request->chassis,
            'sale_price' => $request->sale_price === 'null' ? null : $request->sale_price,
            'sale_date' => $request->sale_date === 'null' ? null : $request->sale_date,
            'iva_sale_id' => $request->iva_sale_id === 'null' ? null : $request->iva_sale_id,
            'sale_comments' => $request->sale_comments === 'null' ? null : $request->sale_comments,
            'iva_sale_amount' => $request->iva_sale_amount === 'null' ? null : $request->iva_sale_amount,
            'iva_sale_exclusive' => $request->iva_sale_exclusive === 'null' ? null : $request->iva_sale_exclusive,
            'iva_purchase_amount' => $request->iva_purchase_amount === 'null' ? null : $request->iva_purchase_amount,
            'iva_purchase_exclusive' => $request->iva_purchase_exclusive === 'null' ? null : $request->iva_purchase_exclusive,
            'total_sale' => $request->total_sale === 'null' ? null : $request->total_sale,
            'discount' => $request->discount === 'null' ? null : $request->discount,
            'registration_fee' => $request->registration_fee === 'null' ? null : $request->registration_fee
        ]);

        $vehicle = self::with(['supplier.user', 'user', 'model.brand', 'state', 'iva_purchase'])->find($vehicle->id);
        $name = $vehicle->reg_num;

        if($vehicle->supplier_id === null) {
            //Admin
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
        } else {
            $user = UserDetails::with(['user'])->find($vehicle->supplier->user_id);
            $company = $user->user->userDetail;
            $company->email = $user->user->email;
        }

        if (!file_exists(storage_path('app/public/pdfs'))) {
            mkdir(storage_path('app/public/pdfs'), 0755,true);
        } //create a folder

        PDF::loadView('pdfs.vehicle', compact('vehicle', 'company'))->save(storage_path('app/public/pdfs').'/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf');

        $vehicle->file = 'pdfs/'.Str::slug($name).'-sammanställning-'.$vehicle->id.'.pdf';
        $vehicle->update();

        if($request->save_client === 'true') {
            $request->supplier_id = 'null';
            $client = Client::createClient($request);
            $order_id = Client::where('supplier_id', $client->supplier_id)
                            ->withTrashed()
                            ->latest('order_id')
                            ->first()
                            ->order_id ?? 0;

            $client->order_id = $order_id + 1;
            $client->update();
        }

        if ($request->has("client_id"))
            $request->merge([
                "client_id" => $request->save_client === 'true' ? $client->id : ($request->client_id === 'null' ? null : $request->client_id)
            ]);
        else
            $request->request->add([
                'client_id' => $request->save_client === 'true' ? $client->id : ($request->client_id === 'null' ? null : $request->client_id)
            ]);
        
        if ($request->has("vehicle_id"))
            $request->merge([
                "vehicle_id" => $vehicle->id
            ]);
        else
            $request->request->add([
                'vehicle_id' => $vehicle->id
            ]);

        VehicleClient::createClient($request);

        return $vehicle;
    }

    public static function deleteVehicle($id) {
        self::deleteVehicles(array($id));
    }

    public static function deleteVehicles($ids) {
        foreach ($ids as $id) {
            $vehicle = self::find($id);
            $vehicle->delete();

            if($vehicle->file)
                deleteFile($vehicle->file);
        }
    }

}
