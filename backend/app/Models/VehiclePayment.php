<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class VehiclePayment extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**** Relationship ****/
    public function vehicle(){
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function payment_types(){
        return $this->belongsTo(PaymentType::class, 'payment_type_id', 'id');
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
    public static function createVehiclePayment($request) {

        $vehicle_payment = self::create([
            'vehicle_id' => $request->vehicle_id === 'null' ? null : $request->vehicle_id,
            'payment_type_id' => $request->payment_type_id === 'null' ? null : $request->payment_type_id,
            'is_loan' => $request->is_loan === 'null' ? null : $request->is_loan,
            'loan_amount' => $request->loan_amount === 'null' ? null : $request->loan_amount,
            'lessor' => $request->lessor === 'null' ? null : $request->lessor,
            'remaining_amount' => $request->remaining_amount === 'null' ? null : $request->remaining_amount,
            'settled_by' => $request->settled_by === 'null' ? null : $request->settled_by,
            'bank' => $request->bank === 'null' ? null : $request->bank,            
            'account' => $request->account === 'null' ? null : $request->account,
            'description' => $request->description === 'null' ? null : $request->description,
            'payment_type' => $request->payment_type === 'null' ? null : $request->payment_type
        ]);

        return $vehicle_payment;
    }

    public static function updateVehiclePayment($request, $vehicle_payment) {
        $vehicle_payment->update([
            'payment_type_id' => $request->payment_type_id === 'null' ? null : $request->payment_type_id,
            'is_loan' => $request->is_loan === 'null' ? null : $request->is_loan,
            'loan_amount' => $request->loan_amount === 'null' ? null : $request->loan_amount,
            'lessor' => $request->lessor === 'null' ? null : $request->lessor,
            'remaining_amount' => $request->remaining_amount === 'null' ? null : $request->remaining_amount,
            'settled_by' => $request->settled_by === 'null' ? null : $request->settled_by,
            'bank' => $request->bank === 'null' ? null : $request->bank,            
            'account' => $request->account === 'null' ? null : $request->account,
            'description' => $request->description === 'null' ? null : $request->description,
            'payment_type' => $request->payment_type === 'null' ? null : $request->payment_type
        ]);

        return $vehicle_payment;
    }

    public static function deleteVehiclePayment($id) {
        self::deleteVehiclePayments(array($id));
    }

    public static function deleteVehiclePayments($ids) {
        foreach ($ids as $id) {
            $vehicle_payment = self::find($id);
            $vehicle_payment->delete();
        }
    }
    
}
