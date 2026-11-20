<?php

require __DIR__.'/../vendor/autoload.php';

foreach ([
    __DIR__.'/../bootstrap/cache',
    __DIR__.'/../storage/framework/cache/data',
    __DIR__.'/../storage/framework/sessions',
    __DIR__.'/../storage/framework/views',
    __DIR__.'/../storage/logs',
] as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$missing = [
    'App\\Http\\Controllers\\AppReleaseAdminController',
    'App\\Http\\Controllers\\AppUpdateApiController',
    'App\\Http\\Controllers\\AppUpdateController',
    'App\\Http\\Controllers\\AttendanceController',
    'App\\Http\\Controllers\\ContactController',
    'App\\Http\\Controllers\\DeviceBlacklistController',
    'App\\Http\\Controllers\\InstallmentCronController',
    'App\\Http\\Controllers\\InstallmentPlanController',
    'App\\Http\\Controllers\\InstallmentReportController',
    'App\\Http\\Controllers\\InstallmentSettingController',
    'App\\Http\\Controllers\\LabelPrintController',
    'App\\Http\\Controllers\\LicenseAdminController',
    'App\\Http\\Controllers\\LicenseApiController',
    'App\\Http\\Controllers\\LicenseNetworkApiController',
    'App\\Http\\Controllers\\MyLicenseController',
    'App\\Http\\Controllers\\PartCategoryController',
    'App\\Http\\Controllers\\PartImportController',
    'App\\Http\\Controllers\\PartnerController',
    'App\\Http\\Controllers\\PartnerReferralController',
    'App\\Http\\Controllers\\PortalInviteController',
    'App\\Http\\Controllers\\Portal\\InstallmentController',
    'App\\Http\\Controllers\\Portal\\RemotePartPreorderController',
    'App\\Http\\Controllers\\RemotePartPreorderController',
    'App\\Http\\Controllers\\TrashController',
    'App\\Http\\Controllers\\WarehouseOpsController',
    'App\\Http\\Controllers\\WorkReportController',
];

foreach ($missing as $class) {
    if (class_exists($class)) {
        continue;
    }
    $pos = strrpos($class, '\\');
    $namespace = substr($class, 0, $pos);
    $short = substr($class, $pos + 1);
    eval('namespace '.$namespace.'; class '.$short.' extends \\Illuminate\\Routing\\Controller { public function __invoke() { return response(""); } }');
}

if (! class_exists(App\Support\LicenseStatus::class)) {
    eval('namespace App\\Support; class LicenseStatus { public static function isSellerSite(): bool { return false; } }');
}
