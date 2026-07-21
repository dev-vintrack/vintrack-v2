<?php

use App\Presentation\Http\Controllers\Web\Admin\AdminConsultationController;
use App\Presentation\Http\Controllers\Web\Admin\AdminInventoryController;
use App\Presentation\Http\Controllers\Web\Admin\AdminMenuPermissionController;
use App\Presentation\Http\Controllers\Web\Admin\AdminPackageController;
use App\Presentation\Http\Controllers\Web\Admin\AdminProviderController;
use App\Presentation\Http\Controllers\Web\Admin\AdminPurchaseItemController;
use App\Presentation\Http\Controllers\Web\Admin\AdminUserController;
use App\Presentation\Http\Controllers\Web\Admin\AdminVehicleController;
use App\Presentation\Http\Controllers\Web\Admin\CreditPurchaseController;
use App\Presentation\Http\Controllers\Web\Admin\AdminWalletController;
use App\Presentation\Http\Controllers\Web\ConsultationController;
use App\Presentation\Http\Controllers\Web\HomeController;
use App\Presentation\Http\Controllers\Web\LoginController;
use App\Presentation\Http\Controllers\Web\RegisterController;
use App\Presentation\Http\Controllers\Web\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('home');
    });
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/cliente', [HomeController::class, 'cliente'])->name('home.cliente');
    Route::get('/home/perito', [HomeController::class, 'perito'])->name('home.perito');
    Route::get('/home/oficial', [HomeController::class, 'oficial'])->name('home.oficial');
    Route::get('/home/ocasional', [HomeController::class, 'ocasional'])->name('home.ocasional');
    Route::get('/home/pending', [HomeController::class, 'pending'])->name('home.pending');

    Route::post('/consult', [ConsultationController::class, 'consult'])->name('consult');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{id}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

    Route::prefix('admin')->middleware(['role:admin,analista,soporte'])->group(function () {
        Route::get('/providers', [AdminProviderController::class, 'index'])->name('admin.providers.index');
        Route::post('/providers/{id}', [AdminProviderController::class, 'updateProvider'])->name('admin.providers.update');
        Route::post('/providers/{providerId}/services/{serviceId}', [AdminProviderController::class, 'updateService'])->name('admin.services.update');
        Route::post('/providers/{providerId}/services/{serviceId}/sections/{sectionId}', [AdminProviderController::class, 'updateSection'])->name('admin.sections.update');
        Route::post('/providers/{providerId}/services/{serviceId}/sections/{sectionId}/roles/{role}', [AdminProviderController::class, 'updateSectionRole'])->name('admin.section-roles.update');

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

    Route::prefix('admin')->middleware(['role:admin,soporte'])->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::put('/users/{id}/approve', [AdminUserController::class, 'approve'])->name('admin.users.approve');
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

        Route::get('/consultations', [AdminConsultationController::class, 'index'])->name('admin.consultations.index');

        Route::get('/wallets', [AdminWalletController::class, 'index'])->name('admin.wallets.index');
        Route::get('/wallets/movements', [AdminWalletController::class, 'movements'])->name('admin.wallets.movements');

        Route::get('/packages/active', [AdminPackageController::class, 'active'])->name('admin.packages.active');

        Route::get('/purchases', [AdminPurchaseItemController::class, 'index'])->name('admin.purchases.index');
        Route::get('/purchases/create', [AdminPurchaseItemController::class, 'create'])->name('admin.purchases.create');
        Route::post('/purchases', [AdminPurchaseItemController::class, 'store'])->name('admin.purchases.store');
        Route::get('/purchases/{id}/edit', [AdminPurchaseItemController::class, 'edit'])->name('admin.purchases.edit');
        Route::put('/purchases/{id}', [AdminPurchaseItemController::class, 'update'])->name('admin.purchases.update');
        Route::delete('/purchases/{id}', [AdminPurchaseItemController::class, 'destroy'])->name('admin.purchases.destroy');

        Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('admin.inventory.index');
        Route::post('/inventory/adjustment', [AdminInventoryController::class, 'storeAdjustment'])->name('admin.inventory.adjustment.store');
        Route::post('/inventory/return-expired', [AdminInventoryController::class, 'returnExpired'])->name('admin.inventory.return-expired');

        Route::get('/vehicles', [AdminVehicleController::class, 'index'])->name('admin.vehicles.index');
    });

    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        Route::get('/menu-permissions', [AdminMenuPermissionController::class, 'index'])->name('admin.menu-permissions.index');
        Route::put('/menu-permissions', [AdminMenuPermissionController::class, 'update'])->name('admin.menu-permissions.update');
    });
});
