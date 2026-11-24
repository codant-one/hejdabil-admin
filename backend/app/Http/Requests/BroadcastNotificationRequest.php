<?php

namespace App\Http\Requests;

use App\Models\AdminNotification;
use App\Services\BroadcastNotificationService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BroadcastNotificationRequest extends FormRequest
{
    public const PREVIEW_ROUTE = 'admin.notifications-broadcast.preview-count';

    /** Internal path: starts with a single "/", no scheme, no leading "//", no whitespace or "\". */
    public const INTERNAL_ROUTE_REGEX = '/^\/(?![\/\\\\])[^\s\\\\]*$/';

    public function authorize(): bool
    {
        return true; // authorization is handled by the controller's role/permission middleware
    }

    public function rules(): array
    {
        $rules = [
            'audience' => ['required', Rule::in([
                AdminNotification::AUDIENCE_ALL,
                AdminNotification::AUDIENCE_ROLES,
                AdminNotification::AUDIENCE_USERS,
            ])],
            'user_ids' => ['exclude_unless:audience,' . AdminNotification::AUDIENCE_USERS, 'required', 'array', 'min:1'],
            'user_ids.*' => [
                'exclude_unless:audience,' . AdminNotification::AUDIENCE_USERS,
                'integer',
                'distinct',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'roles' => ['exclude_unless:audience,' . AdminNotification::AUDIENCE_ROLES, 'required', 'array', 'min:1'],
            'roles.*' => [
                'exclude_unless:audience,' . AdminNotification::AUDIENCE_ROLES,
                'string',
                'distinct',
                Rule::exists('roles', 'name')
                    ->where('guard_name', 'api')
                    ->whereNotIn('name', BroadcastNotificationService::EXCLUDED_ROLES),
            ],
        ];

        if ($this->routeIs(self::PREVIEW_ROUTE)) {
            return $rules;
        }

        return $rules + [
            'title' => ['required', 'string', 'max:65'],
            'subtitle' => ['required', 'string', 'max:178'],
            'route' => ['nullable', 'string', 'max:255', 'regex:' . self::INTERNAL_ROUTE_REGEX],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Titeln är obligatorisk.',
            'title.string' => 'Titeln måste vara en text.',
            'title.max' => 'Titeln får inte vara längre än :max tecken.',
            'subtitle.required' => 'Underrubriken är obligatorisk.',
            'subtitle.string' => 'Underrubriken måste vara en text.',
            'subtitle.max' => 'Underrubriken får inte vara längre än :max tecken.',
            'route.string' => 'Länken måste vara en text.',
            'route.max' => 'Länken får inte vara längre än :max tecken.',
            'route.regex' => 'Länken måste vara en intern sökväg som börjar med "/" (externa adresser är inte tillåtna).',
            'audience.required' => 'Mottagare är obligatoriskt.',
            'audience.in' => 'Ogiltigt val av mottagare.',
            'roles.required' => 'Välj minst en roll.',
            'roles.array' => 'Rollformatet är inte tillåtet.',
            'roles.min' => 'Välj minst en roll.',
            'roles.*.string' => 'Rollformatet är inte tillåtet.',
            'roles.*.distinct' => 'Samma roll har valts flera gånger.',
            'roles.*.exists' => 'Den angivna rollen finns inte.',
            'user_ids.required' => 'Välj minst en användare.',
            'user_ids.array' => 'Användarformatet är inte tillåtet.',
            'user_ids.min' => 'Välj minst en användare.',
            'user_ids.*.integer' => 'Användarformatet är inte tillåtet.',
            'user_ids.*.distinct' => 'Samma användare har valts flera gånger.',
            'user_ids.*.exists' => 'Den valda användaren finns inte eller är inaktiv.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'feedback' => 'params_validation_failed',
            'message' => $validator->errors(),
        ], 400));
    }
}
