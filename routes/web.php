<?php

use App\Presentation\Http\Controllers\Web\Admin\AdminPackageController;
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

        Route::get('/packages', [AdminPackageController::class, 'index'])->name('admin.packages.index');
        Route::get('/packages/create', [AdminPackageController::class, 'create'])->name('admin.packages.create');
        Route::post('/packages', [AdminPackageController::class, 'store'])->name('admin.packages.store');
        Route::get('/packages/{id}/edit', [AdminPackageController::class, 'edit'])->name('admin.packages.edit');
        Route::put('/packages/{id}', [AdminPackageController::class, 'update'])->name('admin.packages.update');
        Route::delete('/packages/{id}', [AdminPackageController::class, 'destroy'])->name('admin.packages.destroy');
        Route::get('/packages/assign', [AdminPackageController::class, 'assignForm'])->name('admin.packages.assign');
        Route::post('/packages/assign', [AdminPackageController::class, 'assign'])->name('admin.packages.assign.store');
    });
});
