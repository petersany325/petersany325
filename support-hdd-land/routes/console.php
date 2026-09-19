<?php

use App\Support\BackupSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('backup:run {--force : Ignore schedule window}', function () {
    $force = (bool) $this->option('force');
    $service = app(\App\Services\DatabaseBackupService::class);

    if (! $force && ! BackupSettings::isDue()) {
        $this->info('Backup not due yet.');

        return 0;
    }

    $result = $service->runScheduled();
    $this->{$result['ok'] ? 'info' : 'error'}($result['message']);

    return $result['ok'] ? 0 : 1;
})->purpose('Create database backup and optionally upload to remote host');

Schedule::command('backup:run')->hourly();
