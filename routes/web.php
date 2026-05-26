<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\SalaryGridController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

require __DIR__.'/auth.php';

Route::middleware(['auth'])->group(function () {

    // ── Dashboard ────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Profil ───────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Employés ─────────────────────────────────────────────
    Route::resource('employees', EmployeeController::class);
    Route::get('/employees/{employee}/print', [EmployeeController::class, 'print'])
        ->name('employees.print');

    // ── Contrats ─────────────────────────────────────────────
    Route::resource('contracts', ContractController::class);
    Route::get('/contracts/{contract}/print', [ContractController::class, 'print'])
        ->name('contracts.print');
    Route::post('/contracts/{contract}/renew', [ContractController::class, 'renew'])
        ->name('contracts.renew');

    // ── Congés & Permissions ─────────────────────────────────
    Route::resource('leaves', LeaveController::class);
    Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve'])
        ->name('leaves.approve');
    Route::post('/leaves/{leave}/reject', [LeaveController::class, 'reject'])
        ->name('leaves.reject');
    Route::get('/leaves/{leave}/print', [LeaveController::class, 'print'])
        ->name('leaves.print');

    // ── Paie ─────────────────────────────────────────────────
    Route::middleware(['role:superadmin|admin|comptable'])->group(function () {
        Route::resource('payroll', PayrollController::class)->except(['create', 'store', 'edit', 'update', 'destroy']);
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])
            ->name('payroll.generate');
        Route::get('/payroll/{payroll}/pdf', [PayrollController::class, 'pdf'])
            ->name('payroll.pdf');

        // Grilles salariales
        Route::resource('salary-grids', SalaryGridController::class)->except(['show', 'create', 'edit']);
    });

    // ── Messagerie ───────────────────────────────────────────
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{user}/send', [MessageController::class, 'send'])->name('messages.send');
    Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

    // ── Rôles & Permissions ──────────────────────────────────
    Route::middleware(['role:superadmin'])->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
        Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])
            ->name('roles.permissions.update');
        Route::post('/users/{user}/roles', [RoleController::class, 'assignRole'])
            ->name('users.roles.assign');
    });

    // ── Configuration ────────────────────────────────────────
    Route::middleware(['role:superadmin|admin'])->group(function () {
        Route::get('/config', [ConfigController::class, 'index'])->name('config.index');
        Route::post('/config/general', [ConfigController::class, 'updateGeneral'])->name('config.general');
        Route::post('/config/payroll', [ConfigController::class, 'updatePayroll'])->name('config.payroll');
        Route::post('/config/leaves', [ConfigController::class, 'updateLeaves'])->name('config.leaves');
    });

    // ── API JSON (widgets dynamiques) ────────────────────────
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
        Route::get('/messages/unread', [MessageController::class, 'unread'])->name('messages.unread');
        Route::get('/employees/search', [EmployeeController::class, 'search'])->name('employees.search');
    });
});
