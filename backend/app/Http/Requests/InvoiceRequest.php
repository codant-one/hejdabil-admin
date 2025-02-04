<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\Invoice;

class InvoiceRequest extends FormRequest
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
            'type_id' => [
                'integer',
                'required',
                'exists:App\Models\Type,id'
            ],
            'name_en' => [
                'required'
            ],
            'name_se' => [
                'required'
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'type_id.required' => 'The type is required.',
            'type_id.integer' => 'The type format must be integer.',
            'type_id.exists' => 'The type entered does not exist.',
            'name_en.required' => 'The name in English is required.',
            'name_se.required' => 'The name in Swedish is required.'
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
