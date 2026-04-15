<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'avatar_url' => $this->input('avatar-url'),
            'is_active' => $this->input('is-active', false),
            'role_name' => $this->input('role-name', 'user'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8'],
            'role'      => ['nullable', 'string', 'exists:roles,name'],
            'is_active' => ['nullable', 'boolean'],
            'avatar'    => ['nullable', 'image', 'max:2048'], // Maksimal 2MB
        ];
    }
}
