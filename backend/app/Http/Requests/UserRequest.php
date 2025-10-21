<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Support\Facades\Log;

class UserRequest extends FormRequest
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
                'required'
            ],
            'last_name' => [
                'required'
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->user)
            ],
            'password' => [
                ($this->user) ? 'nullable' : 'required'
            ]      
        ];

        $rules['roles'] = 'required|array|exists:Spatie\Permission\Models\Role,name';
    }

    public function messages()
    {
        return [
            'name.required' => 'Namnet är obligatoriskt.',
            'last_name.required' => 'Efternamnet är obligatoriskt.',
            'email.required' => 'E-postadressen är obligatorisk.',
            'email.email' => 'E-postformatet är inte tillåtet.',
            'email.unique' => 'En användare med den angivna e-postadressen finns redan.',
            'password.required' => 'Lösenordet är obligatoriskt.',
            'roles.required' => 'Rollen är obligatorisk.',
            'roles.array' => 'Rollformatet är inte tillåtet.',
            'roles.exists' => 'Den angivna rollen finns inte.'
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
