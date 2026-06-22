<?php

namespace App\Providers;

use App\Actions\Interfaces\PreparesSendPivotData;
use App\Actions\PrepareSendPivotDataAction;
use App\Repositories\Eloquent\CachedSendsRepository;
use App\Repositories\Eloquent\SendRepository;
use App\Repositories\Interfaces\SendRepositoryInterface;
use App\Services\Interfaces\SendReadServiceInterface;
use App\Services\Interfaces\SendServiceInterface;
use App\Services\SendReadService;
use App\Services\SendService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
        $this->app->bind(SendRepositoryInterface::class, SendRepository::class);
        $this->app->bind(SendServiceInterface::class, SendService::class);
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
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
