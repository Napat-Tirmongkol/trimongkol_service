<?php

use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\Products\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\Products\ScannerController as AdminScannerController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SystemController as AdminSystemController;
use App\Http\Controllers\Admin\WorkspaceController as AdminWorkspaceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ClassroomController;
use App\Models\Classroom;
use App\Http\Controllers\ContactController;
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

// Admin portal (separate login from the regular Breeze /login)
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminLoginController::class, 'store'])->name('admin.login.store');
});
Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('admin.logout');

// Authenticated app — the free Homework Scanner product
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/scanner', [ClassroomController::class, 'index'])->name('dashboard');

    Route::get('/plans', [PlansController::class, 'index'])->name('plans.index');

    // Queue System product — workspace-scoped. Counter staff operate here;
    // customers pull tickets on the public /q/{token} page (registered below).
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
            Route::post('/billing', [AdminController::class, 'updateBilling'])->name('billing.update');
            Route::delete('/workspaces/{workspace}', [AdminWorkspaceController::class, 'destroy'])->name('workspaces.destroy');
        });

        Route::middleware('can:leads.view')->group(function () {
            Route::get('/leads', [AdminLeadController::class, 'index'])->name('leads.index');
            Route::get('/leads/{lead}', [AdminLeadController::class, 'show'])->name('leads.show');
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
        });

        Route::middleware('can:system.manage')->group(function () {
            Route::get('/system', [AdminSystemController::class, 'index'])->name('system');
            Route::post('/system/pull', [AdminSystemController::class, 'pull'])->name('system.pull');
            Route::post('/system/migrate', [AdminSystemController::class, 'migrate'])->name('system.migrate');
            Route::post('/system/clear-cache', [AdminSystemController::class, 'clearCache'])->name('system.clear-cache');
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
                Route::post('/settings', [AdminQueueController::class, 'updateSettings'])->name('settings');
                Route::post('/tts-test', [AdminQueueController::class, 'testTts'])->name('tts-test');
                Route::post('/billing-settings', [AdminQueueController::class, 'updateBilling'])->name('billing-settings');
                Route::get('/payments', [AdminQueueController::class, 'payments'])->name('payments');
                Route::get('/payments/{payment}/slip', [AdminQueueController::class, 'slip'])->name('payments.slip');
                Route::post('/payments/{payment}/approve', [AdminQueueController::class, 'approvePayment'])->name('payments.approve');
                Route::post('/payments/{payment}/reject', [AdminQueueController::class, 'rejectPayment'])->name('payments.reject');
            });

            // Back-compat redirects for the old flat URLs.
            Route::get('/classrooms', fn () => redirect()->route('admin.scanner.classrooms', request()->query()));
            Route::get('/classrooms/{classroom}', fn (Classroom $classroom) => redirect()->route('admin.scanner.classrooms.show', $classroom));
        });
    });
});

Route::middleware('auth')->group(function () {
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

require __DIR__.'/auth.php';
