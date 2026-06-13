<?php

use App\Http\Controllers\Accounting\AccountController as AccountingAccountController;
use App\Http\Controllers\Accounting\AuditLogController as AccountingAuditLogController;
use App\Http\Controllers\Accounting\AccountingUserController as AccountingUserController;
use App\Http\Controllers\Accounting\ApprovalController as AccountingApprovalController;
use App\Http\Controllers\Accounting\AttachmentController as AccountingAttachmentController;
use App\Http\Controllers\Accounting\AuthController as AccountingAuthController;
use App\Http\Controllers\Accounting\BankReconciliationController as AccountingBankReconciliationController;
use App\Http\Controllers\Accounting\BillController as AccountingBillController;
use App\Http\Controllers\Accounting\BudgetController as AccountingBudgetController;
use App\Http\Controllers\Accounting\DashboardController as AccountingDashboardController;
use App\Http\Controllers\Accounting\DepartmentController as AccountingDepartmentController;
use App\Http\Controllers\Accounting\FixedAssetController as AccountingFixedAssetController;
use App\Http\Controllers\Accounting\OnboardingController as AccountingOnboardingController;
use App\Http\Controllers\Accounting\InvoiceController as AccountingInvoiceController;
use App\Http\Controllers\Accounting\ManualJournalController as AccountingManualJournalController;
use App\Http\Controllers\Accounting\OpeningBalanceController as AccountingOpeningBalanceController;
use App\Http\Controllers\Accounting\PartnerController as AccountingPartnerController;
use App\Http\Controllers\Accounting\PayrollController as AccountingPayrollController;
use App\Http\Controllers\Accounting\PeriodController as AccountingPeriodController;
use App\Http\Controllers\Accounting\ProductController as AccountingProductController;
use App\Http\Controllers\Accounting\RecurringJournalController as AccountingRecurringJournalController;
use App\Http\Controllers\Accounting\ReportController as AccountingReportController;
use App\Http\Controllers\Accounting\WhtCertificateController as AccountingWhtCertificateController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\Products\AccountingController as AdminAccountingController;
use App\Http\Controllers\Admin\Products\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\Products\ScannerController as AdminScannerController;
use App\Http\Controllers\Admin\Products\SocialController as AdminSocialController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\SystemController as AdminSystemController;
use App\Http\Controllers\Admin\WorkspaceController as AdminWorkspaceController;
use App\Http\Controllers\Portfolio\AuthController as PortfolioAuthController;
use App\Http\Controllers\Portfolio\DashboardController as PortfolioDashboardController;
use App\Http\Controllers\Portfolio\TransactionController as PortfolioTransactionController;
use App\Http\Controllers\Portfolio\PlannerController as PortfolioPlannerController;
use App\Http\Controllers\Portfolio\BudgetController as PortfolioBudgetController;
use App\Http\Controllers\Portfolio\LedgerController as PortfolioLedgerController;
use App\Http\Controllers\Portfolio\DebtOverviewController as PortfolioDebtOverviewController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClassroomController;
use App\Models\Classroom;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GradebookController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicQueueController;
use App\Http\Controllers\QueueBillingController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use Illuminate\Support\Facades\Route;

// Marketing site
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');
Route::get('/donate', [PageController::class, 'donate'])->name('donate');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');

// Smart Clipboard OCR — public, free, runs entirely in the browser (Tesseract.js
// via CDN). No upload, no auth, no server processing — just returns the page.
Route::get('/ocr', [PageController::class, 'ocr'])->name('ocr');

// Public, login-free queue page — customers scan the QR / open the share link
// to pull a ticket and watch the queue live. Resolved by unguessable token.
Route::get('/q/{token}', [PublicQueueController::class, 'show'])->name('queue.public');
Route::get('/q/{token}/tts', [PublicQueueController::class, 'tts'])->middleware('throttle:120,1')->name('queue.public.tts');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->where('locale', 'th|en')
    ->name('locale.switch');

// Workspace invitation links — handle guest + authenticated cases.
Route::get('/workspace-invite/{token}', [WorkspaceInvitationController::class, 'show'])
    ->name('workspace-invitations.show');
Route::post('/workspace-invite/{token}/accept', [WorkspaceInvitationController::class, 'accept'])
    ->middleware('auth')
    ->name('workspace-invitations.accept');

// LINE Webhook for capturing user ID (public, CSRF-exempt)
Route::post('/line/webhook', [App\Http\Controllers\Admin\NotificationController::class, 'handleLineWebhook'])->name('line.webhook');

// Admin portal (separate login from the regular Breeze /login)
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'store'])->name('admin.login.store');
});
Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

