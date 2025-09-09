<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
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
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,csv',
            'type' => 'sometimes|string|in:avatar,document,attachment',
            'description' => 'nullable|string|max:500',
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
            'file.required' => 'File is required',
            'file.file' => 'Uploaded item must be a valid file',
            'file.max' => 'File size cannot exceed 10MB',
            'file.mimes' => 'File must be one of: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx, txt, csv',
            'type.in' => 'File type must be avatar, document, or attachment',
            'description.max' => 'Description cannot exceed 500 characters',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'uploaded file',
            'type' => 'file type',
            'description' => 'file description',
        ];
    }
}
