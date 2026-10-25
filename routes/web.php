<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\BusinessDashboardController;
use App\Http\Controllers\BusinessApplicationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\LanguageController;

Route::view('/', 'main.mainpage')->name('home');
Route::get('/business', BusinessDashboardController::class)->middleware('auth')->name('business.dashboard');
Route::post('/business/resubmit', [BusinessApplicationController::class, 'resubmit'])->middleware('auth')->name('business.resubmit');
Route::redirect('/admin', '/login')->name('admin.entry');
Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->middleware('auth:admin')->name('admin.dashboard');
Route::post('/admin/business/{business}/approve', [AdminDashboardController::class, 'approve'])->middleware('auth:admin')->name('admin.business.approve');
Route::post('/admin/business/{business}/reject', [AdminDashboardController::class, 'reject'])->middleware('auth:admin')->name('admin.business.reject');
Route::get('/admin/business/{business}/documents/{document}', [AdminDashboardController::class, 'document'])->middleware('auth:admin')->name('admin.business.document');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
Route::post('/language', [LanguageController::class, 'update'])->name('language.update');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
