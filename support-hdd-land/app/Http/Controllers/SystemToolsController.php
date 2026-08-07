<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use App\Services\SystemMaintenanceService;
use App\Support\BackupSettings;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemToolsController extends Controller
{
    public function __construct(
        private SystemMaintenanceService $maintenance,
        private DatabaseBackupService $backups,
    ) {
    }

    public function index()
    {
        return view('system-tools.index', [
            'snapshot' => $this->maintenance->statusSnapshot(),
            'lastResult' => session('system_tools_result'),
            'backups' => $this->backups->listLocal(),
            'backupCfg' => BackupSettings::all(),
        ]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:clear_cache,rebuild_cache,db_health,db_repair,db_optimize,db_rebuild,migrate,backup_full,backup_accounting,backup_delete,backup_restore_local,backup_restore_upload,backup_upload_remote,backup_run_now,backup_test_remote'],
            'file' => ['nullable', 'string', 'max:200'],
            'confirm_restore' => ['nullable'],
            'backup_file' => ['nullable', 'file', 'max:102400'],
        ]);

        $result = match ($data['action']) {
            'clear_cache' => $this->maintenance->clearCaches(),
            'rebuild_cache' => $this->maintenance->rebuildCaches(),
            'db_health' => $this->maintenance->databaseHealth(),
            'db_repair' => $this->maintenance->repairDatabase(),
            'db_optimize' => $this->maintenance->optimizeDatabase(),
            'db_rebuild' => $this->maintenance->rebuildDamagedDatabase(),
            'migrate' => $this->maintenance->runPendingMigrations(),
            'backup_full' => $this->backups->create('full', true),
            'backup_accounting' => $this->backups->create('accounting', true),
            'backup_delete' => $this->backups->deleteLocal((string) ($data['file'] ?? '')),
            'backup_restore_local' => $this->backups->restoreFromPath(
                (string) ($this->backups->absolutePath((string) ($data['file'] ?? '')) ?: ''),
                $request->boolean('confirm_restore')
            ),
            'backup_restore_upload' => $request->hasFile('backup_file')
                ? $this->backups->restoreUpload($request->file('backup_file'), $request->boolean('confirm_restore'))
                : ['ok' => false, 'message' => 'فایل بکاپ انتخاب نشده است.'],
            'backup_upload_remote' => (function () use ($data) {
                $path = $this->backups->absolutePath((string) ($data['file'] ?? ''));
                if (! $path) {
                    return ['ok' => false, 'message' => 'فایل محلی یافت نشد.'];
                }

                return $this->backups->uploadToRemote($path);
            })(),
            'backup_run_now' => $this->backups->runScheduled(),
            'backup_test_remote' => $this->backups->testRemote(),
        };

        return redirect()
            ->route('system-tools.index')
            ->with($result['ok'] ? 'success' : 'error', $result['message'])
            ->with('system_tools_result', $result);
    }

    public function downloadBackup(string $file): BinaryFileResponse
    {
        $path = $this->backups->absolutePath($file);
        abort_unless($path, 404);

        return response()->download($path, basename($path), [
            'Content-Type' => str_ends_with($path, '.gz') ? 'application/gzip' : 'application/sql',
        ]);
    }
}
