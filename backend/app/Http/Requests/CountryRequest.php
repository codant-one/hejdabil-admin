<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\Country;

class CountryRequest extends FormRequest
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
            'name' => [
                'required'
            ],
            'iso' => [
                'required',
                Rule::unique('countries', 'iso')->ignore($this->country)
            ],
            'iso3' => [
                'required',
                Rule::unique('countries', 'iso3')->ignore($this->country)
            ],
            'numcode' => [
                'required',
                Rule::unique('countries', 'numcode')->ignore($this->country)
            ],
            'phonecode' => [
                'required',
                Rule::unique('countries', 'phonecode')->ignore($this->country)
            ],
            'phone_digits' => [
                'required'
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Namnet är obligatoriskt.',
            'iso.required' => 'ISO-kod är obligatorisk.',
            'iso.unique' => 'ISO-koden används redan.',
            'iso3.required' => 'ISO3-kod är obligatorisk.',
            'iso3.unique' => 'ISO3-koden används redan.',
            'numcode.required' => 'Numerisk landskod är obligatorisk.',
            'numcode.unique' => 'Den numeriska landskoden används redan.',
            'phonecode.required' => 'Telefonkod är obligatorisk.',
            'phonecode.unique' => 'Telefonkoden används redan.',
            'phone_digits.required' => 'Antal siffror för telefonnummer är obligatoriskt.'
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
