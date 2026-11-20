<?php

namespace App\Observers;

use App\Support\DesktopSync\SyncTableRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DesktopSyncTombstoneObserver
{
    public static function register(): void
    {
        if (app()->bound('desktop.sync.tombstones')) {
            return;
        }
        // Route files can load before Eloquent has a dispatcher. Retry on the
        // next boot instead of remembering a failed attempt for the process.
        if (Model::getEventDispatcher() === null) {
            return;
        }
        app()->instance('desktop.sync.tombstones', true);

        foreach (SyncTableRegistry::models() as $model) {
            if (is_string($model) && class_exists($model)) {
                $model::observe(self::class);
            }
        }
    }

    public function deleting(Model $model): void
    {
        if (! Schema::hasTable('desktop_sync_tombstones')) {
            return;
        }
        $children = match ($model->getTable()) {
            'journal_entries' => ['journal_lines', 'journal_entry_id'],
            'parts' => ['stock_movements', 'part_id'],
            default => null,
        };
        if ($children === null || ! Schema::hasTable($children[0])) {
            return;
        }
        $ids = DB::table($children[0])->where($children[1], $model->getKey())->pluck('id');
        foreach ($ids as $id) {
            $this->write((string) $children[0], (int) $id);
        }
    }

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

        $this->write($model->getTable(), (int) $model->getKey());
    }

    private function write(string $table, int $id): void
    {
        if (! SyncTableRegistry::readable($table) || $id <= 0) {
            return;
        }
        DB::table('desktop_sync_tombstones')->insert([
            'table_name' => $table,
            'row_id' => $id,
            'deleted_at' => now(),
        ]);
    }
}
