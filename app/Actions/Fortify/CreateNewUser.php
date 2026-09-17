<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        $validated = Validator::make(
            $input,
            [
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
                    Rule::unique(User::class, 'email'),
                ],

                'password' => [
                    'required',
                    'string',
                    Password::min(8)->letters()->numbers(),
                    'confirmed',
                ],
            ],
            [
                'name.required' => 'Vui lòng nhập họ tên.',
                'name.string' => 'Họ tên phải là chuỗi ký tự.',
                'name.max' => 'Họ tên không được vượt quá 100 ký tự.',

                'email.required' => 'Vui lòng nhập email.',
                'email.string' => 'Email không hợp lệ.',
                'email.email' => 'Email không đúng định dạng.',
                'email.max' => 'Email không được vượt quá 255 ký tự.',
                'email.unique' => 'Email này đã được đăng ký.',

                'password.required' => 'Vui lòng nhập mật khẩu.',
                'password.string' => 'Mật khẩu không hợp lệ.',
                'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
                'password.letters' => 'Mật khẩu phải chứa chữ cái.',
                'password.numbers' => 'Mật khẩu phải chứa chữ số.',
                'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            ]
        )->validate();

        return User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
    }
}