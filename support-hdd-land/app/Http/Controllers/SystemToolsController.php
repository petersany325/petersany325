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
            'downloadFile' => session('backup_download_file'),
        ]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:clear_cache,rebuild_cache,db_health,db_repair,db_optimize,db_rebuild,migrate,backup_full,backup_accounting,backup_save_pc,backup_delete,backup_restore_local,backup_restore_upload,backup_upload_remote,backup_run_now,backup_test_remote'],
            'file' => ['nullable', 'string', 'max:200'],
            'scope' => ['nullable', 'in:full,accounting'],
            'confirm_restore' => ['nullable'],
            'backup_file' => ['nullable', 'file', 'max:512000'],
        ]);

        // Create backup then immediately download to the user's computer
        if ($data['action'] === 'backup_save_pc') {
            $scope = ($data['scope'] ?? 'accounting') === 'full' ? 'full' : 'accounting';
            $created = $this->backups->create($scope, true);
            if (! ($created['ok'] ?? false)) {
                return $this->toolsRedirect($request, $created['message'] ?? 'ساخت بکاپ ناموفق بود.', 'error')
                    ->with('system_tools_result', $created);
            }

            $path = $created['path'] ?? $this->backups->absolutePath((string) ($created['file'] ?? ''));
            if (! $path || ! is_file($path)) {
                return $this->toolsRedirect($request, 'بکاپ ساخته شد ولی فایل برای دانلود یافت نشد.', 'error')
                    ->with('system_tools_result', $created)
                    ->with('backup_download_file', $created['file'] ?? null);
            }

            return response()->download($path, basename($path), [
                'Content-Type' => str_ends_with($path, '.gz') ? 'application/gzip' : 'application/sql',
            ]);
        }

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
                : ['ok' => false, 'message' => 'فایل بکاپ از کامپیوتر انتخاب نشده است.'],
            'backup_upload_remote' => (function () use ($data) {
                $path = $this->backups->absolutePath((string) ($data['file'] ?? ''));
                if (! $path) {
                    return ['ok' => false, 'message' => 'فایل محلی یافت نشد.'];
                }

                return $this->backups->uploadToRemote($path);
            })(),
            'backup_run_now' => $this->backups->runScheduled(),
            'backup_test_remote' => $this->backups->testRemote(),
            default => ['ok' => false, 'message' => 'عملیات نامعتبر است.'],
        };

        $redirect = $this->toolsRedirect(
            $request,
            $result['message'] ?? '',
            ($result['ok'] ?? false) ? 'success' : 'error'
        )->with('system_tools_result', $result);

        if (($result['ok'] ?? false) && ! empty($result['file']) && in_array($data['action'], ['backup_full', 'backup_accounting'], true)) {
            $redirect->with('backup_download_file', $result['file']);
        }

        return $redirect;
    }

    private function toolsRedirect(Request $request, string $message, string $flash = 'success')
    {
        $returnTab = (string) $request->input('settings_tab', '');
        if ($returnTab === 'backup' || $request->input('return_to') === 'settings') {
            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->withFragment('backup')
                ->with($flash, $message)
                ->with('settings_tab', 'backup');
        }

        return redirect()
            ->route('system-tools.index')
            ->with($flash, $message);
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
