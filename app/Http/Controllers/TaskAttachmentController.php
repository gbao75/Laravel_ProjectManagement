<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use App\Services\TaskActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TaskAttachmentController extends Controller
{
    public function store(
        Request $request,
        Workspace $workspace,
        Project $project,
        Task $task,
        TaskActivityLogger $logger
    ): RedirectResponse {
        Gate::authorize('view', $task);

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf,txt',
                'extensions:jpg,jpeg,png,webp,pdf,txt',
                'max:2048',
            ],
        ], [
            'file.required' => 'Vui lòng chọn tệp.',
            'file.uploaded' => 'Không tải được tệp. Kiểm tra cấu hình PHP.',
            'file.mimes' => 'Chỉ nhận JPG, PNG, WebP, PDF hoặc TXT.',
            'file.extensions' => 'Phần mở rộng của tệp không hợp lệ.',
            'file.max' => 'Tệp tối đa 2 MB.',
        ]);

        $file = $request->file('file');

        $originalName = basename(str_replace(
            '\\',
            '/',
            $file->getClientOriginalName()
        ));

        $originalName = preg_replace(
            '/[\x00-\x1F\x7F]/',
            '',
            $originalName
        );

        $originalName = Str::limit($originalName, 180, '');

        if ($originalName === '') {
            $originalName = 'attachment';
        }

        // Laravel tự tạo tên lưu để tránh trùng tên file.
        $path = $file->store(
            'tasks/'.$task->id,
            'task_attachments'
        );

        try {
            DB::transaction(function () use (
                $request,
                $task,
                $file,
                $path,
                $originalName,
                $logger
            ) {
                $attachment = $task->attachments()->make();

                $attachment->user_id = $request->user()->id;
                $attachment->original_name = $originalName;
                $attachment->path = $path;
                $attachment->mime_type = $file->getMimeType();
                $attachment->size = $file->getSize();

                $attachment->save();

                $logger->record($task, 'attachment.created', [
                    'name' => $originalName,
                ]);
            });
        } catch (Throwable $exception) {
            try {
                Storage::disk('task_attachments')->delete($path);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        return back()->with('status', 'Đã tải tệp lên.');
    }

    public function download(
        Workspace $workspace,
        Project $project,
        Task $task,
        int $attachment
    ): StreamedResponse {
        Gate::authorize('view', $task);

        $file = $task->attachments()->findOrFail($attachment);

        $disk = Storage::disk('task_attachments');

        abort_unless($disk->exists($file->path), 404);

        return $disk->download(
            $file->path,
            $file->original_name,
            [
                'Content-Type' => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function destroy(
        Request $request,
        Workspace $workspace,
        Project $project,
        Task $task,
        int $attachment,
        TaskActivityLogger $logger
    ): RedirectResponse {
        Gate::authorize('view', $task);

        $file = $task->attachments()->findOrFail($attachment);

        abort_unless(
            (int) $file->user_id === (int) $request->user()->id
            || $request->user()->can('update', $task),
            403
        );

        $path = $file->path;

        DB::transaction(function () use ($file, $task, $logger) {
            $logger->record($task, 'attachment.deleted', [
                'name' => $file->original_name,
            ]);

            $file->delete();
        });

        try {
            Storage::disk('task_attachments')->delete($path);
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('status', 'Đã xóa tệp đính kèm.');
    }
}