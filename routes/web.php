<?php

use App\Presentation\Http\Controllers\Web\Admin\AdminConsultationController;
use App\Presentation\Http\Controllers\Web\Admin\AdminInventoryController;
use App\Presentation\Http\Controllers\Web\Admin\AdminMenuPermissionController;
use App\Presentation\Http\Controllers\Web\Admin\AdminNotificationController;
use App\Presentation\Http\Controllers\Web\Admin\AdminNotificationCaseController;
use App\Presentation\Http\Controllers\Web\Admin\AdminPackageController;
use App\Presentation\Http\Controllers\Web\Admin\AdminProviderController;
use App\Presentation\Http\Controllers\Web\Admin\AdminProviderServiceRoleController;
use App\Presentation\Http\Controllers\Web\Admin\AdminPurchaseItemController;
use App\Presentation\Http\Controllers\Web\Admin\AdminRoleController;
use App\Presentation\Http\Controllers\Web\Admin\AdminRoleTypeController;
use App\Presentation\Http\Controllers\Web\Admin\AdminUserController;
use App\Presentation\Http\Controllers\Web\Admin\AdminVehicleController;
use App\Presentation\Http\Controllers\Web\Admin\AdminWalletController;
use App\Presentation\Http\Controllers\Web\Admin\CreditPurchaseController;
use App\Presentation\Http\Controllers\Web\Admin\CustomerMenuPermissionController;
use App\Presentation\Http\Controllers\Web\ConsultationController;
use App\Presentation\Http\Controllers\Web\CustomerAccountController;
use App\Presentation\Http\Controllers\Web\CustomerNotificationCaseController;
use App\Presentation\Http\Controllers\Web\ForgotPasswordController;
use App\Presentation\Http\Controllers\Web\HomeController;
use App\Presentation\Http\Controllers\Web\LoginController;
use App\Presentation\Http\Controllers\Web\NotificationCaseDocumentController;
use App\Presentation\Http\Controllers\Web\PortalNotificationController;
use App\Presentation\Http\Controllers\Web\RegisterController;
use App\Presentation\Http\Controllers\Web\ReportController;
use App\Presentation\Http\Controllers\Web\ResetPasswordController;
use App\Presentation\Http\Controllers\Web\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::get('/login/verificar', [LoginController::class, 'showVerifyForm'])->name('login.verify');
Route::post('/login/verificar', [LoginController::class, 'verifyOtp'])->name('login.verify.post');
Route::post('/login/reenviar', [LoginController::class, 'resendOtp'])->name('login.resend');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/registro', [RegisterController::class, 'showEmailForm'])->name('register');
Route::post('/registro', [RegisterController::class, 'sendEmailOtp'])->name('register.otp');
Route::get('/registro/verificar', [RegisterController::class, 'showVerifyForm'])->name('register.verify');
Route::post('/registro/verificar', [RegisterController::class, 'verifyEmailOtp'])->name('register.verify.post');
Route::post('/registro/reenviar', [RegisterController::class, 'resendEmailOtp'])->name('register.resend');
Route::get('/registro/datos', [RegisterController::class, 'showDataForm'])->name('register.data');
Route::post('/registro/datos', [RegisterController::class, 'store'])->name('register.store');
Route::get('/registro/exitoso', [RegisterController::class, 'showWhatsApp'])->name('register.whatsapp');

