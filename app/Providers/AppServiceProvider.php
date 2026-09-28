<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Workspace;
use App\Policies\WorkspacePolicy;
use Illuminate\Support\Facades\Gate;
use App\Models\Project;
use App\Policies\ProjectPolicy;
use App\Models\Task;
use App\Observers\TaskObserver;
use App\Events\NotificationsChanged;
use App\Models\User;
use App\Observers\ProjectRealtimeObserver;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Task::observe(TaskObserver::class);
        Project::observe(ProjectRealtimeObserver::class);

        Event::listen(NotificationSent::class, function (NotificationSent $event) {
            if (
                $event->channel === 'database'
                && $event->notifiable instanceof User
            ) {
                NotificationsChanged::dispatch(
                    (int) $event->notifiable->id
                );
            }
        });

        DatabaseNotification::updated(function (DatabaseNotification $notification) {
            if (
                $notification->wasChanged('read_at')
                && $notification->notifiable_type === (new User())->getMorphClass()
            ) {
                NotificationsChanged::dispatch(
                    (int) $notification->notifiable_id
                );
            }
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?? $request->ip()
            );
        });
        
    }
}
