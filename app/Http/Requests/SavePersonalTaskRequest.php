<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePersonalTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        // Tạo mới: route đã yêu cầu đăng nhập và xác minh email.
        if ($task === null) {
            return $this->user() !== null;
        }

        return $task instanceof Task
            && $task->project_id === null
            && $this->user()->can('update', $task);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('title'))) {
            $this->merge([
                'title' => trim($this->input('title')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => [
                'required',
                Rule::in(array_keys(Task::PRIORITIES)),
            ],
            'status' => [
                'required',
                Rule::in(array_keys(Task::STATUSES)),
            ],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề.',
            'title.string' => 'Tiêu đề không hợp lệ.',
            'title.max' => 'Tiêu đề tối đa 200 ký tự.',
            'description.string' => 'Mô tả không hợp lệ.',
            'description.max' => 'Mô tả tối đa 10.000 ký tự.',
            'priority.required' => 'Vui lòng chọn độ ưu tiên.',
            'priority.in' => 'Độ ưu tiên không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'due_date.date_format' => 'Hạn hoàn thành không hợp lệ.',
        ];
    }
}