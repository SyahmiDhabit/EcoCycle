<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\BusinessDashboardController;
use App\Http\Controllers\BusinessApplicationController;
use App\Http\Controllers\BusinessNotificationController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\LanguageController;

Route::view('/', 'main.mainpage')->name('home');
Route::get('/business', BusinessDashboardController::class)->middleware('auth')->name('business.dashboard');
Route::get('/business/directory/{company}', [BusinessDashboardController::class, 'show'])->middleware('auth')->name('business.directory.show');
Route::get('/business/marketplace', [MarketplaceController::class, 'index'])->middleware('auth')->name('business.marketplace');
Route::post('/business/marketplace', [MarketplaceController::class, 'store'])->middleware('auth')->name('business.marketplace.store');
Route::delete('/business/marketplace/{listing}', [MarketplaceController::class, 'destroy'])->middleware('auth')->name('business.marketplace.destroy');
Route::get('/business/inventory', [InventoryController::class, 'index'])->middleware('auth')->name('business.inventory');
Route::post('/business/inventory', [InventoryController::class, 'store'])->middleware('auth')->name('business.inventory.store');
Route::put('/business/inventory/{item}', [InventoryController::class, 'update'])->middleware('auth')->name('business.inventory.update');
Route::post('/business/inventory/{item}/adjust', [InventoryController::class, 'adjust'])->middleware('auth')->name('business.inventory.adjust');
Route::delete('/business/inventory/{item}', [InventoryController::class, 'destroy'])->middleware('auth')->name('business.inventory.destroy');
Route::get('/business/application', [BusinessApplicationController::class, 'show'])->middleware('auth')->name('business.application');
Route::post('/business/application', [BusinessApplicationController::class, 'update'])->middleware('auth')->name('business.application.update');
Route::delete('/business/application', [BusinessApplicationController::class, 'destroy'])->middleware('auth')->name('business.application.destroy');
Route::get('/business/application/documents/{document}', [BusinessApplicationController::class, 'document'])->middleware('auth')->name('business.application.document');
Route::post('/business/resubmit', [BusinessApplicationController::class, 'resubmit'])->middleware('auth')->name('business.resubmit');
Route::post('/business/notifications/mark-read', [BusinessNotificationController::class, 'markAllAsRead'])->middleware('auth')->name('business.notifications.mark-read');
Route::redirect('/admin', '/login')->name('admin.entry');
Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->middleware('auth:admin')->name('admin.dashboard');
Route::post('/admin/notifications/mark-read', [AdminDashboardController::class, 'markAllNotificationsRead'])->middleware('auth:admin')->name('admin.notifications.mark-read');
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
Route::get('/profile-images/{filename}', function (string $filename) {
    $path = 'profile-images/' . $filename;
    $disk = Storage::disk('public');
    abort_unless($disk->exists($path), 404);
    return Response::make($disk->get($path), 200, [
        'Content-Type' => $disk->mimeType($path) ?: 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('profile.image');

Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::get('/register/check-availability', [RegisteredUserController::class, 'checkAvailability'])->name('register.check');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
