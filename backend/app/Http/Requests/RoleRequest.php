<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                 Rule::unique('roles', 'name')->ignore($this->role)
            ],
            'permissions' => [
                'required',
                'array',
                'exists:Spatie\Permission\Models\Permission,name'
            ]
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Namnet är obligatoriskt.',
            'permissions.required' => 'Behörigheterna är obligatoriska.',
            'permissions.array' => 'Behörighetsformatet är inte tillåtet.',
            'permissions.exists' => 'Den angivna behörigheten finns inte.'
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
