<?php

namespace App\Observers;

use App\Support\DesktopSync\SyncTableRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DesktopSyncTombstoneObserver
{
    public function deleted(Model $model): void
    {
        if (! Schema::hasTable('desktop_sync_tombstones')) {
            return;
        }
        if (! SyncTableRegistry::readable($model->getTable())) {
            return;
        }
        $usesSoft = in_array(SoftDeletes::class, class_uses_recursive($model), true);
        if ($usesSoft && ! $model->isForceDeleting()) {
            return;
        }

        DB::table('desktop_sync_tombstones')->insert([
            'table_name' => $model->getTable(),
            'row_id' => (int) $model->getKey(),
            'deleted_at' => now(),
        ]);
    }
}
