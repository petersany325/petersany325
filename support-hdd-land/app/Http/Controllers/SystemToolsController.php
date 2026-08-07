<?php

namespace App\Http\Controllers;

use App\Services\SystemMaintenanceService;
use Illuminate\Http\Request;

class SystemToolsController extends Controller
{
    public function __construct(private SystemMaintenanceService $maintenance)
    {
    }

    public function index()
    {
        return view('system-tools.index', [
            'snapshot' => $this->maintenance->statusSnapshot(),
            'lastResult' => session('system_tools_result'),
        ]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:clear_cache,rebuild_cache,db_health,db_repair,db_optimize,db_rebuild,migrate'],
        ]);

        $result = match ($data['action']) {
            'clear_cache' => $this->maintenance->clearCaches(),
            'rebuild_cache' => $this->maintenance->rebuildCaches(),
            'db_health' => $this->maintenance->databaseHealth(),
            'db_repair' => $this->maintenance->repairDatabase(),
            'db_optimize' => $this->maintenance->optimizeDatabase(),
            'db_rebuild' => $this->maintenance->rebuildDamagedDatabase(),
            'migrate' => $this->maintenance->runPendingMigrations(),
        };

        return redirect()
            ->route('system-tools.index')
            ->with($result['ok'] ? 'success' : 'error', $result['message'])
            ->with('system_tools_result', $result);
    }
}
