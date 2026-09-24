<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public const STATUSES = [
        'planning' => 'Lên kế hoạch',
        'in_progress' => 'Đang thực hiện',
        'on_hold' => 'Tạm dừng',
        'completed' => 'Hoàn thành',
    ];

    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'workspace_id' => 'integer',
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas('members', function (Builder $members) use ($user) {
            $members->where('users.id', $user->id);
        });
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $allowed) use ($user) {
            // Nhánh 1: Chủ sở hữu workspace.
            $allowed->whereHas(
                'workspace',
                function (Builder $workspace) use ($user) {
                    $workspace->where('owner_id', $user->id);
                }
            );

            // Nhánh 2: Vẫn thuộc workspace và có quyền tại dự án.
            $allowed->orWhere(function (Builder $memberAccess) use ($user) {
                $memberAccess->whereHas(
                    'workspace.members',
                    function (Builder $members) use ($user) {
                        $members->where('users.id', $user->id);
                    }
                );

                $memberAccess->where(function (Builder $projectAccess) use ($user) {
                    // Admin workspace.
                    $projectAccess->whereHas(
                        'workspace.members',
                        function (Builder $members) use ($user) {
                            $members->where('users.id', $user->id)
                                ->where('workspace_user.role', 'admin');
                        }
                    );

                    // Thành viên trực tiếp của dự án.
                    $projectAccess->orWhereHas(
                        'members',
                        function (Builder $members) use ($user) {
                            $members->where('users.id', $user->id);
                        }
                    );
                });
            });
        });
    }
}