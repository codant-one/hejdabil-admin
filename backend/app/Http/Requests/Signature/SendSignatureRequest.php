<?php

namespace App\Http\Requests\Signature;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class SendSignatureRequest extends FormRequest
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
            'email' => ['required','email'],
            'x' => ['required','numeric'],
            'y' => ['required','numeric'],
            'page' => ['required','integer'],
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'email.required' => 'E-postadress är obligatorisk',
            'email.email' => 'E-postadressen måste vara giltig',
            'x.required' => 'Position X är obligatorisk',
            'x.numeric' => 'Position X måste vara ett tal',
            'y.required' => 'Position Y är obligatorisk',
            'y.numeric' => 'Position Y måste vara ett tal',
            'page.required' => 'Sidan är obligatorisk',                        
            'page.integer' => 'Sidan måste vara ett heltal'
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