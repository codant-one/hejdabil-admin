<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HelpFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'module' => 'nullable|integer|min:1',
            'state_id' => 'nullable|integer|in:1,2|exists:states,id',
            'sort' => 'nullable|in:newest,oldest,title_asc,title_desc',
            'orderByField' => 'nullable|in:id,title,created_at',
            'orderBy' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|in:-1,6,9,10,12,20,30,50,100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
