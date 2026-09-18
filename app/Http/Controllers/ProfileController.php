<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $emailChanged = $user->email !== $data['email'];

        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return to_route('verification.notice')
                ->with('status', 'verification-link-sent');
        }

        return to_route('profile.edit')
            ->with('status', 'profile-updated');
    }

    public function updatePassword(
        UpdatePasswordRequest $request
    ): RedirectResponse {
        $user = $request->user();
        $data = $request->validated();

        $user->password = Hash::make($data['password']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        $request->session()->regenerate();

        return to_route('profile.edit')
            ->with('status', 'password-updated');
    }

    public function updateAvatar(
        UpdateAvatarRequest $request
    ): RedirectResponse {
        $user = $request->user();
        $oldPath = $user->avatar_path;

        $newPath = $request->file('avatar')->store('avatars', 'public');

        if ($newPath === false) {
            return to_route('profile.edit')->withErrors(
                ['avatar' => 'Không thể lưu ảnh. Vui lòng thử lại.'],
                'avatar'
            );
        }

        try {
            $user->avatar_path = $newPath;
            $user->save();
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPath);

            throw $exception;
        }

        if ($oldPath) {
            try {
                if (! Storage::disk('public')->delete($oldPath)) {
                    report(new \RuntimeException(
                        'Không xóa được avatar cũ: ' . $oldPath
                    ));
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return to_route('profile.edit')
            ->with('status', 'avatar-updated');
    }
}