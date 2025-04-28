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
            'name' => [
                'required'
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'type_id.required' => 'Typen är obligatorisk.',
            'type_id.integer' => 'Typformatet måste vara integer.',
            'type_id.exists' => 'Den angivna typen finns inte.',
            'name.required' => 'namnet är obligatoriskt'
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
