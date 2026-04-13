<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $targetUserId = $this->route('user') ?? $this->route('id');
        $targetUser = \App\Models\User::findOrFail($targetUserId);

        return $this->user()->can('update', $targetUser);
    }

    protected function prepareForValidation(): void
    {
        $mergeData = [];

        if ($this->has('avatar-url')) {
            $mergeData['avatar_url'] = $this->input('avatar-url');
        }

        if ($this->has('is-active')) {
            $mergeData['is_active'] = $this->input('is-active');
        }

        if ($this->has('role-name')) {
            $mergeData['role_name'] = $this->input('role-name');
        }

        if (!empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? $user->id : $user;

        return [
            'name'       => ['sometimes', 'required', 'string', 'max:255'],
            'email'      => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password'   => ['sometimes', 'required', 'string', 'min:8'],
            'is_active'  => ['sometimes', 'boolean'],
            'avatar_url' => ['sometimes', 'nullable', 'url'],
            'role_name'  => ['sometimes', 'required', 'string'],
        ];
    }
}
