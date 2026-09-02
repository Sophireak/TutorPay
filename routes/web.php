<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\GenerateFeesController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('students', StudentController::class);
    Route::patch('/students/{student}/toggle', [StudentController::class, 'toggle'])->name('students.toggle');

    Route::get('/fees', [FeeController::class, 'index'])->name('fees.index');
    Route::post('/fees', [FeeController::class, 'store'])->name('fees.store');
    Route::delete('/fees/{fee}', [FeeController::class, 'destroy'])->name('fees.destroy');
    Route::post('/fees/generate', GenerateFeesController::class)->name('fees.generate');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');

    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
