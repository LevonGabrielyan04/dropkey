<?php

namespace App\Providers;

use App\Actions\Interfaces\PreparesSendPivotData;
use App\Actions\PrepareSendPivotDataAction;
use App\Models\User;
use App\Repositories\Eloquent\CachedSendsRepository;
use App\Repositories\Eloquent\ChatMessageRepository;
use App\Repositories\Eloquent\ConversationRepository;
use App\Repositories\Eloquent\SendRepository;
use App\Repositories\Eloquent\UserIdentityKeyRepository;
use App\Repositories\Interfaces\ChatMessageRepositoryInterface;
use App\Repositories\Interfaces\ConversationRepositoryInterface;
use App\Repositories\Interfaces\SendRepositoryInterface;
use App\Repositories\Interfaces\UserIdentityKeyRepositoryInterface;
use App\Services\ChatMessageService;
use App\Services\Interfaces\ChatMessageServiceInterface;
use App\Services\Interfaces\SendReadServiceInterface;
use App\Services\Interfaces\SendWriteServiceInterface;
use App\Services\SendReadService;
use App\Services\SendWriteService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ChatMessageRepositoryInterface::class, ChatMessageRepository::class);
        $this->app->bind(ConversationRepositoryInterface::class, ConversationRepository::class);
        $this->app->bind(UserIdentityKeyRepositoryInterface::class, UserIdentityKeyRepository::class);
        $this->app->bind(ChatMessageServiceInterface::class, ChatMessageService::class);
        $this->app->bind(SendRepositoryInterface::class, SendRepository::class);
        $this->app->bind(SendWriteServiceInterface::class, SendWriteService::class);
        $this->app->bind(SendReadServiceInterface::class, SendReadService::class);

        $this->app->extend(SendRepositoryInterface::class, function (SendRepositoryInterface $repository, Application $app) {
            return new CachedSendsRepository(
                $repository,
                $app->make('cache.store')
            );
        });

        $this->app->bind(PreparesSendPivotData::class, PrepareSendPivotDataAction::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        RateLimiter::for('sends-write', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });

        RateLimiter::for('sends-index', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()->id);
        });

        RateLimiter::for('chat-write', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()->id);
        });

        RateLimiter::for('chat-poll', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()->id);
        });

        RateLimiter::for('chat-identity', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()->id);
        });

        Gate::define('viewPulse', function (User $user) {
            return ! is_null($user->email_verified_at)
                && $user->email === config('pulse.admin_email');
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        TrustProxies::at(config('app.trusted_proxies', ''));

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(function (): Password {
            $user = auth()->user();

            if (! $user && request()->filled('email')) {
                $user = User::query()
                    ->where('email', request()->input('email'))
                    ->first();
            }

            if ($user?->hasEnabledTwoFactorAuthentication()) {
                return Password::min(8)->uncompromised();
            }

            return Password::min(15)->uncompromised();
        });
    }
}
