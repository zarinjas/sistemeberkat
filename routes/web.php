<?php

use App\Http\Controllers\AdminApprovalController;
use App\Http\Controllers\AdminDashboardPosterController;
use App\Http\Controllers\AdminFormBuilderController;
use App\Http\Controllers\AdminHeroSettingsController;
use App\Http\Controllers\AdminInfoDocumentController;
use App\Http\Controllers\AdminMembershipImportController;
use App\Http\Controllers\AidApplicationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailNotificationController;
use App\Http\Controllers\InfoCenterController;
use App\Http\Controllers\OperationAuditController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportingController;
use App\Http\Controllers\SystemManagementController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/info-center', [InfoCenterController::class, 'index'])->name('info-center.index');
});

Route::middleware(['auth', 'verified', 'role:applicant'])->group(function () {
    Route::get('/membership-card', [DashboardController::class, 'membershipCard'])->name('membership-card');
    Route::get('/applications', [AidApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/create', [AidApplicationController::class, 'create'])->name('applications.create');
    Route::post('/applications', [AidApplicationController::class, 'store'])->name('applications.store');
    Route::get('/applications/{application}', [AidApplicationController::class, 'show'])->name('applications.show');
    Route::delete('/applications/{application}/draft', [AidApplicationController::class, 'destroyDraft'])->name('applications.destroy-draft');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/approvals', [AdminApprovalController::class, 'index'])->name('admin.approvals.index');
    Route::get('/admin/approvals/{application}', [AdminApprovalController::class, 'show'])->name('admin.approvals.show');
    Route::patch('/admin/approvals/{application}/status', [AdminApprovalController::class, 'updateStatus'])->name('admin.approvals.status');

    Route::get('/admin/applications', [AdminApprovalController::class, 'index'])->name('admin.applications.index');
    Route::get('/admin/applications/{application}/show', [AdminApprovalController::class, 'show'])->name('admin.applications.show');

    Route::get('/admin/reports', [ReportingController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/data', [ReportingController::class, 'data'])->name('admin.reports.data');

    Route::get('/admin/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/admin/payments/export', [PaymentController::class, 'export'])->name('admin.payments.export');
    Route::patch('/admin/payments/{application}/prepare', [PaymentController::class, 'prepare'])->name('admin.payments.prepare');
    Route::patch('/admin/payments/{application}/disburse', [PaymentController::class, 'disburse'])->name('admin.payments.disburse');

    Route::get('/admin/email-notifications', [EmailNotificationController::class, 'index'])->name('admin.notifications.index');
    Route::post('/admin/email-notifications/preview-count', [EmailNotificationController::class, 'previewCount'])->name('admin.notifications.preview-count');
    Route::post('/admin/email-notifications/send-bulk', [EmailNotificationController::class, 'sendBulk'])->name('admin.notifications.send.bulk');
    Route::post('/admin/email-notifications/{application}/send', [EmailNotificationController::class, 'send'])->name('admin.notifications.send');

    Route::get('/admin/system', [SystemManagementController::class, 'index'])->name('admin.system.index');
    Route::get('/admin/system/audit', [OperationAuditController::class, 'index'])->name('admin.audit.index');
    Route::patch('/admin/system/users/{user}/role', [SystemManagementController::class, 'updateRole'])->name('admin.system.users.role');
    Route::post('/admin/system/dashboard-posters', [AdminDashboardPosterController::class, 'store'])->name('admin.system.dashboard-posters.store');
    Route::patch('/admin/system/dashboard-posters/{dashboardPoster}', [AdminDashboardPosterController::class, 'update'])->name('admin.system.dashboard-posters.update');
    Route::delete('/admin/system/dashboard-posters/{dashboardPoster}', [AdminDashboardPosterController::class, 'destroy'])->name('admin.system.dashboard-posters.destroy');
});

Route::middleware(['auth', 'verified', 'superadmin'])->group(function () {
    Route::get('/admin/form-builder', [AdminFormBuilderController::class, 'index'])->name('admin.form-builder');
    Route::get('/forms/builder', [AdminFormBuilderController::class, 'index'])->name('forms.builder');
    Route::post('/admin/form-builder/draft', [AdminFormBuilderController::class, 'saveDraft'])->name('admin.form-builder.draft');
    Route::post('/admin/form-builder/publish', [AdminFormBuilderController::class, 'publish'])->name('admin.form-builder.publish');
    Route::patch('/admin/form-builder/categories/{categoryKey}', [AdminFormBuilderController::class, 'updateCategory'])->name('admin.form-builder.categories.update');
    Route::delete('/admin/form-builder/categories/{categoryKey}', [AdminFormBuilderController::class, 'destroyCategory'])->name('admin.form-builder.categories.destroy');
    Route::get('/forms/manage', [AdminFormBuilderController::class, 'manage'])->name('forms.manage');
    Route::patch('/forms/manage/{formSchema}/activate', [AdminFormBuilderController::class, 'activate'])->name('forms.manage.activate');
    Route::patch('/forms/manage/{formSchema}/archive', [AdminFormBuilderController::class, 'archive'])->name('forms.manage.archive');
    Route::post('/forms/manage/{formSchema}/duplicate', [AdminFormBuilderController::class, 'duplicate'])->name('forms.manage.duplicate');
    Route::delete('/forms/manage/{formSchema}', [AdminFormBuilderController::class, 'destroy'])->name('forms.manage.destroy');

    Route::get('/admin/hero-settings', [AdminHeroSettingsController::class, 'index'])->name('admin.hero-settings');
    Route::post('/admin/hero-settings', [AdminHeroSettingsController::class, 'update'])->name('admin.hero-settings.update');
    Route::delete('/admin/hero-settings/image', [AdminHeroSettingsController::class, 'removeImage'])->name('admin.hero-settings.remove-image');

    Route::get('/admin/system/membership-import/template', [AdminMembershipImportController::class, 'downloadTemplate'])->name('admin.system.membership-import.template');
    Route::post('/admin/system/membership-import', [AdminMembershipImportController::class, 'import'])->name('admin.system.membership-import.store');

    Route::post('/admin/system/info-documents', [AdminInfoDocumentController::class, 'store'])->name('admin.system.info-documents.store');
    Route::patch('/admin/system/info-documents/{infoDocument}', [AdminInfoDocumentController::class, 'update'])->name('admin.system.info-documents.update');
    Route::delete('/admin/system/info-documents/{infoDocument}', [AdminInfoDocumentController::class, 'destroy'])->name('admin.system.info-documents.destroy');
});

require __DIR__.'/auth.php';
