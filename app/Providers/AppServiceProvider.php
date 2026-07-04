<?php

namespace App\Providers;

use App\Actions\Interfaces\PreparesSendPivotData;
use App\Actions\PrepareSendPivotDataAction;
use App\Models\User;
use App\Repositories\Eloquent\CachedSendsRepository;
use App\Repositories\Eloquent\SendRepository;
use App\Repositories\Interfaces\SendRepositoryInterface;
use App\Services\Interfaces\SendReadServiceInterface;
use App\Services\Interfaces\SendWriteServiceInterface;
use App\Services\SendReadService;
use App\Services\SendWriteService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Session\EncryptedStore;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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

        $this->app->booting(function (): void {
            $config = $this->app->make('config');

            if ($config->has('database.valkey')) {
                $config->set('database.redis', $config->get('database.valkey'));
            }

            if ($config->has('pulse.ingest.valkey')) {
                $config->set('pulse.ingest.redis', $config->get('pulse.ingest.valkey'));
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureValkeyDrivers();
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
     * Register Valkey-backed drivers and Laravel compatibility aliases.
     */
    protected function configureValkeyDrivers(): void
    {
        Session::extend('valkey', function ($app) {
            $config = $app['config'];

            $handler = new CacheBasedSessionHandler(
                clone $app['cache']->store('valkey'),
                $config->get('session.lifetime')
            );

            if ($connection = $config->get('session.connection')) {
                $handler->getCache()->getStore()->setConnection($connection);
            }

            if ($config->get('session.encrypt')) {
                return new EncryptedStore(
                    $config->get('session.cookie'),
                    $handler,
                    $app['encrypter'],
                    id: null,
                    serialization: $config->get('session.serialization', 'php'),
                );
            }

            return new Store(
                $config->get('session.cookie'),
                $handler,
                id: null,
                serialization: $config->get('session.serialization', 'php'),
            );
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
