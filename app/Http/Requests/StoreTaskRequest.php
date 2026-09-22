<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [
            Task::class,
            $this->route('project'),
        ]);
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
        $project = $this->route('project');

        return [
            'title' => [
                'required',
                'string',
                'max:200',
            ],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'priority' => [
                'required',
                Rule::in(array_keys(Task::PRIORITIES)),
            ],

            'due_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'assigned_to' => [
                'bail',
                'nullable',
                'integer',

                // Phải có tên trong đúng dự án.
                Rule::exists('project_user', 'user_id')
                    ->where('project_id', $project->id),

                // Đồng thời vẫn thuộc workspace của dự án.
                Rule::exists('workspace_user', 'user_id')
                    ->where('workspace_id', $project->workspace_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề công việc.',
            'title.string' => 'Tiêu đề phải là chuỗi ký tự.',
            'title.max' => 'Tiêu đề không được vượt quá 200 ký tự.',

            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.max' => 'Mô tả không được vượt quá 10.000 ký tự.',

            'priority.required' => 'Vui lòng chọn độ ưu tiên.',
            'priority.in' => 'Độ ưu tiên không hợp lệ.',

            'due_date.date_format' => 'Hạn hoàn thành không hợp lệ.',

            'assigned_to.integer' => 'Người thực hiện không hợp lệ.',
            'assigned_to.exists' =>
                'Người thực hiện phải thuộc dự án và workspace hiện tại.',
        ];
    }
}