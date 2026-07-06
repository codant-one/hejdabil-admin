<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\Models\Plan;

class PlanRequest extends FormRequest
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
        $plan = Plan::find($this->plan);

        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            // 'state_id' => 'required|exists:states,id',
            'price_month' => 'required|numeric',
            'price_annual' => 'required|numeric',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => 'Namnet är obligatoriskt.',
            'description.required' => 'Beskrivningen är obligatorisk.',
            'state_id.required' => 'Status är obligatorisk.',
            // 'state_id.exists' => 'Den valda statusen är ogiltig.',
            'price_month.required' => 'Månadpriset är obligatoriskt.',
            'price_month.numeric' => 'Månadpriset måste vara ett nummer.',
            'price_annual.required' => 'Årspriset är obligatoriskt.',
            'price_annual.numeric' => 'Årspriset måste vara ett nummer.'
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
