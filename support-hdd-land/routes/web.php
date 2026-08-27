<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DailyLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SmsStatusController;
use App\Http\Controllers\BackupCronController;
use App\Http\Controllers\BackupCloudController;
use App\Http\Controllers\SystemToolsController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\Payment\ZarinPalController;
use App\Http\Controllers\PortalInviteController;
use App\Http\Controllers\Portal\PaymentReceiptController as PortalPaymentReceiptController;
use App\Http\Controllers\Portal\RemotePartPreorderController as PortalRemotePartPreorderController;
use App\Http\Controllers\RemotePartPreorderController;
use App\Http\Controllers\HandoffController;
use App\Http\Controllers\InternController;
use App\Http\Controllers\InternPortalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StaffSmsTemplateController;
use App\Http\Controllers\Portal\AuthController as PortalAuthController;
use App\Http\Controllers\Portal\CartableController as PortalCartableController;
use App\Http\Controllers\Portal\MessageController as PortalMessageController;
use App\Http\Controllers\LicenseApiController;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsurePortalCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/license/request-otp', [LicenseApiController::class, 'requestOtp'])
    ->middleware('throttle:10,1')
    ->name('license.request-otp');
Route::post('/license/confirm-otp', [LicenseApiController::class, 'confirmOtp'])
    ->middleware('throttle:20,1')
    ->name('license.confirm-otp');
Route::post('/license/activate', [LicenseApiController::class, 'activate'])
    ->middleware('throttle:30,1')
    ->name('license.activate');
Route::post('/license/verify', [LicenseApiController::class, 'verify'])
    ->middleware('throttle:60,1')
    ->name('license.verify');

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(Auth::user()->homeRoute());
    }
    if (session('portal_customer_id')) {
        return redirect()->route('portal.home');
    }

    return response()
        ->view('gate')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
        ->header('Pragma', 'no-cache');
})->name('gate');

Route::redirect('/gate', '/');
Route::redirect('/portal', '/cartable');

