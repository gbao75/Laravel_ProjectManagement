<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    protected $errorBag = 'avatar';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:max_width=2048,max_height=2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Vui lòng chọn ảnh.',
            'avatar.file' => 'File tải lên không hợp lệ.',
            'avatar.image' => 'File phải là hình ảnh.',
            'avatar.mimes' => 'Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.',
            'avatar.extensions' => 'Đuôi file phải là JPG, PNG hoặc WebP.',
            'avatar.max' => 'Ảnh không được vượt quá 2 MB.',

            'avatar.dimensions' =>
                'Chiều rộng và chiều cao ảnh không được vượt quá 2048 px.',

            'avatar.uploaded' =>
                'Không tải được ảnh. Hãy kiểm tra kích thước file.',
        ];
    }
}