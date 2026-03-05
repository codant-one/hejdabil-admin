<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupplierSwishRequest extends FormRequest
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
        $supplierId = $this->route('id')
            ?? $this->route('supplier')
            ?? $this->input('id')
            ?? $this->input('supplier_id');

        $rules = [
            'is_payout' => [
                'required'
            ],
            'payout_number' => [
                'required',
                Rule::unique('suppliers', 'payout_number')->ignore($supplierId, 'id')
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'is_payout.required' => 'Fältet "is_payout" är obligatoriskt.',
            'payout_number.required' => 'Swish-kontonumret är obligatoriskt.',
            'payout_number.unique' => 'Swish-kontonumret används redan.'
        ];
    }

    /**
    * Get the error messages for the defined validation rules.*
    * @return array
    */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'feedback' => 'params_validation_failed',
            'message' => implode (', ', $validator->errors()->all())
        ], 400));
    }

}
