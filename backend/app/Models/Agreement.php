<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    use HasFactory;

    /**** Relationship ****/
    public function agreementType(){
        return $this->belongsTo(AgreementType::class, 'agreement_type_id', 'id');
    }

    public function vehicle(){
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function guaranty(){
        return $this->belongsTo(Guaranty::class, 'guaranty_id', 'id');
    }

    public function guarantyType(){
        return $this->belongsTo(GuarantyType::class, 'guaranty_type_id', 'id');
    }

    public function insuranceCompany(){
        return $this->belongsTo(InsuranceCompany::class, 'insurance_company_id', 'id');
    }

    public function insuranceType(){
        return $this->belongsTo(InsuranceType::class, 'insurance_type_id', 'id');
    }

    public function currency(){
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }

    public function iva(){
        return $this->belongsTo(Iva::class, 'iva_id', 'id');
    }

    public function paymentType(){
        return $this->belongsTo(PaymentType::class, 'user_id', 'id');
    }

    public function vehicleInterchange(){
        return $this->belongsTo(VehicleInterchange::class, 'vehicle_interchange_id', 'id');
    }

    public function client(){
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function agreement_client(){
        return $this->belongsTo(AgreementClient::class, 'agreement_client_id', 'id');
    }


    /**** Scopes ****/
    public function scopeWhereSearch($query, $search) {
        $query->where('id', $search);
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy) {
        $query->orderByRaw('(IFNULL('. $orderByField .', id)) '. $orderBy);
    }

    public function scopeApplyFilters($query, array $filters) {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('guaranty_id') === '0') {
            $query->where('guaranty_id',  $filters->get('guaranty_id'));
        }

        if ($filters->get('guaranty_type_id') !== null) {
            $query->where('guaranty_type_id', $filters->get('guaranty_type_id'));
        }

        if ($filters->get('insurance_company_id') !== null) {
            $query->where('insurance_company_id', $filters->get('insurance_company_id'));
        }

        if ($filters->get('insurance_type_id') !== null) {
            $query->where('insurance_type_id', $filters->get('insurance_type_id'));
        }

        if ($filters->get('currency_id') !== null) {
            $query->where('currency_id', $filters->get('currency_id'));
        }

        if ($filters->get('payment_type_id') !== null) {
            $query->where('payment_type_id', $filters->get('payment_type_id'));
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
    public static function createAgreement($request) {

        $agreement = self::create([
            'supplier_id' => Auth::user()->supplier->id,
            'agreement_type_id' => $request->agreement_type_id,
            'vehicle_id' => $request->vehicle_id === 'null' ? null : $request->vehicle_id,
            'guaranty_id' => $request->guaranty_id === 'null' ? null : $request->guaranty_id,
            'guaranty_type_id' => $request->guaranty_type_id === 'null' ? null : $request->guaranty_type_id,
            'insurance_company_id' => $request->insurance_company_id === 'null' ? null : $request->insurance_company_id,
            'insurance_type_id' => $request->insurance_type_id === 'null' ? null : $request->insurance_type_id,
            'insurance_agent' => $request->insurance_agent === 'null' ? null : $request->insurance_agent,
            'price' => $request->price === 'null' ? null : $request->price,
            'currency_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'iva_id' => $request->iva_id === 'null' ? null : $request->iva_id,
            'iva_amount' => $request->iva_amount === 'null' ? null : $request->iva_amount,
            'registration_fee' => $request->registration_fee === 'null' ? null : $request->registration_fee,
            'payment_type_id' => $request->payment_type_id === 'null' ? null : $request->payment_type_id,
            'down_payment_percentage' => $request->down_payment_percentage === 'null' ? null : $request->down_payment_percentage,
            'payment_received' => $request->payment_received === 'null' ? null : $request->payment_received,
            'payment_method_forcash' => $request->payment_method_forcash === 'null' ? null : $request->payment_method_forcash,
            'installment_amount' => $request->installment_amount === 'null' ? null : $request->installment_amount,
            'installment_contract_upon_delivery' => $request->installment_contract_upon_delivery === 'null' ? null : $request->installment_contract_upon_delivery,
            'payment_description' => $request->payment_description === 'null' ? null : $request->payment_description,
            'vehicle_interchange_id' => $request->vehicle_interchange_id === 'null' ? null : $request->vehicle_interchange_id,
            'client_id' => $request->client_id === 'null' ? null : $request->client_id,
            'agreement_client_id' => $request->agreement_client_id === 'null' ? null : $request->agreement_client_id
        ]);
        
        return $agreement;
    }

    public static function updateAgreement($request, $agreement) {

        $agreement->update([
            'vehicle_id' => $request->vehicle_id === 'null' ? null : $request->vehicle_id,
            'guaranty_id' => $request->guaranty_id === 'null' ? null : $request->guaranty_id,
            'guaranty_type_id' => $request->guaranty_type_id === 'null' ? null : $request->guaranty_type_id,
            'insurance_company_id' => $request->insurance_company_id === 'null' ? null : $request->insurance_company_id,
            'insurance_type_id' => $request->insurance_type_id === 'null' ? null : $request->insurance_type_id,
            'insurance_agent' => $request->insurance_agent === 'null' ? null : $request->insurance_agent,
            'price' => $request->price === 'null' ? null : $request->price,
            'currency_id' => $request->currency_id === 'null' ? null : $request->currency_id,
            'iva_id' => $request->iva_id === 'null' ? null : $request->iva_id,
            'iva_amount' => $request->iva_amount === 'null' ? null : $request->iva_amount,
            'registration_fee' => $request->registration_fee === 'null' ? null : $request->registration_fee,
            'payment_type_id' => $request->payment_type_id === 'null' ? null : $request->payment_type_id,
            'down_payment_percentage' => $request->down_payment_percentage === 'null' ? null : $request->down_payment_percentage,
            'payment_received' => $request->payment_received === 'null' ? null : $request->payment_received,
            'payment_method_forcash' => $request->payment_method_forcash === 'null' ? null : $request->payment_method_forcash,
            'installment_amount' => $request->installment_amount === 'null' ? null : $request->installment_amount,
            'installment_contract_upon_delivery' => $request->installment_contract_upon_delivery === 'null' ? null : $request->installment_contract_upon_delivery,
            'payment_description' => $request->payment_description === 'null' ? null : $request->payment_description,
            'vehicle_interchange_id' => $request->vehicle_interchange_id === 'null' ? null : $request->vehicle_interchange_id,
            'client_id' => $request->client_id === 'null' ? null : $request->client_id,
            'agreement_client_id' => $request->agreement_client_id === 'null' ? null : $request->agreement_client_id

        ]);

        return $agreement;
    }

    public static function deleteAgreement($id) {
        self::deleteVehicles(array($id));
    }

    public static function deleteAgreements($ids) {
        foreach ($ids as $id) {
            $vehicle = self::find($id);
            $vehicle->delete();
        }
    }

    
    
    
    
    
    
    
    
    
    
    
}
