<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Policies\ActivityPolicy;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Foundation\DevCommands;

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
        $this->configureDefaults();
        $this->registerPolicies();
        $this->configureDevCommands();
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

        /**
     * Policies de models que não estão em App\Models
     * (o Gate não consegue descobri-las pelo nome).
     */
    protected function registerPolicies(): void
    {
        Gate::policy(Activity::class, ActivityPolicy::class);
    }

     /**
     * No Windows não existe pcntl: o "composer run dev" usa o queue:listen
     * no lugar do horizon. No servidor (Linux), quem roda o Horizon é o Supervisor.
     */
    protected function configureDevCommands(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            DevCommands::except('horizon');
        }
    }
}
