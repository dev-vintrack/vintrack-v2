<?php

use App\Presentation\Http\Controllers\Web\Admin\AdminProviderController;
use App\Presentation\Http\Controllers\Web\Admin\CreditPurchaseController;
use App\Presentation\Http\Controllers\Web\ConsultationController;
use App\Presentation\Http\Controllers\Web\HomeController;
use App\Presentation\Http\Controllers\Web\LoginController;
use App\Presentation\Http\Controllers\Web\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('home');
    });
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/consult', [ConsultationController::class, 'consult'])->name('consult');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{id}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        Route::get('/providers', [AdminProviderController::class, 'index'])->name('admin.providers.index');
        Route::post('/providers/{id}', [AdminProviderController::class, 'updateProvider'])->name('admin.providers.update');
        Route::post('/providers/{providerId}/services/{serviceId}', [AdminProviderController::class, 'updateService'])->name('admin.services.update');

        Route::get('/credits/purchase', [CreditPurchaseController::class, 'create'])->name('admin.credits.purchase');
        Route::post('/credits/purchase', [CreditPurchaseController::class, 'store'])->name('admin.credits.purchase.store');
    });
});
