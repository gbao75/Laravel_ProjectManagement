<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    public function reset($user, array $input): void
    {
        $validated = Validator::make(
            $input,
            [
                'password' => [
                    'required',
                    'string',
                    Password::min(8)->letters()->numbers(),
                    'confirmed',
                ],
            ],
            [
                'password.required' => 'Vui lòng nhập mật khẩu mới.',
                'password.string' => 'Mật khẩu không hợp lệ.',
                'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
                'password.letters' => 'Mật khẩu phải chứa chữ cái.',
                'password.numbers' => 'Mật khẩu phải chứa chữ số.',
                'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            ]
        )->validate();

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();
    }
}