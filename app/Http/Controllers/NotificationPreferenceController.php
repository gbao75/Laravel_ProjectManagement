<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationPreferenceController extends Controller
{
    public function edit(Request $request): View
    {
        $preferences = array_replace(
            User::DEFAULT_NOTIFICATION_PREFERENCES,
            $request->user()->notification_preferences ?? []
        );

        return view(
            'notifications.preferences',
            compact('preferences')
        );
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'mentions' => ['required', 'boolean'],
            'deadlines' => ['required', 'boolean'],
            'overdue' => ['required', 'boolean'],
        ]);

        $user = $request->user();

        $user->notification_preferences = [
            'mentions' => $request->boolean('mentions'),
            'deadlines' => $request->boolean('deadlines'),
            'overdue' => $request->boolean('overdue'),
        ];

        $user->save();

        return back()->with(
            'status',
            'Đã lưu tùy chọn thông báo.'
        );
    }
}