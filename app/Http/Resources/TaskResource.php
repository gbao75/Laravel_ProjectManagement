<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,

            'status' => $this->status,
            'priority' => $this->priority,

            'due_date' => $this->due_date?->toDateString(),

            'is_personal' => $this->project_id === null,

            'project' => $this->project
                ? [
                    'id' => $this->project->id,
                    'name' => $this->project->name,

                    'board_version' => (int) $this->project->board_version,
                ]
                : null,

            'assignee' => $this->assignee
                ? [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                ]
                : null,

            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}