// Personal-portfolio area — Google OAuth sign-in for the owner only.
// Authorisation is the email allowlist in config/portfolio.php, enforced
// by both the OAuth callback (friendly error) and the route middleware
// (hard 403 in case session state ever drifts).
Route::prefix('portfolio')->name('portfolio.')->group(function () {
    Route::get('/login', [PortfolioAuthController::class, 'showLogin'])->name('login');
    Route::get('/auth/google', [PortfolioAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [PortfolioAuthController::class, 'callback'])->name('auth.google.callback');
    Route::post('/logout', [PortfolioAuthController::class, 'logout'])->name('logout');

    Route::middleware('portfolio.access')->group(function () {
        Route::get('/', [PortfolioDashboardController::class, 'index'])->name('dashboard');
        Route::post('/refresh', [PortfolioDashboardController::class, 'refresh'])->name('refresh');
        Route::get('/holdings/create', [PortfolioDashboardController::class, 'create'])->name('holdings.create');
        Route::post('/holdings', [PortfolioDashboardController::class, 'store'])->name('holdings.store');
        Route::get('/holdings/{holding}/edit', [PortfolioDashboardController::class, 'edit'])->name('holdings.edit');
        Route::patch('/holdings/{holding}', [PortfolioDashboardController::class, 'update'])->name('holdings.update');
        Route::delete('/holdings/{holding}', [PortfolioDashboardController::class, 'destroy'])->name('holdings.destroy');

        // Transactions ledger
        Route::get('/holdings/{holding}/transactions', [PortfolioTransactionController::class, 'index'])->name('holdings.transactions.index');
        Route::post('/holdings/{holding}/transactions', [PortfolioTransactionController::class, 'store'])->name('holdings.transactions.store');
        Route::delete('/holdings/{holding}/transactions/{transaction}', [PortfolioTransactionController::class, 'destroy'])->name('holdings.transactions.destroy');

        // Investment Planner
        Route::get('/planner', [PortfolioPlannerController::class, 'index'])->name('planner');
        Route::post('/goals', [PortfolioPlannerController::class, 'storeGoal'])->name('goals.store');
        Route::patch('/goals/{goal}', [PortfolioPlannerController::class, 'updateGoal'])->name('goals.update');
        Route::delete('/goals/{goal}', [PortfolioPlannerController::class, 'destroyGoal'])->name('goals.destroy');
        Route::post('/watchlist', [PortfolioPlannerController::class, 'storeWatchlistItem'])->name('watchlist.store');
        Route::delete('/watchlist/{item}', [PortfolioPlannerController::class, 'destroyWatchlistItem'])->name('watchlist.destroy');
        Route::post('/watchlist/refresh', [PortfolioPlannerController::class, 'refreshWatchlist'])->name('watchlist.refresh');

        // Monthly Budget & Debt Tracker
        Route::get('/budget', [PortfolioBudgetController::class, 'index'])->name('budget.index');
        Route::post('/budget/incomes', [PortfolioBudgetController::class, 'storeIncome'])->name('budget.income.store');
        Route::patch('/budget/incomes/{income}', [PortfolioBudgetController::class, 'updateIncome'])->name('budget.income.update');
        Route::delete('/budget/incomes/{income}', [PortfolioBudgetController::class, 'destroyIncome'])->name('budget.income.destroy');
        Route::post('/budget/reset', [PortfolioBudgetController::class, 'resetMonth'])->name('budget.reset');
        Route::post('/budget/items', [PortfolioBudgetController::class, 'storeBudgetItem'])->name('budget.items.store');
        Route::patch('/budget/items/{item}', [PortfolioBudgetController::class, 'updateBudgetItem'])->name('budget.items.update');
        Route::delete('/budget/items/{item}', [PortfolioBudgetController::class, 'destroyBudgetItem'])->name('budget.items.destroy');
        Route::post('/budget/toggle/{type}/{id}', [PortfolioBudgetController::class, 'toggleCheck'])->name('budget.toggle');
        Route::post('/budget/installments', [PortfolioBudgetController::class, 'storeInstallment'])->name('budget.installments.store');
        Route::patch('/budget/installments/{installment}', [PortfolioBudgetController::class, 'updateInstallment'])->name('budget.installments.update');
        Route::delete('/budget/installments/{installment}', [PortfolioBudgetController::class, 'destroyInstallment'])->name('budget.installments.destroy');
        Route::post('/budget/subscriptions', [PortfolioBudgetController::class, 'storeSubscription'])->name('budget.subscriptions.store');
        Route::patch('/budget/subscriptions/{subscription}', [PortfolioBudgetController::class, 'updateSubscription'])->name('budget.subscriptions.update');
        Route::delete('/budget/subscriptions/{subscription}', [PortfolioBudgetController::class, 'destroySubscription'])->name('budget.subscriptions.destroy');
        Route::post('/budget/debts', [PortfolioBudgetController::class, 'storeDebt'])->name('budget.debts.store');
        Route::patch('/budget/debts/{debt}', [PortfolioBudgetController::class, 'updateDebt'])->name('budget.debts.update');
        Route::delete('/budget/debts/{debt}', [PortfolioBudgetController::class, 'destroyDebt'])->name('budget.debts.destroy');
        Route::post('/budget/debt-payments', [PortfolioBudgetController::class, 'storeDebtPayment'])->name('budget.debt-payments.store');
        Route::patch('/budget/debt-payments/{payment}', [PortfolioBudgetController::class, 'updateDebtPayment'])->name('budget.debt-payments.update');
        Route::delete('/budget/debt-payments/{payment}', [PortfolioBudgetController::class, 'destroyDebtPayment'])->name('budget.debt-payments.destroy');

        // Daily Income / Expense Ledger
        Route::get('/ledger', [PortfolioLedgerController::class, 'index'])->name('ledger.index');
        Route::post('/ledger', [PortfolioLedgerController::class, 'store'])->name('ledger.store');
        Route::patch('/ledger/{entry}', [PortfolioLedgerController::class, 'update'])->name('ledger.update');
        Route::delete('/ledger/{entry}', [PortfolioLedgerController::class, 'destroy'])->name('ledger.destroy');

        // Debt Overview
        Route::get('/debts', [PortfolioDebtOverviewController::class, 'index'])->name('debts.index');
    });
});

// Authenticated app — the free Homework Scanner product
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/scanner', [ClassroomController::class, 'index'])->name('dashboard');

    Route::get('/plans', [PlansController::class, 'index'])->name('plans.index');

    // Queue System product — workspace-scoped. Counter staff operate here;
    // customers pull tickets on the public /q/{token} page (registered below).
    Route::middleware('product:queue')->group(function () {
        Route::get('/queues', [QueueController::class, 'index'])->name('queues.index');
        Route::get('/queues/create', [QueueController::class, 'create'])->name('queues.create');
        // Billing routes use a static /queues/billing prefix and must stay above the
        // /queues/{queue} wildcard so "billing" isn't captured as a queue id.
        Route::get('/queues/billing', [QueueBillingController::class, 'show'])->name('queues.billing');
        Route::get('/queues/billing/{plan}', [QueueBillingController::class, 'pay'])->name('queues.billing.pay');
        Route::post('/queues/billing/{plan}', [QueueBillingController::class, 'submit'])->name('queues.billing.submit');
        Route::post('/queues', [QueueController::class, 'store'])->name('queues.store');
        Route::get('/queues/{queue}', [QueueController::class, 'show'])->name('queues.show');
        Route::get('/queues/{queue}/edit', [QueueController::class, 'edit'])->name('queues.edit');
        Route::patch('/queues/{queue}', [QueueController::class, 'update'])->name('queues.update');
        Route::delete('/queues/{queue}', [QueueController::class, 'destroy'])->name('queues.destroy');
        Route::post('/queues/{queue}/counters', [QueueController::class, 'addCounter'])->name('queues.counters.store');
        Route::delete('/queues/{queue}/counters/{counter}', [QueueController::class, 'removeCounter'])->name('queues.counters.destroy');
        Route::post('/queues/{queue}/reset', [QueueController::class, 'reset'])->name('queues.reset');
        Route::get('/queues/{queue}/poster', [QueueController::class, 'poster'])->name('queues.poster');
        Route::get('/queues/{queue}/tts', [QueueController::class, 'tts'])->name('queues.tts');
    });


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

    Route::post('classrooms/demo', [ClassroomController::class, 'demo'])->name('classrooms.demo');
    Route::resource('classrooms', ClassroomController::class)->except(['index']);

    // Static student routes must be registered before the {student} resource
    // routes, otherwise /students/bulk would match show with student="bulk".
    Route::get('classrooms/{classroom}/students/bulk', [StudentController::class, 'bulkCreate'])
        ->name('classrooms.students.bulk');
    Route::post('classrooms/{classroom}/students/bulk', [StudentController::class, 'bulkStore'])
        ->name('classrooms.students.bulk.store');
    Route::get('classrooms/{classroom}/students/print', [StudentController::class, 'print'])
        ->name('classrooms.students.print');

    Route::resource('classrooms.students', StudentController::class)
        ->only(['show', 'create', 'store', 'edit', 'update', 'destroy']);

    Route::get('classrooms/{classroom}/students/{student}/qr', [StudentController::class, 'printQr'])
        ->name('classrooms.students.qr');

    Route::get('classrooms/{classroom}/gradebook', [GradebookController::class, 'show'])
        ->name('classrooms.gradebook');
    Route::get('classrooms/{classroom}/gradebook/export', [GradebookController::class, 'export'])
        ->name('classrooms.gradebook.export');

    // Attendance — daily roll call, separate from assignment-style scoring.
    Route::get('classrooms/{classroom}/attendance', [AttendanceController::class, 'index'])
        ->name('classrooms.attendance.index');
    Route::post('classrooms/{classroom}/attendance/today', [AttendanceController::class, 'today'])
        ->name('classrooms.attendance.today');
    Route::get('classrooms/{classroom}/attendance/{session}', [AttendanceController::class, 'show'])
        ->name('classrooms.attendance.show');
    Route::patch('classrooms/{classroom}/attendance/{session}', [AttendanceController::class, 'update'])
        ->name('classrooms.attendance.update');
    Route::delete('classrooms/{classroom}/attendance/{session}', [AttendanceController::class, 'destroy'])
        ->name('classrooms.attendance.destroy');
    Route::get('classrooms/{classroom}/students/{student}/attendance', [AttendanceController::class, 'studentHistory'])
        ->name('classrooms.students.attendance');

    Route::resource('classrooms.assignments', AssignmentController::class)
        ->only(['create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::get('classrooms/{classroom}/assignments/{assignment}/scan', [AssignmentController::class, 'scan'])
        ->name('classrooms.assignments.scan');

    Route::get('classrooms/{classroom}/assignments/{assignment}/export', [AssignmentController::class, 'export'])
        ->name('classrooms.assignments.export');

    Route::post('classrooms/{classroom}/assignments/{assignment}/submissions', [SubmissionController::class, 'store'])
        ->name('classrooms.assignments.submissions.store');
    Route::patch('classrooms/{classroom}/assignments/{assignment}/submissions/{submission}', [SubmissionController::class, 'update'])
        ->name('classrooms.assignments.submissions.update');
    Route::delete('classrooms/{classroom}/assignments/{assignment}/submissions/{submission}', [SubmissionController::class, 'destroy'])
        ->name('classrooms.assignments.submissions.destroy');

    // Admin back-office is exempt from email verification — staff are managed
    // by a super admin, and this also avoids locking the deployer out before
    // the backfill migration runs.
    Route::middleware('admin')->withoutMiddleware('verified')->prefix('admin')->name('admin.')->group(function () {
        // Platform-wide tools (cut across all products). Every admin role can
        // see the dashboard; everything else is gated by a granular permission
        // (see config/permissions.php). Super Admin holds '*' and passes all.
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/logs', [AdminController::class, 'logs'])->name('logs')->middleware('can:audit.view');
        Route::get('/security', [AdminController::class, 'security'])->name('security')->middleware('can:security.view');

        Route::middleware('can:workspaces.view')->group(function () {
            Route::get('/workspaces', [AdminWorkspaceController::class, 'index'])->name('workspaces.index');
            Route::get('/workspaces/{workspace}', [AdminWorkspaceController::class, 'show'])->name('workspaces.show');
            Route::get('/billing', [AdminController::class, 'billing'])->name('billing');
        });
        Route::middleware('can:workspaces.manage')->group(function () {
            Route::patch('/workspaces/{workspace}/plan', [AdminWorkspaceController::class, 'updatePlan'])->name('workspaces.update-plan');
            Route::patch('/workspaces/{workspace}/queue-plan', [AdminWorkspaceController::class, 'updateQueuePlan'])->name('workspaces.update-queue-plan');
            Route::patch('/workspaces/{workspace}/accounting-plan', [AdminWorkspaceController::class, 'updateAccountingPlan'])->name('workspaces.update-accounting-plan');
            Route::post('/billing', [AdminController::class, 'updateBilling'])->name('billing.update');
            Route::delete('/workspaces/{workspace}', [AdminWorkspaceController::class, 'destroy'])->name('workspaces.destroy');
        });

        Route::middleware('can:leads.view')->group(function () {
            Route::get('/leads', [AdminLeadController::class, 'index'])->name('leads.index');
            Route::get('/leads/{lead}', [AdminLeadController::class, 'show'])->name('leads.show');
            Route::get('/leads/{lead}/attachment', [AdminLeadController::class, 'attachment'])->name('leads.attachment');
        });
        Route::middleware('can:leads.manage')->group(function () {
            Route::patch('/leads/{lead}', [AdminLeadController::class, 'update'])->name('leads.update');
            Route::delete('/leads/{lead}', [AdminLeadController::class, 'destroy'])->name('leads.destroy');
        });

        Route::middleware('can:users.view')->group(function () {
            Route::get('/users', [AdminController::class, 'users'])->name('users');
            Route::get('/users/export', [AdminController::class, 'exportUsers'])->name('users.export');
            Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
        });
        Route::post('/users/{user}/role', [AdminController::class, 'assignRole'])->name('users.assign-role')->middleware('can:users.assign_roles');
        Route::middleware('can:users.manage')->group(function () {
            Route::post('/users/{user}/toggle-active', [AdminController::class, 'toggleActive'])->name('users.toggle-active');
            Route::post('/users/{user}/password-reset', [AdminController::class, 'sendPasswordReset'])->name('users.password-reset');
            Route::post('/users/{user}/impersonate', [AdminController::class, 'impersonate'])->name('users.impersonate');
        });
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy')->middleware('can:users.delete');

        // Roles & permissions (full RBAC)
        Route::middleware('can:roles.manage')->group(function () {
            Route::get('/roles', [AdminRoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/create', [AdminRoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [AdminRoleController::class, 'store'])->name('roles.store');
            Route::get('/roles/{role}/edit', [AdminRoleController::class, 'edit'])->name('roles.edit');
            Route::patch('/roles/{role}', [AdminRoleController::class, 'update'])->name('roles.update');
            Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->name('roles.destroy');
        });

        Route::middleware('can:cms.manage')->group(function () {
            Route::get('/site', [SiteSettingsController::class, 'edit'])->name('site-settings.edit');
            Route::patch('/site', [SiteSettingsController::class, 'update'])->name('site-settings.update');

            // Platform notification channels (LINE, Discord) — shared by all products.
            Route::get('/notifications', [AdminNotificationController::class, 'edit'])->name('notifications.edit');
            Route::post('/notifications/line', [AdminNotificationController::class, 'updateLine'])->name('notifications.line');
            Route::post('/notifications/line-test', [AdminNotificationController::class, 'testLine'])->name('notifications.line-test');
            Route::post('/notifications/line-clear-captured', [AdminNotificationController::class, 'clearLineCaptured'])->name('notifications.line-clear-captured');
            Route::post('/notifications/discord', [AdminNotificationController::class, 'updateDiscord'])->name('notifications.discord');
            Route::post('/notifications/discord-test', [AdminNotificationController::class, 'testDiscord'])->name('notifications.discord-test');
        });

        Route::middleware('can:system.manage')->group(function () {
            Route::get('/system', [AdminSystemController::class, 'index'])->name('system');
            Route::post('/system/pull', [AdminSystemController::class, 'pull'])->name('system.pull');
            Route::post('/system/migrate', [AdminSystemController::class, 'migrate'])->name('system.migrate');
            Route::post('/system/clear-cache', [AdminSystemController::class, 'clearCache'])->name('system.clear-cache');
            Route::post('/system/build-assets', [AdminSystemController::class, 'buildAssets'])->name('system.build-assets');
            Route::post('/system/seed-accounting-demo', [AdminSystemController::class, 'seedAccountingDemo'])->name('system.seed-accounting-demo');
            Route::post('/system/reset-accounting-workspace', [AdminSystemController::class, 'resetAccountingWorkspace'])->name('system.reset-accounting-workspace');
            Route::post('/system/test-email', [AdminSystemController::class, 'testEmail'])->name('system.test-email');
        });

        // Product-specific moderation. New products get a sibling group here +
        // an entry in config/admin-products.php and the nav picks them up.
        Route::middleware('can:products.moderate')->group(function () {
            Route::prefix('products/scanner')->name('scanner.')->group(function () {
                Route::get('/', [AdminScannerController::class, 'dashboard'])->name('dashboard');
                Route::get('/classrooms', [AdminScannerController::class, 'classrooms'])->name('classrooms');
                Route::get('/classrooms/{classroom}', [AdminScannerController::class, 'showClassroom'])->name('classrooms.show');
                Route::delete('/classrooms/{classroom}', [AdminScannerController::class, 'destroyClassroom'])->name('classrooms.destroy');
            });

            Route::prefix('products/queue')->name('queue.')->group(function () {
                Route::get('/', [AdminQueueController::class, 'dashboard'])->name('dashboard');
                Route::get('/queues', [AdminQueueController::class, 'index'])->name('index');
                Route::delete('/queues/{queue}', [AdminQueueController::class, 'destroy'])->name('destroy');
                Route::get('/plans', [AdminQueueController::class, 'plans'])->name('plans');
                Route::post('/plans', [AdminQueueController::class, 'updatePlans'])->name('plans.update');
                Route::post('/settings', [AdminQueueController::class, 'updateSettings'])->name('settings');
                Route::post('/tts-test', [AdminQueueController::class, 'testTts'])->name('tts-test');
                Route::post('/billing-settings', [AdminQueueController::class, 'updateBilling'])->name('billing-settings');
                Route::post('/slip-test', [AdminQueueController::class, 'testSlip'])->name('slip-test');
                Route::get('/payments', [AdminQueueController::class, 'payments'])->name('payments');
                Route::get('/payments/{payment}/slip', [AdminQueueController::class, 'slip'])->name('payments.slip');
                Route::post('/payments/{payment}/approve', [AdminQueueController::class, 'approvePayment'])->name('payments.approve');
                Route::post('/payments/{payment}/reject', [AdminQueueController::class, 'rejectPayment'])->name('payments.reject');
            });

            Route::prefix('products/accounting')->name('accounting.')->group(function () {
                Route::get('/', [AdminAccountingController::class, 'dashboard'])->name('dashboard');
                Route::get('/users', [AdminAccountingController::class, 'users'])->name('users');
                Route::post('/users', [AdminAccountingController::class, 'storeUser'])->name('users.store');
                Route::delete('/users/{accountingUser}', [AdminAccountingController::class, 'destroyUser'])->name('users.destroy');
            });

            Route::prefix('products/social')->name('social.')->group(function () {
                Route::get('/', [AdminSocialController::class, 'dashboard'])->name('dashboard');
                Route::get('/feeds', [AdminSocialController::class, 'feeds'])->name('feeds');
                Route::post('/feeds', [AdminSocialController::class, 'storeFeed'])->name('feeds.store');
                Route::patch('/feeds/{feed}/toggle', [AdminSocialController::class, 'toggleFeed'])->name('feeds.toggle');
                Route::delete('/feeds/{feed}', [AdminSocialController::class, 'destroyFeed'])->name('feeds.destroy');
                Route::get('/posts', [AdminSocialController::class, 'posts'])->name('posts');
                Route::get('/posts/{post}', [AdminSocialController::class, 'showPost'])->name('posts.show');
                Route::patch('/posts/{post}', [AdminSocialController::class, 'updatePost'])->name('posts.update');
                Route::post('/posts/{post}/approve', [AdminSocialController::class, 'approvePost'])->name('posts.approve');
                Route::post('/posts/{post}/reject', [AdminSocialController::class, 'rejectPost'])->name('posts.reject');
                Route::post('/posts/{post}/publish', [AdminSocialController::class, 'publishPost'])->name('posts.publish');
                Route::post('/posts/{post}/retry', [AdminSocialController::class, 'retryPost'])->name('posts.retry');
                Route::delete('/posts/{post}', [AdminSocialController::class, 'destroyPost'])->name('posts.destroy');
                Route::get('/settings', [AdminSocialController::class, 'settings'])->name('settings');
                Route::patch('/settings', [AdminSocialController::class, 'updateSettings'])->name('settings.update');
                Route::post('/fetch-now', [AdminSocialController::class, 'fetchNow'])->name('fetch-now');
            });

            // Back-compat redirects for the old flat URLs.
            Route::get('/classrooms', fn () => redirect()->route('admin.scanner.classrooms', request()->query()));
            Route::get('/classrooms/{classroom}', fn (Classroom $classroom) => redirect()->route('admin.scanner.classrooms.show', $classroom));
        });
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/feedback', [FeedbackController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('feedback.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/impersonate/stop', [AdminController::class, 'stopImpersonating'])->name('impersonate.stop');

    Route::get('/two-factor-challenge', [TwoFactorAuthController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorAuthController::class, 'verifyChallenge'])->middleware('throttle:6,1')->name('two-factor.verify');

    Route::post('/user/two-factor/enable', [TwoFactorAuthController::class, 'enable'])->name('two-factor.enable');
    Route::post('/user/two-factor/confirm', [TwoFactorAuthController::class, 'confirm'])->name('two-factor.confirm');
    Route::delete('/user/two-factor', [TwoFactorAuthController::class, 'disable'])->name('two-factor.disable');
    Route::post('/user/two-factor/recovery-codes', [TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes');
});

// Accounting portal — completely separate auth from the main platform.
// Guest routes (login/logout) come first so they are reachable before auth.
Route::middleware('product:accounting')->prefix('accounting')->name('accounting.')->group(function () {
    // Public — no accounting auth required
    Route::get('/login', [AccountingAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AccountingAuthController::class, 'login'])->name('login.store');
    Route::post('/logout', [AccountingAuthController::class, 'logout'])->name('logout');

    // Protected — must be authenticated via the accounting guard
    Route::middleware('auth.accounting')->group(function () {
        Route::get('/', [AccountingDashboardController::class, 'index'])->name('dashboard');
        Route::get('/onboarding', [AccountingOnboardingController::class, 'show'])->name('onboarding');
        Route::post('/onboarding', [AccountingOnboardingController::class, 'store'])->name('onboarding.store');
        Route::get('/reports', [AccountingReportController::class, 'index'])->name('reports');
        Route::get('/reports/tax', [AccountingReportController::class, 'tax'])->name('reports.tax');
        Route::get('/reports/aged-ar', [AccountingReportController::class, 'agedReceivables'])->name('reports.aged-ar');
        Route::get('/reports/aged-ap', [AccountingReportController::class, 'agedPayables'])->name('reports.aged-ap');
        Route::get('/reports/sales-by-partner', [AccountingReportController::class, 'salesByPartner'])->name('reports.sales-by-partner');
        Route::get('/reports/purchases-by-partner', [AccountingReportController::class, 'purchasesByPartner'])->name('reports.purchases-by-partner');
        Route::get('/reports/partner-statement/{partner}', [AccountingReportController::class, 'partnerStatement'])->name('reports.partner-statement');
        Route::get('/reports/pnl-by-department', [AccountingReportController::class, 'profitAndLossByDepartment'])->name('reports.pnl-by-department');
        Route::get('/reports/budget-vs-actual', [AccountingReportController::class, 'budgetVsActual'])->name('reports.budget-vs-actual');
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
        Route::get('/partners/{partner}/edit', [AccountingPartnerController::class, 'edit'])->name('partners.edit');
        Route::put('/partners/{partner}', [AccountingPartnerController::class, 'update'])->name('partners.update');
        Route::delete('/partners/{partner}', [AccountingPartnerController::class, 'destroy'])->name('partners.destroy');

        Route::get('/departments', [AccountingDepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/create', [AccountingDepartmentController::class, 'create'])->name('departments.create');
        Route::post('/departments', [AccountingDepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/{department}/edit', [AccountingDepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('/departments/{department}', [AccountingDepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [AccountingDepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('/budgets', [AccountingBudgetController::class, 'index'])->name('budgets.index');
        Route::get('/budgets/create', [AccountingBudgetController::class, 'create'])->name('budgets.create');
        Route::post('/budgets', [AccountingBudgetController::class, 'store'])->name('budgets.store');
        Route::get('/budgets/{budget}/edit', [AccountingBudgetController::class, 'edit'])->name('budgets.edit');
        Route::put('/budgets/{budget}', [AccountingBudgetController::class, 'update'])->name('budgets.update');
        Route::delete('/budgets/{budget}', [AccountingBudgetController::class, 'destroy'])->name('budgets.destroy');

        Route::get('/invoices', [AccountingInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/create', [AccountingInvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices', [AccountingInvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}', [AccountingInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/edit', [AccountingInvoiceController::class, 'edit'])->name('invoices.edit');
        Route::put('/invoices/{invoice}', [AccountingInvoiceController::class, 'update'])->name('invoices.update');
        Route::post('/invoices/{invoice}/issue', [AccountingInvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('/invoices/{invoice}/void', [AccountingInvoiceController::class, 'void'])->name('invoices.void');
        Route::post('/invoices/{invoice}/receipts', [AccountingInvoiceController::class, 'recordReceipt'])->name('invoices.receipts.store');

        Route::get('/bills', [AccountingBillController::class, 'index'])->name('bills.index');
        Route::get('/bills/create', [AccountingBillController::class, 'create'])->name('bills.create');
        Route::post('/bills', [AccountingBillController::class, 'store'])->name('bills.store');
        Route::get('/bills/{bill}', [AccountingBillController::class, 'show'])->name('bills.show');
        Route::get('/bills/{bill}/edit', [AccountingBillController::class, 'edit'])->name('bills.edit');
        Route::put('/bills/{bill}', [AccountingBillController::class, 'update'])->name('bills.update');
        Route::post('/bills/{bill}/post', [AccountingBillController::class, 'post'])->name('bills.post');
        Route::post('/bills/{bill}/void', [AccountingBillController::class, 'void'])->name('bills.void');
        Route::post('/bills/{bill}/payments', [AccountingBillController::class, 'recordPayment'])->name('bills.payments.store');

        Route::get('/invoices/{invoice}/print', [AccountingInvoiceController::class, 'print'])->name('invoices.print');
        Route::get('/bills/{bill}/print', [AccountingBillController::class, 'print'])->name('bills.print');
        Route::get('/wht-certificates', [AccountingWhtCertificateController::class, 'index'])->name('wht-certificates.index');
        Route::get('/wht-certificates/{certificate}/print', [AccountingWhtCertificateController::class, 'print'])->name('wht-certificates.print');

        // Accounting periods + year-end close
        Route::get('/periods', [AccountingPeriodController::class, 'index'])->name('periods.index');
        Route::post('/periods', [AccountingPeriodController::class, 'store'])->name('periods.store');
        Route::post('/periods/{period}/close', [AccountingPeriodController::class, 'close'])->name('periods.close');
        Route::post('/periods/{period}/reopen', [AccountingPeriodController::class, 'reopen'])->name('periods.reopen');
        Route::post('/periods/year-end-close', [AccountingPeriodController::class, 'yearEndClose'])->name('periods.year-end-close');

        // Bank reconciliation
        Route::get('/bank-reconciliation', [AccountingBankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
        Route::post('/bank-reconciliation/lines', [AccountingBankReconciliationController::class, 'storeLine'])->name('bank-reconciliation.lines.store');
        Route::post('/bank-reconciliation/import', [AccountingBankReconciliationController::class, 'importCsv'])->name('bank-reconciliation.import');
        Route::post('/bank-reconciliation/{statement}/match', [AccountingBankReconciliationController::class, 'match'])->name('bank-reconciliation.match');
        Route::post('/bank-reconciliation/{statement}/unmatch', [AccountingBankReconciliationController::class, 'unmatch'])->name('bank-reconciliation.unmatch');
        Route::delete('/bank-reconciliation/{statement}', [AccountingBankReconciliationController::class, 'destroy'])->name('bank-reconciliation.destroy');

        // Recurring journals
        Route::get('/recurring-journals', [AccountingRecurringJournalController::class, 'index'])->name('recurring-journals.index');
        Route::get('/recurring-journals/create', [AccountingRecurringJournalController::class, 'create'])->name('recurring-journals.create');
        Route::post('/recurring-journals', [AccountingRecurringJournalController::class, 'store'])->name('recurring-journals.store');
        Route::get('/recurring-journals/{recurringJournal}/edit', [AccountingRecurringJournalController::class, 'edit'])->name('recurring-journals.edit');
        Route::put('/recurring-journals/{recurringJournal}', [AccountingRecurringJournalController::class, 'update'])->name('recurring-journals.update');
        Route::post('/recurring-journals/{recurringJournal}/run', [AccountingRecurringJournalController::class, 'run'])->name('recurring-journals.run');
        Route::post('/recurring-journals/{recurringJournal}/toggle', [AccountingRecurringJournalController::class, 'toggle'])->name('recurring-journals.toggle');
        Route::delete('/recurring-journals/{recurringJournal}', [AccountingRecurringJournalController::class, 'destroy'])->name('recurring-journals.destroy');

        Route::get('/manual-journals', [AccountingManualJournalController::class, 'index'])->name('manual-journals.index');
        Route::get('/manual-journals/create', [AccountingManualJournalController::class, 'create'])->name('manual-journals.create');
        Route::post('/manual-journals', [AccountingManualJournalController::class, 'store'])->name('manual-journals.store');
        Route::get('/manual-journals/{manualJournal}', [AccountingManualJournalController::class, 'show'])->name('manual-journals.show');
        Route::post('/manual-journals/{manualJournal}/void', [AccountingManualJournalController::class, 'void'])->name('manual-journals.void');

        // Fixed assets
        Route::get('/fixed-assets', [AccountingFixedAssetController::class, 'index'])->name('fixed-assets.index');
        Route::get('/fixed-assets/create', [AccountingFixedAssetController::class, 'create'])->name('fixed-assets.create');
        Route::post('/fixed-assets', [AccountingFixedAssetController::class, 'store'])->name('fixed-assets.store');
        Route::get('/fixed-assets/{fixedAsset}', [AccountingFixedAssetController::class, 'show'])->name('fixed-assets.show');
        Route::post('/fixed-assets/{fixedAsset}/depreciate', [AccountingFixedAssetController::class, 'depreciate'])->name('fixed-assets.depreciate');
        Route::post('/fixed-assets/{fixedAsset}/dispose', [AccountingFixedAssetController::class, 'dispose'])->name('fixed-assets.dispose');

        // Inventory (products + stock movements)
        Route::get('/products', [AccountingProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [AccountingProductController::class, 'create'])->name('products.create');
        Route::post('/products', [AccountingProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [AccountingProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [AccountingProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [AccountingProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AccountingProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/receive', [AccountingProductController::class, 'receive'])->name('products.receive');
        Route::post('/products/{product}/issue', [AccountingProductController::class, 'issue'])->name('products.issue');
        Route::post('/products/{product}/adjust', [AccountingProductController::class, 'adjust'])->name('products.adjust');

        // Payroll
        Route::get('/payroll/employees', [AccountingPayrollController::class, 'employees'])->name('payroll.employees');
        Route::post('/payroll/employees', [AccountingPayrollController::class, 'storeEmployee'])->name('payroll.employees.store');
        Route::get('/payroll/employees/{employee}/edit', [AccountingPayrollController::class, 'editEmployee'])->name('payroll.employees.edit');
        Route::patch('/payroll/employees/{employee}', [AccountingPayrollController::class, 'updateEmployee'])->name('payroll.employees.update');
        Route::post('/payroll/employees/{employee}/toggle', [AccountingPayrollController::class, 'toggleEmployee'])->name('payroll.employees.toggle');
        Route::get('/payroll/runs', [AccountingPayrollController::class, 'runs'])->name('payroll.runs.index');
        Route::get('/payroll/runs/create', [AccountingPayrollController::class, 'createRun'])->name('payroll.runs.create');
        Route::post('/payroll/runs', [AccountingPayrollController::class, 'storeRun'])->name('payroll.runs.store');
        Route::get('/payroll/runs/{payrollRun}', [AccountingPayrollController::class, 'showRun'])->name('payroll.runs.show');
        Route::patch('/payroll/items/{payrollItem}', [AccountingPayrollController::class, 'updateItem'])->name('payroll.items.update');
        Route::post('/payroll/runs/{payrollRun}/post', [AccountingPayrollController::class, 'postRun'])->name('payroll.runs.post');
        Route::get('/payroll/items/{payrollItem}/payslip', [AccountingPayrollController::class, 'payslip'])->name('payroll.items.payslip');

        // Accounting user management
        Route::get('/users', [AccountingUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AccountingUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{accountingUser}', [AccountingUserController::class, 'update'])->name('users.update');
        Route::post('/users/{accountingUser}/reset-password', [AccountingUserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{accountingUser}', [AccountingUserController::class, 'destroy'])->name('users.destroy');

        // Audit log
        Route::get('/audit-log', [AccountingAuditLogController::class, 'index'])->name('audit-log.index');

        // Change own password
        Route::get('/password', [AccountingAuthController::class, 'showChangePassword'])->name('password.edit');
        Route::post('/password', [AccountingAuthController::class, 'changePassword'])->name('password.update');

        // Document attachments
        Route::post('/attachments/{type}/{id}', [AccountingAttachmentController::class, 'store'])->name('attachments.store');
        Route::get('/attachments/{attachment}/download', [AccountingAttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('/attachments/{attachment}', [AccountingAttachmentController::class, 'destroy'])->name('attachments.destroy');

        // Approval workflow
        Route::get('/approvals', [AccountingApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{type}/{id}/request', [AccountingApprovalController::class, 'request'])->name('approvals.request');
        Route::post('/approvals/{approval}/approve', [AccountingApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{approval}/reject', [AccountingApprovalController::class, 'reject'])->name('approvals.reject');
    });
});

require __DIR__.'/auth.php';
