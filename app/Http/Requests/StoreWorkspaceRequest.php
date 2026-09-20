<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên workspace.',
            'name.string' => 'Tên workspace phải là văn bản.',
            'name.max' => 'Tên workspace không được vượt quá 120 ký tự.',

            'description.string' => 'Mô tả phải là văn bản.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
        ];
    }
}