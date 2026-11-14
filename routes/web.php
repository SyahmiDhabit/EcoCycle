<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BusinessApplicationController;
use App\Http\Controllers\BusinessDashboardController;
use App\Http\Controllers\BusinessNotificationController;
use App\Http\Controllers\BusinessProfileController;
use App\Http\Controllers\EcoCycleHelperController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\MyListingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WastageMonitoringController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::view('/', 'main.mainpage')->name('home');

// Business Pending Approval & Application Management (Accessible to unapproved business)
Route::redirect('/business/verification', '/business/pending-approval');
Route::middleware('auth')->group(function () {
    Route::get('/business/pending-approval', [BusinessApplicationController::class, 'pending'])->name('business.pending');
    Route::get('/business/application', [BusinessApplicationController::class, 'show'])->name('business.application');
    Route::post('/business/application', [BusinessApplicationController::class, 'update'])->name('business.application.update');
    Route::delete('/business/application', [BusinessApplicationController::class, 'destroy'])->name('business.application.destroy');
    Route::get('/business/application/documents/{document}', [BusinessApplicationController::class, 'document'])->name('business.application.document');
    Route::post('/business/resubmit', [BusinessApplicationController::class, 'resubmit'])->name('business.resubmit');
});

// Protected Business Workspace & Dashboard (Strictly requires administrator approval)
Route::middleware(['auth', 'business.approved'])->group(function () {
    Route::get('/business', BusinessDashboardController::class)->name('business.dashboard');
    Route::get('/business/directory/{company}', [BusinessDashboardController::class, 'show'])->name('business.directory.show');
    Route::get('/business/marketplace', [MarketplaceController::class, 'index'])->name('business.marketplace');
    Route::post('/business/marketplace', [MarketplaceController::class, 'store'])->name('business.marketplace.store');
    Route::post('/business/marketplace/{listing}/exchange', [TransactionController::class, 'store'])->name('business.marketplace.exchange');
    Route::delete('/business/marketplace/{listing}', [MarketplaceController::class, 'destroy'])->name('business.marketplace.destroy');
    Route::get('/business/inventory', [InventoryController::class, 'index'])->name('business.inventory');
    Route::post('/business/inventory', [InventoryController::class, 'store'])->name('business.inventory.store');
    Route::put('/business/inventory/{item}', [InventoryController::class, 'update'])->name('business.inventory.update');
    Route::post('/business/inventory/{item}/adjust', [InventoryController::class, 'adjust'])->name('business.inventory.adjust');
    Route::delete('/business/inventory/{item}', [InventoryController::class, 'destroy'])->name('business.inventory.destroy');

    // Waste Monitoring Routes
    Route::get('/business/waste-monitoring', [WastageMonitoringController::class, 'index'])->name('business.waste.index');
    Route::post('/business/waste-monitoring', [WastageMonitoringController::class, 'store'])->name('business.waste.store');
    Route::put('/business/waste-monitoring/{record}', [WastageMonitoringController::class, 'updateStatus'])->name('business.waste.update');
    Route::post('/business/waste-monitoring/from-inventory/{item}', [WastageMonitoringController::class, 'logFromInventory'])->name('business.waste.from-inventory');
    Route::delete('/business/waste-monitoring/{record}', [WastageMonitoringController::class, 'destroy'])->name('business.waste.destroy');

    // Transactions & Invoicing Routes
    Route::get('/business/transactions', [TransactionController::class, 'index'])->name('business.transactions');
    Route::post('/business/transactions/{exchange}/request-payment', [TransactionController::class, 'requestPayment'])->name('business.transactions.request-payment');
    Route::post('/business/transactions/{exchange}/mark-paid', [TransactionController::class, 'markPaid'])->name('business.transactions.mark-paid');

    // My Listings & Reviews
    Route::get('/business/my-listings', [MyListingController::class, 'index'])->name('business.listings');
    Route::post('/business/my-listings/{listing}/status', [MyListingController::class, 'updateStatus'])->name('business.listings.status');
    Route::post('/business/my-listings/{listing}/reviews', [MyListingController::class, 'storeReview'])->name('business.listings.reviews');

    // Reports & Financial Analytics
    Route::get('/business/reports', [ReportController::class, 'index'])->name('business.reports');

    // Business Profile & Account Security
    Route::get('/business/profile', [BusinessProfileController::class, 'show'])->name('business.profile');
    Route::put('/business/profile', [BusinessProfileController::class, 'updateProfile'])->name('business.profile.update');
    Route::put('/business/profile/password', [BusinessProfileController::class, 'updatePassword'])->name('business.profile.password');

    // EcoCycle Helper (AI Chatbot, Material Recommendations & GPS Recycling Centre Locator)
    Route::get('/business/helper', [EcoCycleHelperController::class, 'index'])->name('business.helper');
    Route::post('/business/helper/chat', [EcoCycleHelperController::class, 'chat'])->name('business.helper.chat');
    Route::get('/business/helper/analyze/{item}', [EcoCycleHelperController::class, 'analyzeItem'])->name('business.helper.analyze');
    Route::post('/business/helper/nearby-centres', [EcoCycleHelperController::class, 'nearbyCentres'])->name('business.helper.nearby');

    Route::post('/business/notifications/mark-read', [BusinessNotificationController::class, 'markAllAsRead'])->name('business.notifications.mark-read');
});
Route::redirect('/admin', '/admin/dashboard')->name('admin.entry');
Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->middleware('auth:admin')->name('admin.dashboard');
Route::post('/admin/notifications/mark-read', [AdminDashboardController::class, 'markAllNotificationsRead'])->middleware('auth:admin')->name('admin.notifications.mark-read');
Route::post('/admin/business/{business}/approve', [AdminDashboardController::class, 'approve'])->middleware('auth:admin')->name('admin.business.approve');
Route::post('/admin/business/{business}/request-changes', [AdminDashboardController::class, 'requestChanges'])->middleware('auth:admin')->name('admin.business.request-changes');
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
    $path = 'profile-images/'.$filename;
    $disk = Storage::disk('public');
    abort_unless($disk->exists($path), 404);

    return Response::make($disk->get($path), 200, [
        'Content-Type' => $disk->mimeType($path) ?: 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('profile.image');

Route::get('/listing-images/{filename}', function (string $filename) {
    $path = 'listing-images/'.$filename;
    $disk = Storage::disk('public');
    abort_unless($disk->exists($path), 404);

    return Response::make($disk->get($path), 200, [
        'Content-Type' => $disk->mimeType($path) ?: 'image/jpeg',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('listing.image');

Route::get('/inventory-images/{filename}', function (string $filename) {
    $path = 'inventory-images/'.$filename;
    $disk = Storage::disk('public');
    abort_unless($disk->exists($path), 404);

    return Response::make($disk->get($path), 200, [
        'Content-Type' => $disk->mimeType($path) ?: 'image/jpeg',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('inventory.image');

Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::get('/register/check-availability', [RegisteredUserController::class, 'checkAvailability'])->name('register.check');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
