<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC19 Post Comment: plain text, optionally about one submission of the
 * same application.
 */
class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('comment', $this->route('application')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'submission_id' => [
                'nullable',
                'integer',
                Rule::exists('submissions', 'id')->where('application_id', $this->route('application')->id),
            ],
            // A reply can only answer a comment in the same job's thread.
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('comments', 'id')->where('application_id', $this->route('application')->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'comment', 'submission_id' => 'submission', 'parent_id' => 'comment you replied to'];
    }
}
