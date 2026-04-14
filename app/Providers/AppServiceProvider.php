<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\UserRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\CarRepositoryInterface::class,
            \App\Repositories\Eloquent\CarRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ServiceRepositoryInterface::class,
            \App\Repositories\Eloquent\ServiceRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\WorkOrderRepositoryInterface::class,
            \App\Repositories\Eloquent\WorkOrderRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\MechanicAssignmentRepositoryInterface::class,
            \App\Repositories\Eloquent\MechanicAssignmentRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\WorkOrderServiceRepositoryInterface::class,
            \App\Repositories\Eloquent\WorkOrderServiceRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureScramble();
        $this->configureGates();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    private function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(
            fn(): ?Password => app()->isProduction()
                ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
                : null
        );
    }

    private function configureScramble(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });

        Scramble::afterOpenApiGenerated(function (\Dedoc\Scramble\Support\Generator\OpenApi $openApi) {
            // docs tetap generate, error di-ignore
        });
    }

    private function configureGates(): void
    {
        // Super admin bypass semua policy, kecuali policy yang explicitly return false
        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}
