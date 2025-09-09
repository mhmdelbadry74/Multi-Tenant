<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug|regex:/^[a-z0-9-]+$/',
            'db_name' => 'required|string|max:255|regex:/^[a-zA-Z0-9_]+$/',
            'db_user' => 'required|string|max:255|regex:/^[a-zA-Z0-9_]+$/',
            'db_pass' => 'required|string|min:8|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tenant name is required',
            'name.max' => 'Tenant name cannot exceed 255 characters',
            'slug.required' => 'Tenant slug is required',
            'slug.unique' => 'This slug is already taken',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens',
            'db_name.required' => 'Database name is required',
            'db_name.regex' => 'Database name can only contain letters, numbers, and underscores',
            'db_user.required' => 'Database user is required',
            'db_user.regex' => 'Database user can only contain letters, numbers, and underscores',
            'db_pass.required' => 'Database password is required',
            'db_pass.min' => 'Database password must be at least 8 characters',
        ];
    }
}
