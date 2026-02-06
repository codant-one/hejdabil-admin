<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class NotificationRequest extends FormRequest
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
            'title' => [
                'required',
                'string',
                'max:255'
            ],
            'subtitle' => [
                'nullable',
                'string',
                'max:255'
            ],
            'text' => [
                'required',
                'string',
                'max:1000'
            ],
            'color' => [
                'nullable',
                'string',
                'in:primary,success,info,warning,error'
            ],
            'icon' => [
                'nullable',
                'string',
                'max:100'
            ],
            'img' => [
                'nullable',
                'url',
                'max:500'
            ],
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'agreement_id' => [
                'nullable',
                'string',
                'max:100'
            ],
            'signed_by' => [
                'nullable',
                'string',
                'max:255'
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'Titeln är obligatorisk',
            'title.string' => 'Titeln måste vara en textsträng',
            'title.max' => 'Titeln får inte vara längre än 255 tecken',
            'subtitle.string' => 'Undertiteln måste vara en textsträng',
            'subtitle.max' => 'Undertiteln får inte vara längre än 255 tecken',
            'text.required' => 'Texten är obligatorisk',
            'text.string' => 'Texten måste vara en textsträng',
            'text.max' => 'Texten får inte vara längre än 1000 tecken',
            'color.string' => 'Färgen måste vara en textsträng',
            'color.in' => 'Färgen måste vara en av: primary, success, info, warning, error',
            'icon.string' => 'Ikonen måste vara en textsträng',
            'icon.max' => 'Ikonen får inte vara längre än 100 tecken',
            'img.url' => 'Bilden måste vara en giltig URL',
            'img.max' => 'Bildadressen får inte vara längre än 500 tecken',
            'user_id.integer' => 'Användar-ID måste vara ett heltal',
            'user_id.exists' => 'Den valda användaren finns inte',
            'agreement_id.string' => 'Avtals-ID måste vara en textsträng',
            'agreement_id.max' => 'Avtals-ID får inte vara längre än 100 tecken',
            'signed_by.string' => 'Undertecknad av måste vara en textsträng',
            'signed_by.max' => 'Undertecknad av får inte vara längre än 255 tecken'
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