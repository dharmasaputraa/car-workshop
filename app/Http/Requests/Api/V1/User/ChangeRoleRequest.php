<?php

namespace App\Http\Requests\Api\V1\User;

use App\Enums\RoleType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $targetUserId = $this->route('user') ?? $this->route('id');
        $targetUser = \App\Models\User::findOrFail($targetUserId);

        return $this->user()->can('changeRole', $targetUser);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::enum(RoleType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'role.Illuminate\Validation\Rules\Enum' => 'The selected role is invalid. Available options: ' . implode(', ', array_column(RoleType::cases(), 'value')),
        ];
    }
}
