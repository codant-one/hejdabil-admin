<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\Client;

class ClientRequest extends FormRequest
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
        $rules = [
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
            'fullname' => [
                'required'
            ],
            'email' => [
                'required'
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'address.required' => 'The address is required.',
            'street.required' => 'The street is required.',
            'postal_code.required' => 'The postal code is required.',
            'phone.required' => 'The phone is required.',
            'fullname.required' => 'The name is required.',
            'email.required' => 'The email is required.'
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
