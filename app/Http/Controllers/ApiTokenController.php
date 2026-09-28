<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public const ABILITIES = [
        'tasks:read' => 'Xem công việc',
        'tasks:write' => 'Cập nhật trạng thái công việc',
        'reports:read' => 'Xem báo cáo workspace',
    ];

    public function index(Request $request): View
    {
        $tokens = $request->user()
            ->tokens()
            ->latest('id')
            ->paginate(10);

        $abilities = self::ABILITIES;

        return view('api-tokens.index', compact(
            'tokens',
            'abilities'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],

            'password' => [
                'required',
                'string',
                'current_password:web',
            ],

            'abilities' => ['required', 'array', 'min:1'],

            'abilities.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(array_keys(self::ABILITIES)),
            ],
        ], [
            'password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'abilities.required' => 'Vui lòng chọn ít nhất một quyền.',
        ]);

        $token = $request->user()->createToken(
            $data['name'],
            $data['abilities'],
            now()->addDays(30)
        );

        return redirect()
            ->route('api-tokens.index')
            ->with('plain_token', $token->plainTextToken)
            ->with('status', 'Đã tạo token, có hiệu lực trong 30 ngày.');
    }

    public function destroy(
        Request $request,
        int $token
    ): RedirectResponse {
        $request->user()
            ->tokens()
            ->whereKey($token)
            ->firstOrFail()
            ->delete();

        return back()->with('status', 'Đã thu hồi token.');
    }
}