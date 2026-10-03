<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::patch('/companies/{id}/restore', [CompanyController::class, 'restore'])
        ->name('companies.restore');
    Route::resource('companies', CompanyController::class)->only([
        'index', 'store', 'update', 'destroy',
    ]);

    Route::patch('/users/{id}/restore', [UserController::class, 'restore'])
        ->name('users.restore');
    Route::resource('users', UserController::class)->only([
        'index', 'store', 'update', 'destroy',
    ]);

    Route::patch('/subscriptions/{id}/restore', [SubscriptionController::class, 'restore'])
        ->name('subscriptions.restore');
    Route::resource('subscriptions', SubscriptionController::class)->only([
        'index', 'store', 'update', 'destroy',
    ]);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
