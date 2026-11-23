<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UC14 request changes: the comment explains what to change.
 */
class RequestChangesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('respond', $this->route('submission')) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:2000'],
        ];
    }
}
