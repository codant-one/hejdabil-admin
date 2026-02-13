<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\Vehicle;

class VehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isSupplier = auth()->check() && auth()->user()->getRoleNames()[0] === 'Supplier';
        $isUser = auth()->check() && auth()->user()->getRoleNames()[0] === 'User';

        $supplierId = match (true) {
            $isSupplier => auth()->user()->supplier->id,
            $isUser => auth()->user()->supplier->boss_id,
            $this->supplier_id === 'null' || $this->supplier_id === null => null,
            default => $this->supplier_id,
        };

        $rules = [
            'reg_num' => [
                'required',
                Rule::unique('vehicles')->where(function ($query) use ($supplierId) {
                    if ($supplierId === null) {
                        return $query->whereNull('supplier_id');
                    }

                    return $query->where('supplier_id', $supplierId);
                }),
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'reg_num.required' => 'Reg nr är obligatoriskt',
            'reg_num.unique' => 'Fordonsnumret är redan registrerat',
        ];
    }

    /**
    * Get the error messages for the defined validation rules.*
    * @return array
    */
    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'feedback' => 'params_validation_failed',
            'message' => implode (', ', $validator->errors()->all())
        ], 400));
    }

}