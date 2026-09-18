<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    protected $errorBag = 'profile';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($this->user()->id),
            ],

            'current_password' => [
                Rule::requiredIf(
                    fn () => $this->input('email') !== $this->user()->email
                ),
                'nullable',
                'string',
                'current_password:web',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.max' => 'Họ tên không được vượt quá 100 ký tự.',

            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.unique' => 'Email này đã được tài khoản khác sử dụng.',

            'current_password.required' =>
                'Vui lòng nhập mật khẩu hiện tại để đổi email.',

            'current_password.current_password' =>
                'Mật khẩu hiện tại không chính xác.',
        ];
    }
}