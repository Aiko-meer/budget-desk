<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Accounts\Index as AccountsIndex;
use App\Livewire\Bills\Index as BillsIndex;
use App\Livewire\Budgets\Index as BudgetsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Paydays\Index as PaydaysIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Savings\Index as SavingsIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/expenses', ExpensesIndex::class)->name('expenses');
    Route::get('/paydays', PaydaysIndex::class)->name('paydays');
    Route::get('/bills', BillsIndex::class)->name('bills');
    Route::get('/budgets', BudgetsIndex::class)->name('budgets');
    Route::get('/savings', SavingsIndex::class)->name('savings');
    Route::get('/accounts', AccountsIndex::class)->name('accounts');
    Route::get('/reports', ReportsIndex::class)->name('reports');
    Route::get('/reports/export', [ReportExportController::class, 'csv'])->name('reports.export');
    Route::get('/settings', SettingsIndex::class)->name('settings');
    Route::get('/settings/export', [ReportExportController::class, 'json'])->name('settings.export');
});
