<?php

namespace App\Http\Requests;

use App\Models\HelpVideo;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class HelpVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'help_module_id' => 'required|integer|exists:help_modules,id',
            'state_id' => 'required|integer|in:1,2|exists:states,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url' => 'required|url:http,https|max:255',
            'provider' => 'required|in:youtube,vimeo,mp4',
            'thumbnail_url' => 'nullable|url:http,https|max:500',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->has('provider') && ! $validator->errors()->has('video_url')
                && HelpVideo::embedUrlFor($this->string('provider')->toString(), $this->string('video_url')->toString()) === null) {
                $validator->errors()->add('video_url', 'Videolänken måste matcha den valda leverantören.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute är obligatoriskt.',
            'exists' => 'Valt :attribute är ogiltigt.',
            'integer' => ':attribute måste vara ett heltal.',
            'string' => ':attribute måste vara text.',
            'max' => ':attribute får innehålla högst :max tecken.',
            'url' => ':attribute måste vara en giltig HTTP- eller HTTPS-länk.',
            'in' => 'Vald leverantör är ogiltig.',
            'state_id.in' => 'Status måste vara Aktiv eller Inaktiv.',
        ];
    }

    public function attributes(): array
    {
        return [
            'help_module_id' => 'modul', 'state_id' => 'status', 'title' => 'titel',
            'description' => 'beskrivning', 'video_url' => 'videolänk',
            'provider' => 'leverantör', 'thumbnail_url' => 'miniatyrbild',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'feedback' => 'params_validation_failed',
            'message' => implode(', ', $validator->errors()->all()),
            'errors' => $validator->errors(),
        ], 400));
    }
}
