<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class ClientRequest extends FormRequest
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
        $clientId = $this->route('client') ?? $this->route('id');
        $supplierId = $this->resolveSupplierId();

        $organizationNumberUniqueRule = Rule::unique('clients', 'organization_number')
            ->where(function ($query) use ($supplierId) {
                if ($supplierId === null) {
                    $query->whereNull('supplier_id');
                } else {
                    $query->where('supplier_id', $supplierId);
                }

                $query->whereNull('deleted_at');
            });

        if (!empty($clientId)) {
            $organizationNumberUniqueRule->ignore($clientId);
        }

        $rules = [
            'address' => [
                'required'
            ],
            'street' => [
                'required'
            ],
            'postal_code' => [
                'required'
            ],
            'phone' => [
                'required'
            ],
            'fullname' => [
                'required'
            ],
            'email' => [
                'required'
            ],
            'organization_number' => [
                'nullable',
                $organizationNumberUniqueRule
            ]
        ];


        return $rules;
    }

    public function messages()
    {
        return [
            'address.required' => 'Adressen är obligatorisk.',
            'street.required' => 'Gatan är obligatorisk.',
            'postal_code.required' => 'Postnumret är obligatoriskt.',
            'phone.required' => 'Telefonen är obligatorisk.',
            'fullname.required' => 'Namnet är obligatoriskt.',
            'email.required' => 'E-postadressen är obligatorisk.',
            'organization_number.unique' => 'Org/personnummer finns redan för vald leverantör.'
        ];
    }

    private function resolveSupplierId(): ?int
    {
        $supplierFromRequest = $this->input('supplier_id');

        if (Auth::check()) {
            $role = Auth::user()->getRoleNames()[0] ?? null;

            if ($role === 'Supplier' && ($supplierFromRequest === null || $supplierFromRequest === 'null' || $supplierFromRequest === '')) {
                return Auth::user()->supplier?->id;
            }

            if ($role === 'User') {
                return Auth::user()->supplier?->boss_id;
            }
        }

        if ($supplierFromRequest === null || $supplierFromRequest === 'null' || $supplierFromRequest === '') {
            return null;
        }

        return (int) $supplierFromRequest;
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
