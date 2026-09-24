<?php

namespace App\Http\Controllers;

use App\Http\Requests\MyTaskFilterRequest;
use App\Models\SavedTaskFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedTaskFilterController extends Controller
{
    public function store(MyTaskFilterRequest $request): RedirectResponse
    {
        $nameData = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ], [
            'name.required' => 'Vui lòng nhập tên bộ lọc.',
            'name.string' => 'Tên bộ lọc không hợp lệ.',
            'name.max' => 'Tên bộ lọc tối đa 100 ký tự.',
        ]);

        $name = trim($nameData['name']);

        if ($name === '') {
            return back()
                ->withErrors(['name' => 'Vui lòng nhập tên bộ lọc.'])
                ->withInput();
        }

        $filters = $request->filters();

        // Cùng tên trong tài khoản hiện tại: cập nhật bộ lọc.
        SavedTaskFilter::query()->upsert(
            [
                [
                    'user_id' => $request->user()->id,
                    'name' => $name,
                    'filters' => json_encode($filters, JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['user_id', 'name'],
            ['filters', 'updated_at']
        );

        return to_route('my-tasks.index', $filters)
            ->with('status', 'Đã lưu bộ lọc.');
    }

    public function destroy(
        Request $request,
        int $filter
    ): RedirectResponse {
        $savedFilter = SavedTaskFilter::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($filter)
            ->firstOrFail();

        $savedFilter->delete();

        return to_route('my-tasks.index')
            ->with('status', 'Đã xóa bộ lọc.');
    }
}