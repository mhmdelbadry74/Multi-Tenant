<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class CreateDealRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'contact_id' => 'required|exists:contacts,id',
            'assigned_to' => 'required|exists:users,id',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:open,won,lost',
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
            'title.required' => 'Deal title is required',
            'title.max' => 'Deal title cannot exceed 255 characters',
            'amount.required' => 'Deal amount is required',
            'amount.numeric' => 'Deal amount must be a valid number',
            'amount.min' => 'Deal amount must be greater than or equal to 0',
            'contact_id.required' => 'Contact is required',
            'contact_id.exists' => 'Selected contact does not exist',
            'assigned_to.required' => 'Assigned user is required',
            'assigned_to.exists' => 'Selected user does not exist',
            'description.max' => 'Description cannot exceed 1000 characters',
            'status.in' => 'Status must be open, won, or lost',
        ];
    }
}
