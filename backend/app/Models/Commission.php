<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

use App\Models\CommissionClient;
use App\Models\CommissionVehicle;

class Commission extends Model
{
    use HasFactory;
   
    protected $guarded = [];

    /**** Relationship ****/
    public function vehicle() {       
        return $this->hasOne(CommissionVehicle::class, 'commission_id');
    }

    public function client() {       
        return $this->hasOne(CommissionClient::class, 'commission_id');
    }

    public function commission_type() {
        return $this->belongsTo(CommissionType::class, 'commission_type_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id')->withTrashed();
    }

    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('fullname', 'LIKE', '%' . $search . '%')
            ->orWhere('email', 'LIKE', '%' . $search . '%');
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
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
    public static function createCommission($request) {

        $isSupplier = Auth::check() && Auth::user()->getRoleNames()[0] === 'Supplier';
        $isUser = Auth::user()->getRoleNames()[0] === 'User';
        $supplier_id = match (true) {
            $isSupplier => Auth::user()->supplier->id,
            $isUser => Auth::user()->supplier->boss_id,
            $request->supplier_id === 'null' => null,
            default => $request->supplier_id,
        };

        $commission = self::create([
            'user_id' => Auth::user()->id,
            'supplier_id' => $supplier_id,
            'commission_type_id' => $request->commission_type_id === 'null' ? null : $request->commission_type_id,
            'commission_id' => $request->commissionId === 'null' ? null : $request->commissionId,
            'commission_fee' => $request->commission_fee === 'null' ? null : $request->commission_fee,
            'start_date' => $request->start_date === 'null' ? null : $request->start_date,
            'end_date' => $request->end_date === 'null' ? null : $request->end_date,
            'outstanding_debt' => $request->outstanding_debt === 'null' ? null : $request->outstanding_debt,
            'remaining_debt' => $request->remaining_debt === 'null' ? null : $request->remaining_debt,
            'residual_debt' => $request->residual_debt === 'null' ? null : $request->residual_debt,
            'paid_bank' => $request->paid_bank === 'null' ? null : $request->paid_bank,
            'selling_price' => $request->selling_price === 'null' ? null : $request->selling_price,
            'payment_days' => $request->payment_days === 'null' ? null : $request->payment_days,
            'payment_description' => $request->payment_description === 'null' ? null : $request->payment_description
            
        ]);

        $request->request->add([
            'commission_id' => $commission->id
        ]);

        CommissionClient::createClient($request);
        CommissionVehicle::createVehicle($request);

        return $commission;
    }

    public static function updateCommission($request, $commission) {

        $commission->update([
            'commission_type_id' => $request->commission_type_id === 'null' ? null : $request->commission_type_id,
            'commission_id' => $request->commissionId === 'null' ? null : $request->commissionId,
            'commission_fee' => $request->commission_fee === 'null' ? null : $request->commission_fee,
            'start_date' => $request->start_date === 'null' ? null : $request->start_date,
            'end_date' => $request->end_date === 'null' ? null : $request->end_date,
            'outstanding_debt' => $request->outstanding_debt === 'null' ? null : $request->outstanding_debt,
            'remaining_debt' => $request->remaining_debt === 'null' ? null : $request->remaining_debt,
            'residual_debt' => $request->residual_debt === 'null' ? null : $request->residual_debt,
            'paid_bank' => $request->paid_bank === 'null' ? null : $request->paid_bank,
            'selling_price' => $request->selling_price === 'null' ? null : $request->selling_price,
            'payment_days' => $request->payment_days === 'null' ? null : $request->payment_days,
            'payment_description' => $request->payment_description === 'null' ? null : $request->payment_description           
        ]);

        //Update Client.
        $client = CommissionClient::where('commission_id', $commission->id)->first();
        $client->updateClient($request, $client);
        
        //Update Vehicle.
        $vehicle = CommissionVehicle::where('commission_id', $commission->id)->first();
        $vehicle->updateVehicle($request, $vehicle);

        return $commission;
    }

    public static function deleteCommission($id) {
        self::deleteCommissions(array($id));
    }

    public static function deleteCommissions($ids) {
        foreach ($ids as $id) {
            $commission = self::find($id);
            $commission->delete();
        }
    }
}