<?php

namespace App\Http\Requests\Auth;

use App\Enums\EntityType;
use App\Enums\UserRole;
use App\Models\User;
use App\Rules\MalaysianPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

/**
 * One role per account, chosen here. There is no admin role, so the role
 * is limited to Gig Worker or Contract Entity.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(UserRole::values())],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone_number' => ['required', 'string', 'max:20', new MalaysianPhone],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'entity_type' => ['exclude_unless:role,contract_entity', 'required', Rule::enum(EntityType::class)],
            'organisation_name' => [
                'exclude_unless:role,contract_entity', 'nullable', 'required_if:entity_type,business', 'string', 'max:150',
            ],
        ];
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
