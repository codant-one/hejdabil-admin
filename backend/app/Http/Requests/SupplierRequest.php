<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\Supplier;

class SupplierRequest extends FormRequest
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
        $supplier = Supplier::find($this->supplier);

        $rules = [
            'company' => [
                'required'
            ],
            'organization_number' => [
                'required'
            ],
            'address' => [
                'required'
            ],
            'street' => [
                'required'
            ],
            'postal_code' => [
                'required'
            ],
            'phone' => [
                'required'
            ],
            'bank' => [
                'required'
            ],
            'account_number' => [
                'required'
            ],
            'name' => [
                'required'
            ],
            'last_name' => [
                'required'
            ],
            'email' => [
                'required_if:user_id,<>'.($supplier->user_id ?? -1),
                Rule::unique('users', 'email')->ignore($supplier->user_id ?? -1)
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'company.required' => 'Företaget är obligatoriskt.',
            'organization_number.required' => 'Organisationsnumret är obligatoriskt.',
            'address.required' => 'Adressen är obligatorisk.',
            'street.required' => 'Gatan är obligatorisk.',
            'postal_code.required' => 'Postnumret är obligatoriskt.',
            'phone.required' => 'Telefonen är obligatorisk.',
            'bank.required' => 'Bankens namn är obligatoriskt. ',
            'account_number.required' => 'Kontonumret är obligatoriskt.',
            'name.required' => 'Förnamnet är obligatoriskt.',
            'last_name.required' => 'Efternamnet är obligatoriskt.',
            'email.required' => 'E-postadressen är obligatorisk.',
            'email.email' => 'E-postformatet är inte tillåtet.',
            'email.unique' => 'En användare med den angivna e-postadressen finns redan.'
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
