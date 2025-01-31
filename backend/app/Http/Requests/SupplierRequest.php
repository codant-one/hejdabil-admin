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
            'company.required' => 'The company is required.',
            'organization_number.required' => 'The organization number is required.',
            'address.required' => 'The address is required.',
            'street.required' => 'The street is required.',
            'postal_code.required' => 'The postal code is required.',
            'phone.required' => 'The phone is required.',
            'bank.required' => 'The bank name is required.',
            'account_number.required' => 'The account number is required.',
            'name.required' => 'The first name is required.',
            'last_name.required' => 'The last name is required.',
            'email.required' => 'The email is required.',
            'email.email' => 'The email format is not allowed.',
            'email.unique' => 'A user with the entered email already exists.'
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
            'message' => $validator->errors()
        ], 400));
    }

}
