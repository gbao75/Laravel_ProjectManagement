<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkspaceService
{
    public function create(User $owner, array $data): Workspace
    {
        return DB::transaction(function () use ($owner, $data) {
            $slugBase = Str::substr(Str::slug($data['name']), 0, 100);

            $workspace = Workspace::create([
                'owner_id' => $owner->id,
                'name' => $data['name'],

                'slug' => ($slugBase ?: 'workspace')
                    . '-'
                    . Str::lower((string) Str::ulid()),

                'description' => $data['description'] ?? null,
                'is_personal' => false,
                'timezone' => 'Asia/Ho_Chi_Minh',
            ]);

            $workspace->members()->attach($owner->id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);

            return $workspace;
        });
    }
}