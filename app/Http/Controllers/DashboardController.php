<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            [
                'label' => 'Dự án',
                'value' => 0,
                'description' => 'Dự án đang quản lý',
            ],
            [
                'label' => 'Công việc',
                'value' => 0,
                'description' => 'Tổng số công việc',
            ],
            [
                'label' => 'Đang thực hiện',
                'value' => 0,
                'description' => 'Công việc đang xử lý',
            ],
            [
                'label' => 'Hoàn thành',
                'value' => 0,
                'description' => 'Công việc đã hoàn thành',
            ],
        ];

        return view('dashboard', [
            'stats' => $stats,
        ]);
    }
}