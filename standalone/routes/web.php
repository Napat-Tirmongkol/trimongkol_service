<?php

use App\Http\Controllers\Accounting\AccountController as AccountingAccountController;
use App\Http\Controllers\Accounting\BillController as AccountingBillController;
use App\Http\Controllers\Accounting\DashboardController as AccountingDashboardController;
use App\Http\Controllers\Accounting\InvoiceController as AccountingInvoiceController;
use App\Http\Controllers\Accounting\OnboardingController as AccountingOnboardingController;
use App\Http\Controllers\Accounting\OpeningBalanceController as AccountingOpeningBalanceController;
use App\Http\Controllers\Accounting\PartnerController as AccountingPartnerController;
use App\Http\Controllers\Accounting\ReportController as AccountingReportController;
use App\Http\Controllers\Accounting\WhtCertificateController as AccountingWhtCertificateController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('accounting.dashboard') : redirect()->route('login'))->name('home');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Public workspace invitation accept flow.
Route::get('/workspace-invite/{token}', [WorkspaceInvitationController::class, 'show'])->name('workspace-invite.show');
Route::post('/workspace-invite/{token}/accept', [WorkspaceInvitationController::class, 'accept'])->name('workspace-invite.accept');

Route::middleware(['auth', 'verified'])->group(function () {
    // The accounting dashboard is the app home once signed in.
    Route::get('/dashboard', fn () => redirect()->route('accounting.dashboard'))->name('dashboard');

    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [AccountingDashboardController::class, 'index'])->name('dashboard');
        Route::get('/onboarding', [AccountingOnboardingController::class, 'show'])->name('onboarding');
        Route::post('/onboarding', [AccountingOnboardingController::class, 'store'])->name('onboarding.store');
        Route::get('/reports', [AccountingReportController::class, 'index'])->name('reports');
        Route::get('/reports/tax', [AccountingReportController::class, 'tax'])->name('reports.tax');
        Route::get('/reports/export', [AccountingReportController::class, 'exportJournal'])->name('reports.export');

        Route::get('/opening-balances', [AccountingOpeningBalanceController::class, 'edit'])->name('opening-balances.edit');
        Route::post('/opening-balances', [AccountingOpeningBalanceController::class, 'store'])->name('opening-balances.store');

        Route::get('/accounts', [AccountingAccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/create', [AccountingAccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [AccountingAccountController::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{account}/edit', [AccountingAccountController::class, 'edit'])->name('accounts.edit');
        Route::patch('/accounts/{account}', [AccountingAccountController::class, 'update'])->name('accounts.update');
        Route::delete('/accounts/{account}', [AccountingAccountController::class, 'destroy'])->name('accounts.destroy');

        Route::get('/partners', [AccountingPartnerController::class, 'index'])->name('partners.index');
        Route::get('/partners/create', [AccountingPartnerController::class, 'create'])->name('partners.create');
        Route::post('/partners', [AccountingPartnerController::class, 'store'])->name('partners.store');

        Route::get('/invoices', [AccountingInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/create', [AccountingInvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices', [AccountingInvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}', [AccountingInvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoice}/issue', [AccountingInvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('/invoices/{invoice}/receipts', [AccountingInvoiceController::class, 'recordReceipt'])->name('invoices.receipts.store');

        Route::get('/bills', [AccountingBillController::class, 'index'])->name('bills.index');
        Route::get('/bills/create', [AccountingBillController::class, 'create'])->name('bills.create');
        Route::post('/bills', [AccountingBillController::class, 'store'])->name('bills.store');
        Route::get('/bills/{bill}', [AccountingBillController::class, 'show'])->name('bills.show');
        Route::post('/bills/{bill}/post', [AccountingBillController::class, 'post'])->name('bills.post');
        Route::post('/bills/{bill}/payments', [AccountingBillController::class, 'recordPayment'])->name('bills.payments.store');

        Route::get('/invoices/{invoice}/print', [AccountingInvoiceController::class, 'print'])->name('invoices.print');
        Route::get('/bills/{bill}/print', [AccountingBillController::class, 'print'])->name('bills.print');
        Route::get('/wht-certificates', [AccountingWhtCertificateController::class, 'index'])->name('wht-certificates.index');
        Route::get('/wht-certificates/{certificate}/print', [AccountingWhtCertificateController::class, 'print'])->name('wht-certificates.print');
    });

    // Subscription billing.
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe');

    // Workspaces (tenants) — create, switch, members, invitations.
    Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::get('/workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::post('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switchTo'])->name('workspaces.switch');
    Route::get('/workspaces/{workspace}/settings', [WorkspaceController::class, 'settings'])->name('workspaces.settings');
    Route::patch('/workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');
    Route::post('/workspaces/{workspace}/members', [WorkspaceController::class, 'inviteMember'])->name('workspaces.members.invite');
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember'])->name('workspaces.members.remove');
    Route::delete('/workspaces/{workspace}/invitations/{invitation}', [WorkspaceController::class, 'revokeInvitation'])->name('workspaces.invitations.revoke');
    Route::post('/workspaces/{workspace}/leave', [WorkspaceController::class, 'leave'])->name('workspaces.leave');
    Route::post('/workspaces/{workspace}/transfer', [WorkspaceController::class, 'transferOwnership'])->name('workspaces.transfer');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/two-factor-challenge', [TwoFactorAuthController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorAuthController::class, 'verifyChallenge'])->middleware('throttle:6,1')->name('two-factor.verify');
    Route::post('/user/two-factor/enable', [TwoFactorAuthController::class, 'enable'])->name('two-factor.enable');
    Route::post('/user/two-factor/confirm', [TwoFactorAuthController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/user/two-factor', [TwoFactorAuthController::class, 'disable'])->name('two-factor.disable');
    Route::post('/user/two-factor/recovery-codes', [TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
});

require __DIR__.'/auth.php';
