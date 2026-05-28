<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\StudentController;
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

// Authenticated app
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ClassroomController::class, 'index'])->name('dashboard');

    Route::resource('classrooms', ClassroomController::class)->except(['index']);

    Route::resource('classrooms.students', StudentController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy']);

    Route::resource('classrooms.assignments', AssignmentController::class)
        ->only(['create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::get('classrooms/{classroom}/assignments/{assignment}/scan', [AssignmentController::class, 'scan'])
        ->name('classrooms.assignments.scan');

    Route::middleware('admin')->group(function () {
        Route::get('admin/site', [SiteSettingsController::class, 'edit'])->name('admin.site-settings.edit');
        Route::patch('admin/site', [SiteSettingsController::class, 'update'])->name('admin.site-settings.update');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
