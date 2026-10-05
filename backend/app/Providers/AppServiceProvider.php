<?php

namespace App\Providers;

use App\Events\OperationalNotificationRequested;
use App\Listeners\SendOperationalNotification;
use App\Listeners\TransactionalNotificationSubscriber;
use App\Models\DigitalResource;
use App\Models\EmailSetting;
use App\Models\Publication;
use App\Models\User;
use App\Policies\AdminUserPolicy;
use App\Policies\RolePolicy;
use App\Services\Notifications\DynamicMailConfigurator;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) config('e4engineers.rate_limits.api_per_minute', 60))
                ->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('auth-login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('login')).'|'.$request->ip()));

        RateLimiter::for('auth-register', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->ip()));

        RateLimiter::for('auth-password-reset', fn (Request $request): Limit => Limit::perMinute(3)
            ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('admin-login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('downloads', fn (Request $request): Limit => Limit::perMinute((int) config('e4engineers.rate_limits.downloads_per_minute', 20))->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('public-contact', fn (Request $request): Limit => Limit::perHour(5)->by($request->ip().'|'.mb_strtolower((string) $request->input('email'))));
        RateLimiter::for('public-support', fn (Request $request): Limit => Limit::perHour(5)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('workshop-registration', fn (Request $request): Limit => Limit::perHour(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('career-application', fn (Request $request): Limit => Limit::perHour(5)->by($request->ip().'|'.mb_strtolower((string) $request->input('email'))));
        RateLimiter::for('public-search', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));

        Relation::morphMap(['resource' => DigitalResource::class, 'publication' => Publication::class]);

        Gate::before(fn ($user, string $ability): ?bool => $user->hasRole('super-admin') ? true : null);
        Gate::policy(User::class, AdminUserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Event::subscribe(TransactionalNotificationSubscriber::class);
        Event::listen(OperationalNotificationRequested::class, SendOperationalNotification::class);
        Event::listen(NotificationSending::class, function (NotificationSending $event): ?bool {
            if ($event->channel !== 'mail') {
                return null;
            }
            $settings = EmailSetting::current();
            if (! $settings->is_enabled) {
                return false;
            }
            app(DynamicMailConfigurator::class)->apply($settings);
            config(['mail.default' => 'dynamic']);

            return null;
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $query = http_build_query(['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);

            return rtrim((string) config('e4engineers.frontend_url'), '/').'/reset-password?'.$query;
        });
    }
}
