<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');
        $project = $this->route('project');

        if (! $workspace instanceof Workspace) {
            return false;
        }

        if ($project instanceof Project) {
            return $project->workspace_id === $workspace->id
                && ($this->user()?->can('update', $project) ?? false);
        }

        return $this->user()?->can(
            'create',
            [Project::class, $workspace]
        ) ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],

            'status' => [
                'required',
                'string',
                Rule::in(array_keys(Project::STATUSES)),
            ],

            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ];

        if ($this->filled('start_date')) {
            $rules['due_date'][] = 'after_or_equal:start_date';
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => 'tên dự án',
            'description' => 'mô tả',
            'status' => 'trạng thái',
            'start_date' => 'ngày bắt đầu',
            'due_date' => 'hạn hoàn thành',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Vui lòng nhập :attribute.',
            'string' => ':attribute phải là văn bản.',
            'max' => ':attribute không được vượt quá :max ký tự.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'date_format' => ':attribute không đúng định dạng ngày.',
            'due_date.after_or_equal' =>
                'Hạn hoàn thành phải bằng hoặc sau ngày bắt đầu.',
        ];
    }
}