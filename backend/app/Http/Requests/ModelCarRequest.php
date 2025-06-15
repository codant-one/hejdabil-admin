<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\CarModel;

class CarModelRequest extends FormRequest
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
            'brand_id' => [
                'integer',
                'required',
                'exists:App\Models\Brand,id'
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
            'brand_id.required' => 'Märke är obligatorisk.',
            'brand_id.integer' => 'Märke formatet måste vara integer.',
            'brand_id.exists' => 'Den angivna Märket finns inte.',
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
