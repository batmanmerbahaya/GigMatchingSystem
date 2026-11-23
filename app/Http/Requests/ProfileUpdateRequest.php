<?php

namespace App\Http\Requests;

use App\Enums\EntityType;
use App\Models\User;
use App\Rules\MalaysianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC16 Manage Profile. Gig Workers edit a bio; Contract Entities edit
 * their entity type and organisation name. The role cannot change.
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone_number' => ['required', 'string', 'max:20', new MalaysianPhone],
        ];

        if ($this->user()->isGigWorker()) {
            $rules['bio'] = ['nullable', 'string', 'max:1000'];
        } else {
            $rules['entity_type'] = ['required', Rule::enum(EntityType::class)];
            $rules['organisation_name'] = ['nullable', 'required_if:entity_type,business', 'string', 'max:150'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organisation_name.required_if' => 'Enter the organisation name for a business account.',
        ];
    }
}
