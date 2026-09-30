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

Artisan::command('installments:remind {--force : Run even if SMS disabled flags}', function () {
    $service = app(\App\Services\InstallmentService::class);
    $reminders = app(\App\Services\InstallmentReminderService::class);
    $overdue = $service->refreshOverdueStatuses();
    $stats = $reminders->runDueReminders();
    $this->info('Overdue updated: '.$overdue);
    $this->info('Reminders sent: '.($stats['sent'] ?? 0).' errors: '.($stats['errors'] ?? 0));

    return 0;
})->purpose('Send installment due reminders (before / on / after)');

Schedule::command('installments:remind')->dailyAt('09:00');
