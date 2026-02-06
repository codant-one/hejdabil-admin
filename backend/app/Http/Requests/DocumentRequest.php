<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\Document;

class DocumentRequest extends FormRequest
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
                'required'
            ],
            'file' => [
                'required','file','mimes:pdf','max:10240'
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'Titel är obligatoriskt',
            'file.required' => 'Fil är obligatoriskt',
            'file.file' => 'Den uppladdade filen måste vara en giltig fil',
            'file.mimes' => 'Filen måste vara en PDF',
            'file.max' => 'Filen får inte vara större än 10 MB'
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
