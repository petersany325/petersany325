<?php

namespace App\Observers;

use App\Support\DesktopSync\Engine;
use Illuminate\Database\Eloquent\Model;

class DesktopSyncObserver
{
    public function creating(Model $model): void
    {
        if (! app(Engine::class)->presentable($model)) {
            return;
        }
        if (blank($model->getAttribute('sync_key'))) {
            $model->setAttribute('sync_key', (string) \Illuminate\Support\Str::uuid());
        }
    }

    public function saved(Model $model): void
    {
        $op = filled($model->getAttribute('deleted_at')) ? 'delete' : 'upsert';
        app(Engine::class)->record($model, $op);
    }

    public function deleted(Model $model): void
    {
        app(Engine::class)->record($model, 'delete');
    }
}
