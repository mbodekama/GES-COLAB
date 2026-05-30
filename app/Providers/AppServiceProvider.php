<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Payroll;
use App\Observers\ContractObserver;
use App\Observers\EmployeeObserver;
use App\Observers\LeaveObserver;
use App\Observers\PayrollObserver;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActivityLogService::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('fr');
        Paginator::useBootstrapFive();

        // Morph map : noms courts dans la colonne entity_type
        Relation::morphMap([
            'employee' => Employee::class,
            'contract' => Contract::class,
            'leave'    => Leave::class,
            'payroll'  => Payroll::class,
        ]);

        Employee::observe(EmployeeObserver::class);
        Leave::observe(LeaveObserver::class);
        Contract::observe(ContractObserver::class);
        Payroll::observe(PayrollObserver::class);
    }
}
