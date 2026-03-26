<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReminderRequest extends FormRequest
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
            'description' => [
                'required',
                'string'
            ],
            'start_date' => [
                'required',
                'date'
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date'
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'description.required' => 'Beskrivning är obligatorisk',
            'description.string' => 'Beskrivning måste vara en text',
            'start_date.required' => 'Startdatum är obligatoriskt',
            'start_date.date' => 'Startdatum måste vara ett giltigt datum',
            'end_date.required' => 'Slutdatum är obligatoriskt',
            'end_date.date' => 'Slutdatum måste vara ett giltigt datum',
            'end_date.after_or_equal' => 'Slutdatum måste vara samma datum som eller senare än startdatum'
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
