<?php

use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\Products\ScannerController as AdminScannerController;
use App\Http\Controllers\Admin\SystemController as AdminSystemController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ClassroomController;
use App\Models\Classroom;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\GradebookController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

// Marketing site
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->where('locale', 'th|en')
    ->name('locale.switch');

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

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        // Platform-wide tools (cut across all products)
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
        Route::get('/security', [AdminController::class, 'security'])->name('security');

        Route::get('/leads', [AdminLeadController::class, 'index'])->name('leads.index');
        Route::get('/leads/{lead}', [AdminLeadController::class, 'show'])->name('leads.show');
        Route::patch('/leads/{lead}', [AdminLeadController::class, 'update'])->name('leads.update');
        Route::delete('/leads/{lead}', [AdminLeadController::class, 'destroy'])->name('leads.destroy');

        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/export', [AdminController::class, 'exportUsers'])->name('users.export');
        Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
        Route::post('/users/{user}/toggle-admin', [AdminController::class, 'toggleAdmin'])->name('users.toggle-admin');
        Route::post('/users/{user}/toggle-active', [AdminController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{user}/password-reset', [AdminController::class, 'sendPasswordReset'])->name('users.password-reset');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::post('/users/{user}/impersonate', [AdminController::class, 'impersonate'])->name('users.impersonate');
        Route::get('/site', [SiteSettingsController::class, 'edit'])->name('site-settings.edit');
        Route::patch('/site', [SiteSettingsController::class, 'update'])->name('site-settings.update');

        Route::get('/system', [AdminSystemController::class, 'index'])->name('system');
        Route::post('/system/pull', [AdminSystemController::class, 'pull'])->name('system.pull');
        Route::post('/system/migrate', [AdminSystemController::class, 'migrate'])->name('system.migrate');
        Route::post('/system/clear-cache', [AdminSystemController::class, 'clearCache'])->name('system.clear-cache');

        // Product-specific moderation. New products get a sibling group here +
        // an entry in config/admin-products.php and the nav picks them up.
        Route::prefix('products/scanner')->name('scanner.')->group(function () {
            Route::get('/', [AdminScannerController::class, 'dashboard'])->name('dashboard');
            Route::get('/classrooms', [AdminScannerController::class, 'classrooms'])->name('classrooms');
            Route::get('/classrooms/{classroom}', [AdminScannerController::class, 'showClassroom'])->name('classrooms.show');
            Route::delete('/classrooms/{classroom}', [AdminScannerController::class, 'destroyClassroom'])->name('classrooms.destroy');
        });

        // Back-compat redirects for the old flat URLs.
        Route::get('/classrooms', fn () => redirect()->route('admin.scanner.classrooms', request()->query()));
        Route::get('/classrooms/{classroom}', fn (Classroom $classroom) => redirect()->route('admin.scanner.classrooms.show', $classroom));
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/impersonate/stop', [AdminController::class, 'stopImpersonating'])->name('impersonate.stop');
});

require __DIR__.'/auth.php';