Route::get('/recuperar-contrasena', [ForgotPasswordController::class, 'showRequestForm'])->name('password.request');
Route::post('/recuperar-contrasena', [ForgotPasswordController::class, 'sendResetOtp'])->name('password.email');
Route::get('/recuperar-contrasena/verificar', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify');
Route::post('/recuperar-contrasena/verificar', [ForgotPasswordController::class, 'verifyResetOtp'])->name('password.verify.post');
Route::post('/recuperar-contrasena/reenviar', [ForgotPasswordController::class, 'resendResetOtp'])->name('password.resend');
Route::get('/recuperar-contrasena/nueva', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/recuperar-contrasena/nueva', [ResetPasswordController::class, 'reset'])->name('password.reset.post');

Route::get('/', [SiteController::class, 'home'])->name('site.home');
Route::get('/nosotros', [SiteController::class, 'about'])->name('site.about');
Route::get('/servicios', [SiteController::class, 'services'])->name('site.services');
Route::get('/capacitacion', [SiteController::class, 'training'])->name('site.training');
Route::get('/contacto', [SiteController::class, 'contact'])->name('site.contact');
Route::post('/contacto', [SiteController::class, 'sendContact'])->name('site.contact.send');
Route::get('/ventas', [SiteController::class, 'sales'])->name('site.sales');
Route::post('/ventas', [SiteController::class, 'sendSalesInquiry'])->name('site.sales.send');
Route::post('/decode-vin', [SiteController::class, 'decodeVin'])->name('site.decode-vin');

Route::middleware(['auth'])->group(function () {
    Route::prefix('notification-cases/{case}/documents')->name('notification-cases.documents.')->group(function () {
        Route::get('/', [NotificationCaseDocumentController::class, 'index'])->name('index');
        Route::post('/', [NotificationCaseDocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [NotificationCaseDocumentController::class, 'show'])->name('show');
        Route::delete('/{document}', [NotificationCaseDocumentController::class, 'destroy'])->name('destroy');
    });

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/cliente', [HomeController::class, 'cliente'])->name('home.cliente');
    Route::get('/home/perito', [HomeController::class, 'perito'])->name('home.perito');
    Route::get('/home/oficial', [HomeController::class, 'oficial'])->name('home.oficial');
    Route::get('/home/unidad-analisis', [HomeController::class, 'unidad_analisis'])->name('home.unidad_analisis');
    Route::get('/home/ocasional', [HomeController::class, 'ocasional'])->name('home.ocasional');
    Route::get('/home/pending', [HomeController::class, 'pending'])->name('home.pending');

    Route::post('/consult', [ConsultationController::class, 'consult'])->name('consult');
    Route::get('/reports/{id}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{id}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');

    Route::prefix('mi-cuenta')->middleware(['active.customer', 'customer.menu'])->group(function () {
        Route::get('/creditos', [CustomerAccountController::class, 'credits'])->name('customer.credits');
        Route::get('/movimientos', [CustomerAccountController::class, 'movements'])->name('customer.movements');
        Route::get('/consultas', [CustomerAccountController::class, 'consultations'])->name('customer.consultations');
        Route::get('/vin-decoder', [CustomerAccountController::class, 'vinDecoder'])->name('customer.vin-decoder');
        Route::get('/proceso-notificaciones', [CustomerNotificationCaseController::class, 'index'])->name('customer.notification-cases.index');
    });

    Route::get('mi-cuenta/consultas/datos', [CustomerAccountController::class, 'consultationData'])
        ->middleware(['active.customer'])->name('customer.consultations.data');

    Route::prefix('mi-cuenta/proceso-notificaciones')->middleware(['active.customer'])->name('customer.notification-cases.')->group(function () {
        Route::get('/{case}', [CustomerNotificationCaseController::class, 'show'])->name('show');
        Route::put('/{case}', [CustomerNotificationCaseController::class, 'update'])->name('update');
        Route::post('/{case}/submit', [CustomerNotificationCaseController::class, 'submit'])->name('submit');
    });

    Route::prefix('mi-cuenta/notificaciones')->middleware(['active.customer'])->name('customer.notifications.')->group(function () {
        Route::get('/', [PortalNotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [PortalNotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/{notification}/read', [PortalNotificationController::class, 'markRead'])->name('read');
    });

    Route::prefix('admin')->middleware(['role:admin,analista,soporte'])->group(function () {
        Route::get('/customer-menu-permissions', [CustomerMenuPermissionController::class, 'index'])->name('admin.customer-menu-permissions.index');
        Route::put('/customer-menu-permissions', [CustomerMenuPermissionController::class, 'update'])->name('admin.customer-menu-permissions.update');

        Route::get('/providers', [AdminProviderController::class, 'index'])->name('admin.providers.index');
        Route::post('/providers/{id}', [AdminProviderController::class, 'updateProvider'])->name('admin.providers.update');
        Route::post('/providers/{providerId}/services/{serviceId}', [AdminProviderController::class, 'updateService'])->name('admin.services.update');
        Route::post('/providers/{providerId}/services/{serviceId}/sections/{sectionId}', [AdminProviderController::class, 'updateSection'])->name('admin.sections.update');
        Route::post('/providers/{providerId}/services/{serviceId}/sections/{sectionId}/roles/{role}', [AdminProviderController::class, 'updateSectionRole'])->name('admin.section-roles.update');

        Route::get('/credits/purchase', [CreditPurchaseController::class, 'create'])->name('admin.credits.purchase');
        Route::get('/credits/wallet-info', [CreditPurchaseController::class, 'walletInfo'])->name('admin.credits.wallet-info');
        Route::post('/credits/purchase', [CreditPurchaseController::class, 'store'])->name('admin.credits.purchase.store');

        Route::get('/packages', [AdminPackageController::class, 'index'])->name('admin.packages.index');
        Route::get('/packages/create', [AdminPackageController::class, 'create'])->name('admin.packages.create');
        Route::post('/packages', [AdminPackageController::class, 'store'])->name('admin.packages.store');
        Route::get('/packages/{id}/edit', [AdminPackageController::class, 'edit'])->name('admin.packages.edit');
        Route::put('/packages/{id}', [AdminPackageController::class, 'update'])->name('admin.packages.update');
        Route::delete('/packages/{id}', [AdminPackageController::class, 'destroy'])->name('admin.packages.destroy');
        Route::get('/packages/assign', [AdminPackageController::class, 'assignForm'])->name('admin.packages.assign');
        Route::post('/packages/assign', [AdminPackageController::class, 'assign'])->name('admin.packages.assign.store');
        Route::get('/packages/user-wallets', [AdminPackageController::class, 'userWallets'])->name('admin.packages.user-wallets');
    });

    Route::prefix('admin/proceso-notificaciones')->middleware(['role:analista'])->name('admin.notification-cases.')->group(function () {
        Route::get('/', [AdminNotificationCaseController::class, 'index'])->name('index');
        Route::get('/{case}', [AdminNotificationCaseController::class, 'show'])->name('show');
        Route::put('/{case}', [AdminNotificationCaseController::class, 'update'])->name('update');
        Route::post('/{case}/start-review', [AdminNotificationCaseController::class, 'startReview'])->name('start-review');
        Route::post('/{case}/validate', [AdminNotificationCaseController::class, 'validateCase'])->name('validate');
        Route::post('/{case}/reject', [AdminNotificationCaseController::class, 'reject'])->name('reject');
    });

    Route::prefix('admin')->middleware(['role:analista'])->group(function () {
        Route::get('/consultations', [AdminConsultationController::class, 'index'])->name('admin.consultations.index');
        Route::get('/consultations/data', [AdminConsultationController::class, 'data'])->name('admin.consultations.data');
    });

    Route::prefix('admin')->middleware(['role:admin,soporte'])->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');
        Route::put('/users/{id}/approve', [AdminUserController::class, 'approve'])->name('admin.users.approve');
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

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

        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('admin.notifications.index');
        Route::put('/notifications/policies/{policy}', [AdminNotificationController::class, 'update'])->name('admin.notifications.update');

        Route::get('/provider-service-roles', [AdminProviderServiceRoleController::class, 'index'])->name('admin.provider-service-roles.index');
        Route::post('/provider-service-roles/{id_rol}/{provider_service_id}', [AdminProviderServiceRoleController::class, 'update'])->name('admin.provider-service-roles.update');

        Route::resource('role-types', AdminRoleTypeController::class)->names('admin.role-types')->parameters([
            'role-types' => 'roleType',
        ]);

        Route::get('/roles', [AdminRoleController::class, 'index'])->name('admin.roles.index');
        Route::get('/roles/create', [AdminRoleController::class, 'create'])->name('admin.roles.create');
        Route::post('/roles', [AdminRoleController::class, 'store'])->name('admin.roles.store');
        Route::get('/roles/{id_rol}/edit', [AdminRoleController::class, 'edit'])->name('admin.roles.edit');
        Route::put('/roles/{id_rol}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('/roles/{id_rol}', [AdminRoleController::class, 'destroy'])->name('admin.roles.destroy');
    });
});
