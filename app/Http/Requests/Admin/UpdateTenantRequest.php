<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
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
        $tenantId = $this->route('tenant')->id ?? null;
        
        return [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:tenants,slug,' . $tenantId . '|regex:/^[a-z0-9-]+$/',
            'db_name' => 'sometimes|string|max:255|regex:/^[a-zA-Z0-9_]+$/',
            'db_user' => 'sometimes|string|max:255|regex:/^[a-zA-Z0-9_]+$/',
            'db_pass' => 'sometimes|string|min:8|max:255',
            'status' => 'sometimes|in:active,suspended',
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
            'name.max' => 'Tenant name cannot exceed 255 characters',
            'slug.unique' => 'This slug is already taken',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens',
            'db_name.regex' => 'Database name can only contain letters, numbers, and underscores',
            'db_user.regex' => 'Database user can only contain letters, numbers, and underscores',
            'db_pass.min' => 'Database password must be at least 8 characters',
            'status.in' => 'Status must be either active or suspended',
        ];
    }
}
