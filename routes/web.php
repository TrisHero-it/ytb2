<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\GuideController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::get('/api/families/{family}/history', [FamilyController::class, 'history'])->name('families.history');
Route::get('/guide', [GuideController::class, 'index'])->name('guide');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::resource('families', FamilyController::class)->except(['show']);
    Route::post('/families/{family}/quick-pay', [FamilyController::class, 'quickPay'])->name('families.quick-pay');
    Route::get('/api/families/check-member-email', [FamilyController::class, 'checkMemberEmail'])->name('families.check-member-email');
    Route::resource('collaborators', CollaboratorController::class)->except(['show']);
});
