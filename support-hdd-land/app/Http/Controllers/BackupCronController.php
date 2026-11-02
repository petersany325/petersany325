<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use App\Support\BackupSettings;
use Illuminate\Http\Request;

class BackupCronController extends Controller
{
    public function __invoke(Request $request, DatabaseBackupService $backups)
    {
        $token = (string) $request->query('token', '');
        $cfg = BackupSettings::all();
        if ($token === '' || ! hash_equals($cfg['cron_token'], $token)) {
            abort(403, 'Invalid backup cron token');
        }

        $force = $request->boolean('force');
        if (! $force && ! BackupSettings::isDue()) {
            return response()->json([
                'ok' => true,
                'skipped' => true,
                'message' => 'Backup not due',
            ]);
        }

        $result = $backups->runScheduled();

        return response()->json([
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => $result['message'] ?? '',
            'file' => $result['file'] ?? null,
        ]);
    }
}
