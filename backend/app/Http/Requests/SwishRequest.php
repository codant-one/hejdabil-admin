<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class SwishRequest extends FormRequest
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
            'payee_alias' => [
                'required'
            ],
            'payer_alias' => [
                'required'
            ],
            'payee_ssn'   => [
                'required', 
                'digits:12'
            ],
            'amount'      => [
                'required', 
                'numeric', 
                'min:1',
                'regex:/^\d+(\.\d{1,2})?$/'
            ],
            'master_password' => [
                'required',
                'string'
            ]
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'payee_alias.required' => 'Mottagarens Swish-nummer är obligatoriskt',
            'payer_alias.required' => 'Säljarens Swish-nummer är obligatoriskt',
            'payee_ssn.required'   => 'Mottagarens personnummer är obligatoriskt',
            'payee_ssn.digits'     => 'Mottagarens personnummer måste vara 12 siffror (ÅÅÅÅMMDDXXXX)',
            'amount.required'      => 'Belopp är obligatoriskt', 
            'amount.numeric'       => 'Belopp måste vara ett numeriskt värde',
            'amount.min'           => 'Belopp måste vara minst 1',
            'amount.regex'         => 'Belopp måste använda punkt (.) som decimaltecken med maximalt 2 decimaler',
            'master_password.required' => 'Säkerhetslösenord är obligatoriskt',
            'master_password.string'   => 'Säkerhetslösenord måste vara en text'
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