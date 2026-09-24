<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyTaskFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::in(array_keys(Task::STATUSES)),
            ],
            'priority' => [
                'nullable',
                Rule::in(array_keys(Task::PRIORITIES)),
            ],
            'scope' => [
                'nullable',
                Rule::in(['all', 'project', 'personal']),
            ],
        ];
    }

    public function filters(): array
    {
        $data = $this->validated();

        return [
            'q' => trim($data['q'] ?? ''),
            'status' => $data['status'] ?? '',
            'priority' => $data['priority'] ?? '',
            'scope' => $data['scope'] ?? 'all',
        ];
    }

    public function messages(): array
    {
        return [
            'q.string' => 'Từ khóa không hợp lệ.',
            'q.max' => 'Từ khóa tối đa 100 ký tự.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'priority.in' => 'Độ ưu tiên không hợp lệ.',
            'scope.in' => 'Loại công việc không hợp lệ.',
        ];
    }
}