Route::get('/a/{token}', [\App\Http\Controllers\CostApprovalController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{20,80}')
    ->name('approvals.show');
Route::post('/a/{token}/approve', [\App\Http\Controllers\CostApprovalController::class, 'approve'])
    ->where('token', '[A-Za-z0-9]{20,80}')
    ->middleware('throttle:20,1')
    ->name('approvals.approve');
Route::post('/a/{token}/reject', [\App\Http\Controllers\CostApprovalController::class, 'reject'])
    ->where('token', '[A-Za-z0-9]{20,80}')
    ->middleware('throttle:20,1')
    ->name('approvals.reject');

Route::get('/payments/zarinpal/callback/{trx}', [ZarinPalController::class, 'callback'])
    ->name('payments.zarinpal.callback');

Route::get('/cron/backup', BackupCronController::class)
    ->middleware('throttle:30,1')
    ->name('cron.backup');

Route::prefix('cartable')->name('portal.')->group(function () {
    Route::get('/', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/otp/send', [PortalAuthController::class, 'sendOtp'])->name('otp.send');
    Route::post('/otp/verify', [PortalAuthController::class, 'verifyOtp'])->name('otp.verify');

    Route::middleware(EnsurePortalCustomer::class)->group(function () {
        Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');
        Route::get('/home', [PortalCartableController::class, 'home'])->name('home');
        Route::get('/tickets', [PortalCartableController::class, 'tickets'])->name('tickets');
        Route::get('/search', [PortalCartableController::class, 'search'])->name('search');
        Route::get('/tickets/{reception}', [PortalCartableController::class, 'show'])->name('show');
        Route::get('/report', [PortalCartableController::class, 'report'])->name('report');
        Route::get('/approvals', [PortalCartableController::class, 'approvals'])->name('approvals');
        Route::get('/approvals/{approval}', [PortalCartableController::class, 'approvalShow'])->name('approvals.show');
        Route::get('/pay', [PortalCartableController::class, 'pay'])->name('pay');
        Route::post('/pay/{reception}/zarinpal', [ZarinPalController::class, 'start'])->name('zarinpal.start');
        Route::post('/tickets/{reception}/receipts', [PortalPaymentReceiptController::class, 'store'])->name('receipts.store');
        Route::get('/receipts/{receipt}/image', [PortalPaymentReceiptController::class, 'image'])->name('receipts.image');
        Route::get('/messages', [PortalMessageController::class, 'index'])->name('messages');
        Route::post('/messages', [PortalMessageController::class, 'store'])->name('messages.store');
        Route::get('/preorders', [PortalRemotePartPreorderController::class, 'index'])->name('preorders.index');
        Route::get('/preorders/create', [PortalRemotePartPreorderController::class, 'create'])->name('preorders.create');
        Route::post('/preorders', [PortalRemotePartPreorderController::class, 'store'])->name('preorders.store');
        Route::get('/preorders/{preorder}', [PortalRemotePartPreorderController::class, 'show'])->name('preorders.show');
        Route::get('/preorders/{preorder}/photo', [PortalRemotePartPreorderController::class, 'photo'])->name('preorders.photo');
    });
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/otp/send', [AuthController::class, 'sendOtp'])->name('login.otp.send');
    Route::post('/login/otp/verify', [AuthController::class, 'verifyOtp'])->name('login.otp.verify');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(EnsurePermission::class.':dashboard')
        ->name('dashboard');

    Route::middleware(EnsurePermission::class.':profile')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    });

    Route::middleware(EnsurePermission::class.':customers')->group(function () {
        Route::resource('customers', CustomerController::class);
        Route::prefix('portal-invites')->name('portal-invites.')->group(function () {
            Route::get('/', [PortalInviteController::class, 'index'])->name('index');
            Route::post('/template', [PortalInviteController::class, 'saveTemplate'])->name('template');
            Route::post('/start', [PortalInviteController::class, 'start'])->name('start');
            Route::match(['get', 'post'], '/run/{batch}', [PortalInviteController::class, 'run'])->name('run');
            Route::get('/report', [PortalInviteController::class, 'report'])->name('report');
            Route::post('/resend-failed', [PortalInviteController::class, 'resendFailed'])->name('resend-failed');
            Route::post('/resend/{customer}', [PortalInviteController::class, 'resend'])->name('resend');
        });
    });

    Route::middleware(EnsurePermission::class.':receptions')->group(function () {
        Route::get('receptions/lookup-phone', [ReceptionController::class, 'lookupPhone'])->name('receptions.lookup-phone');
        Route::get('receptions/lookup-customers', [ReceptionController::class, 'lookupCustomers'])->name('receptions.lookup-customers');
        Route::post('receptions/ensure-customer', [ReceptionController::class, 'ensureCustomer'])->name('receptions.ensure-customer');
        Route::get('receptions/search', [ReceptionController::class, 'search'])->name('receptions.search');
        Route::get('deliveries/group', [DeliveryController::class, 'create'])->name('deliveries.group');
        Route::post('deliveries/lookup', [DeliveryController::class, 'lookup'])->name('deliveries.lookup');
        Route::post('deliveries/group', [DeliveryController::class, 'store'])->name('deliveries.store');
        Route::resource('receptions', ReceptionController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
        Route::get('receptions/{reception}/report-partial', [ReceptionController::class, 'reportPartial'])->name('receptions.report-partial');
        Route::get('receptions/{reception}/history', [ReceptionController::class, 'history'])->name('receptions.history');
        Route::post('receptions/{reception}/status', [ReceptionController::class, 'updateStatus'])->name('receptions.status');
        Route::post('receptions/{reception}/parts', [ReceptionController::class, 'addPart'])->name('receptions.parts');
        Route::post('receptions/{reception}/payments', [ReceptionController::class, 'addPayment'])->name('receptions.payments');
        Route::put('receptions/{reception}/payments/{payment}', [ReceptionController::class, 'updatePayment'])->name('receptions.payments.update');
        Route::delete('receptions/{reception}/payments/{payment}', [ReceptionController::class, 'destroyPayment'])->name('receptions.payments.destroy');
        Route::post('receptions/{reception}/settle-deliver', [ReceptionController::class, 'settleAndDeliver'])->name('receptions.settle-deliver');
        Route::post('receptions/{reception}/collect-debt', [ReceptionController::class, 'collectDebt'])->name('receptions.collect-debt');
        Route::post('receptions/{reception}/cancel-delivery', [ReceptionController::class, 'cancelDelivery'])->name('receptions.cancel-delivery');
        Route::post('receptions/{reception}/exit-otp/required', [ReceptionController::class, 'updateExitOtpRequired'])->name('receptions.exit-otp.required');
        Route::post('receptions/{reception}/exit-otp/send', [ReceptionController::class, 'sendExitOtp'])->name('receptions.exit-otp.send');
        Route::post('receptions/{reception}/exit-otp/verify', [ReceptionController::class, 'verifyExitOtp'])->name('receptions.exit-otp.verify');
        Route::post('receptions/{reception}/exit-otp/bypass', [ReceptionController::class, 'bypassExitOtp'])->name('receptions.exit-otp.bypass');
        Route::post('receptions/{reception}/cost-stages', [ReceptionController::class, 'storeCostStage'])->name('receptions.cost-stages');
        Route::delete('receptions/{reception}/cost-stages/{stage}', [ReceptionController::class, 'destroyCostStage'])->name('receptions.cost-stages.destroy');
        Route::post('receptions/{reception}/zarinpal', [ZarinPalController::class, 'start'])->name('receptions.zarinpal');
        Route::get('receptions/{reception}/print', [ReceptionController::class, 'print'])->name('receptions.print');
        Route::post('receptions/{reception}/handoffs', [HandoffController::class, 'store'])->name('receptions.handoffs.store');
        Route::post('receptions/{reception}/cost-approval', [ReceptionController::class, 'requestCostApproval'])->name('receptions.cost-approval');
        Route::get('cost-approvals', [\App\Http\Controllers\CostApprovalsManageController::class, 'index'])->name('cost-approvals.index');
        Route::get('cost-approvals/settings', [\App\Http\Controllers\CostApprovalsManageController::class, 'settings'])->name('cost-approvals.settings');
        Route::post('cost-approvals/settings', [\App\Http\Controllers\CostApprovalsManageController::class, 'saveSettings'])->name('cost-approvals.settings.save');
        Route::post('cost-approvals/{reception}/send', [\App\Http\Controllers\CostApprovalsManageController::class, 'send'])->name('cost-approvals.send');
    });

    Route::middleware(EnsurePermission::class.':handoffs')->group(function () {
        Route::get('handoffs', [HandoffController::class, 'index'])->name('handoffs.index');
        Route::post('handoffs/{handoff}/respond', [HandoffController::class, 'respond'])->name('handoffs.respond');
        Route::post('receptions/{reception}/handoffs/return', [HandoffController::class, 'store'])->name('receptions.handoffs.return');
        Route::post('receptions/{reception}/work-report', [HandoffController::class, 'storeWorkReport'])->name('receptions.work-report');
    });

    Route::middleware('auth')->prefix('intern')->name('intern.')->group(function () {
        Route::get('/', [InternPortalController::class, 'index'])->name('portal');
        Route::post('/log', [InternPortalController::class, 'store'])->name('log');
    });

    Route::middleware(EnsurePermission::class.':notifications')->group(function () {
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::get('notifications/messages/{message}', [NotificationController::class, 'openMessage'])->name('notifications.messages.show');
    });

    Route::middleware(EnsurePermission::class.':daily_logs')->prefix('daily-logs')->name('daily-logs.')->group(function () {
        Route::get('/', [DailyLogController::class, 'index'])->name('index');
        Route::post('/', [DailyLogController::class, 'store'])->name('store');
        Route::get('/report', [DailyLogController::class, 'report'])->name('report');
        Route::post('/check', [DailyLogController::class, 'check'])->name('check');
        Route::post('/uncheck', [DailyLogController::class, 'uncheck'])->name('uncheck');
        Route::get('/settings', [DailyLogController::class, 'settings'])->name('settings');
        Route::post('/settings', [DailyLogController::class, 'saveSettings'])->name('settings.save');
        Route::post('/categories', [DailyLogController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [DailyLogController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [DailyLogController::class, 'destroyCategory'])->name('categories.destroy');
        Route::put('/{dailyLog}', [DailyLogController::class, 'update'])->name('update');
        Route::delete('/{dailyLog}', [DailyLogController::class, 'destroy'])->name('destroy');
    });

    Route::middleware(EnsurePermission::class.':parts')->group(function () {
        Route::get('parts/movements', [PartController::class, 'movements'])->name('parts.movements');
        Route::get('parts/valuation', [PartController::class, 'valuation'])->name('parts.valuation');
        Route::get('parts/receipt', [PartController::class, 'receiptForm'])->name('parts.receipt');
        Route::post('parts/receipt', [PartController::class, 'receiptStore'])->name('parts.receipt.store');
        Route::get('parts/issue', [PartController::class, 'issueForm'])->name('parts.issue');
        Route::post('parts/issue', [PartController::class, 'issueStore'])->name('parts.issue.store');
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
        Route::resource('parts', PartController::class)->except(['destroy']);
        Route::post('parts/{part}/stock', [PartController::class, 'adjustStock'])->name('parts.stock');
    });

    Route::middleware(EnsurePermission::class.':technicians')->group(function () {
        Route::resource('technicians', TechnicianController::class)->except(['show']);
    });

    Route::middleware(EnsurePermission::class.':employees')->group(function () {
        Route::resource('employees', EmployeeController::class)->except(['show']);
        Route::post('employees/{employee}/welcome-sms', [EmployeeController::class, 'sendWelcome'])->name('employees.welcome-sms');
        Route::resource('interns', InternController::class)->except(['show']);
        Route::post('interns/{intern}/welcome-sms', [InternController::class, 'sendWelcomeSms'])->name('interns.welcome-sms');
        Route::get('staff-sms/templates', [StaffSmsTemplateController::class, 'edit'])->name('staff-sms.templates');
        Route::post('staff-sms/templates', [StaffSmsTemplateController::class, 'update'])->name('staff-sms.templates.save');
    });

    Route::post('reports/settings', [ReportController::class, 'saveSettings'])->name('reports.settings');

    Route::get('reports/accounting', [ReportController::class, 'accounting'])
        ->middleware(EnsurePermission::class.':reports.accounting')
        ->name('reports.accounting');
    Route::get('reports/operations', [ReportController::class, 'operations'])
        ->middleware(EnsurePermission::class.':reports.operations')
        ->name('reports.operations');
    Route::get('reports/custody', [ReportController::class, 'custody'])
        ->middleware(EnsurePermission::class.':reports.custody')
        ->name('reports.custody');
    Route::get('reports/payments', [ReportController::class, 'payments'])
        ->middleware(EnsurePermission::class.':reports.payments')
        ->name('reports.payments');

    Route::middleware(EnsurePermission::class.':reports.payments')->prefix('payment-receipts')->name('payment-receipts.')->group(function () {
        Route::get('/', [PaymentReceiptController::class, 'index'])->name('index');
        Route::get('{receipt}', [PaymentReceiptController::class, 'show'])->name('show');
        Route::get('{receipt}/image', [PaymentReceiptController::class, 'image'])->name('image');
        Route::post('{receipt}/approve', [PaymentReceiptController::class, 'approve'])->name('approve');
        Route::post('{receipt}/reject', [PaymentReceiptController::class, 'reject'])->name('reject');
    });
    Route::get('reports/technicians', [ReportController::class, 'technicians'])
        ->middleware(EnsurePermission::class.':reports.technicians')
        ->name('reports.technicians');
    Route::get('reports/technicians/{technician}', [ReportController::class, 'technicianShow'])
        ->middleware(EnsurePermission::class.':reports.technicians')
        ->name('reports.technicians.show');
    Route::get('reports/customers', [ReportController::class, 'customers'])
        ->middleware(EnsurePermission::class.':reports.customers')
        ->name('reports.customers');
    Route::get('reports/customers/{customer}', [ReportController::class, 'customerShow'])
        ->middleware(EnsurePermission::class.':reports.customers')
        ->name('reports.customers.show');
    Route::get('reports/parts-used', [ReportController::class, 'partsUsed'])
        ->middleware(EnsurePermission::class.':reports.parts')
        ->name('reports.parts-used');
    Route::get('reports/goods-in', [ReportController::class, 'goodsIn'])
        ->middleware(EnsurePermission::class.':reports.parts')
        ->name('reports.goods-in');
    Route::get('reports/goods-unrepairable', [ReportController::class, 'goodsUnrepairable'])
        ->middleware(EnsurePermission::class.':reports.parts')
        ->name('reports.goods-unrepairable');
    Route::get('reports/goods-filter', [ReportController::class, 'goodsFilter'])
        ->middleware(EnsurePermission::class.':reports.parts')
        ->name('reports.goods-filter');
    Route::get('reports/sms', [ReportController::class, 'sms'])
        ->middleware(EnsurePermission::class.':reports.sms')
        ->name('reports.sms');
    Route::get('reports/messages', [ReportController::class, 'messages'])
        ->middleware(EnsurePermission::class.':reports.messages')
        ->name('reports.messages');

    Route::middleware(EnsurePermission::class.':reports.accounting')->prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [AccountingController::class, 'index'])->name('index');
        Route::get('/accounts', [AccountingController::class, 'accounts'])->name('accounts');
        Route::get('/journals', [AccountingController::class, 'journals'])->name('journals');
        Route::get('/journals/{journal}', [AccountingController::class, 'show'])->name('show');
        Route::get('/ledger', [AccountingController::class, 'ledger'])->name('ledger');
        Route::get('/trial-balance', [AccountingController::class, 'trialBalance'])->name('trial');
        Route::get('/receivables', [AccountingController::class, 'receivables'])->name('receivables');
        Route::get('/manual', [AccountingController::class, 'manualForm'])->name('manual');
        Route::post('/manual', [AccountingController::class, 'storeManual'])->name('manual.store');
        Route::post('/rebuild', [AccountingController::class, 'rebuild'])->name('rebuild');
    });

    Route::middleware(EnsurePermission::class.':sms.statuses')->group(function () {
        Route::get('sms-statuses', [SmsStatusController::class, 'index'])->name('sms-statuses.index');
        Route::post('sms-statuses', [SmsStatusController::class, 'store'])->name('sms-statuses.store');
        Route::put('sms-statuses/{smsStatus}', [SmsStatusController::class, 'update'])->name('sms-statuses.update');
        Route::delete('sms-statuses/{smsStatus}', [SmsStatusController::class, 'destroy'])->name('sms-statuses.destroy');
        Route::post('sms-statuses/{smsStatus}/hide', [SmsStatusController::class, 'hide'])->name('sms-statuses.hide');
        Route::post('sms-statuses/master', [SmsStatusController::class, 'updateMaster'])->name('sms-statuses.master');
        Route::post('sms-statuses/gateway', [SmsStatusController::class, 'updateGateway'])->name('sms-statuses.gateway');
        Route::post('sms-statuses/test', [SmsStatusController::class, 'test'])->name('sms-statuses.test');
    });

    Route::middleware(EnsurePermission::class.':settings')->group(function () {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings/general', [SettingController::class, 'updateGeneral'])->name('settings.general');
        Route::post('settings/fault-types', [SettingController::class, 'storeFaultType'])->name('settings.fault-types');
        Route::put('settings/fault-types/{faultType}', [SettingController::class, 'updateFaultType'])->name('settings.fault-types.update');
        Route::delete('settings/fault-types/{faultType}', [SettingController::class, 'destroyFaultType'])->name('settings.fault-types.destroy');
        Route::post('settings/referral-sources', [SettingController::class, 'storeReferralSource'])->name('settings.referral-sources');
        Route::put('settings/referral-sources/{referralSource}', [SettingController::class, 'updateReferralSource'])->name('settings.referral-sources.update');
        Route::delete('settings/referral-sources/{referralSource}', [SettingController::class, 'destroyReferralSource'])->name('settings.referral-sources.destroy');
        Route::post('settings/users', [SettingController::class, 'storeUser'])->name('settings.users');
        Route::post('settings/sms', [SettingController::class, 'updateSms'])->name('settings.sms');
        Route::post('settings/sms/test', [SettingController::class, 'testSms'])->name('settings.sms.test');
        Route::post('settings/invoice', [SettingController::class, 'updateInvoice'])->name('settings.invoice');
        Route::post('settings/payments', [SettingController::class, 'updatePayments'])->name('settings.payments');
        Route::post('settings/backup', [SettingController::class, 'updateBackup'])->name('settings.backup');
        Route::post('settings/backup/run-now', [SettingController::class, 'runBackupNow'])->name('settings.backup.run-now');
        Route::get('settings/backup/cloud/{provider}/connect', [BackupCloudController::class, 'connect'])
            ->whereIn('provider', ['google', 'onedrive', 'mega'])
            ->name('settings.backup.cloud.connect');
        Route::get('settings/backup/cloud/{provider}/callback', [BackupCloudController::class, 'callback'])
            ->whereIn('provider', ['google', 'onedrive'])
            ->name('settings.backup.cloud.callback');
        Route::post('settings/backup/cloud/{provider}/disconnect', [BackupCloudController::class, 'disconnect'])
            ->whereIn('provider', ['google', 'onedrive', 'mega'])
            ->name('settings.backup.cloud.disconnect');
        Route::post('settings/backup/cloud/{provider}/test', [BackupCloudController::class, 'test'])
            ->whereIn('provider', ['google', 'onedrive', 'mega'])
            ->name('settings.backup.cloud.test');
        Route::post('settings/lookups', [SettingController::class, 'storeLookup'])->name('settings.lookups');
        Route::put('settings/lookups/{lookup}', [SettingController::class, 'updateLookup'])->name('settings.lookups.update');
        Route::delete('settings/lookups/{lookup}', [SettingController::class, 'destroyLookup'])->name('settings.lookups.destroy');
    });

    Route::middleware(EnsurePermission::class.':system.tools')->group(function () {
        Route::get('system-tools', [SystemToolsController::class, 'index'])->name('system-tools.index');
        Route::post('system-tools/run', [SystemToolsController::class, 'run'])->name('system-tools.run');
        Route::get('system-tools/backups/{file}', [SystemToolsController::class, 'downloadBackup'])
            ->where('file', '[A-Za-z0-9._-]+')
            ->name('system-tools.backups.download');
    });

    Route::middleware(EnsurePermission::class.':system.tools')->prefix('licenses')->name('licenses.')->group(function () {
        Route::get('/', [\App\Http\Controllers\LicenseAdminController::class, 'index'])->name('index');
        Route::get('/online', [\App\Http\Controllers\LicenseAdminController::class, 'online'])->name('online');
        Route::get('/plans', [\App\Http\Controllers\LicenseAdminController::class, 'plans'])->name('plans');
        Route::post('/plans', [\App\Http\Controllers\LicenseAdminController::class, 'savePlans'])->name('plans.save');
        Route::post('/', [\App\Http\Controllers\LicenseAdminController::class, 'issue'])->name('issue');
        Route::get('/{license}/edit', [\App\Http\Controllers\LicenseAdminController::class, 'edit'])->name('edit');
        Route::post('/{license}/edit', [\App\Http\Controllers\LicenseAdminController::class, 'update'])->name('update');
        Route::get('/{license}/renew', [\App\Http\Controllers\LicenseAdminController::class, 'renewForm'])->name('renew');
        Route::post('/{license}/sms', [\App\Http\Controllers\LicenseAdminController::class, 'sendSms'])->name('sms');
        Route::post('/{license}/revoke', [\App\Http\Controllers\LicenseAdminController::class, 'revoke'])->name('revoke');
        Route::post('/{license}/unbind', [\App\Http\Controllers\LicenseAdminController::class, 'unbind'])->name('unbind');
        Route::post('/{license}/extend', [\App\Http\Controllers\LicenseAdminController::class, 'extend'])->name('extend');
    });

    Route::middleware(EnsurePermission::class.':receptions')->prefix('remote-preorders')->name('remote-preorders.')->group(function () {
        Route::get('/', [RemotePartPreorderController::class, 'index'])->name('index');
        Route::get('/settings', [RemotePartPreorderController::class, 'settings'])->name('settings');
        Route::post('/settings', [RemotePartPreorderController::class, 'saveSettings'])->name('settings.save');
        Route::get('{preorder}', [RemotePartPreorderController::class, 'show'])->name('show');
        Route::get('{preorder}/photo', [RemotePartPreorderController::class, 'photo'])->name('photo');
        Route::post('{preorder}/arrived', [RemotePartPreorderController::class, 'markArrived'])->name('arrived');
        Route::post('{preorder}/specs', [RemotePartPreorderController::class, 'updateSpecs'])->name('specs');
        Route::post('{preorder}/convert', [RemotePartPreorderController::class, 'convert'])->name('convert');
    });

    Route::middleware(EnsurePermission::class.':receptions')->prefix('trash')->name('trash.')->group(function () {
        Route::get('/', [TrashController::class, 'index'])->name('index');
        Route::post('/restore', [TrashController::class, 'restore'])->name('restore');
        Route::post('/force', [TrashController::class, 'forceDestroy'])->name('force');
    });
});
