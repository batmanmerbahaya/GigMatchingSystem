<?php

namespace App\Http\Requests;

use App\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC08 Report Listing: a reason, and a note that is required for "other".
 */
class ReportListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('report', $this->route('job')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'note' => ['nullable', 'required_if:reason,other', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required_if' => 'Please describe the problem when the reason is Other.',
        ];
    }
}
