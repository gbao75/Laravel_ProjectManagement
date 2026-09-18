<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    protected $errorBag = 'password';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                'current_password:web',
            ],

            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->numbers(),
                'confirmed',
                'different:current_password',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' =>
                'Vui lòng nhập mật khẩu hiện tại.',

            'current_password.current_password' =>
                'Mật khẩu hiện tại không chính xác.',

            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.letters' => 'Mật khẩu mới phải có chữ cái.',
            'password.numbers' => 'Mật khẩu mới phải có chữ số.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',

            'password.different' =>
                'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ];
    }
}