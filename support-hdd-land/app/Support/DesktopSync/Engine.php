<?php

namespace App\Support\DesktopSync;

use App\Models\Customer;
use App\Models\DesktopDevice;
use App\Models\LookupOption;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\SyncChange;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class Engine
{
    public const PAGE = 200;

    public const PUSH_LIMIT = 100;

    public static ?int $originDeviceId = null;

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function exchange(DesktopDevice $device, array $input): array
    {
        $changes = $input['changes'] ?? [];
        if (! is_array($changes) || (is_array($changes) && array_is_list($changes) === false && $changes !== [])) {
            throw new SyncException('فهرست تغییرات باید یک آرایه باشد.', 422);
        }
        if (count($changes) > self::PUSH_LIMIT) {
            throw new SyncException('در هر بار حداکثر '.self::PUSH_LIMIT.' تغییر بفرستید.', 422);
        }

        $cursor = $input['cursor'] ?? null;
        $snapshot = is_array($input['snapshot'] ?? null) ? $input['snapshot'] : [];
        $applied = $this->push($device, $changes);

        if ($cursor === null) {
            $token = array_key_exists('token', $snapshot)
                ? (int) $snapshot['token']
                : (int) (SyncChange::query()->max('id') ?? 0);
            $snapshot['token'] = $token;
            [$rows, $next, $hasMore] = $this->snapshot($snapshot);
            $this->touch($device, $token);

            return $this->envelope('snapshot', $token, $hasMore, $applied, $rows, $next);
        }

        $cursor = max(0, (int) $cursor);
        $events = SyncChange::query()
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->limit(self::PAGE + 1)
            ->get();
        $hasMore = $events->count() > self::PAGE;
        $events = $events->take(self::PAGE);
        $nextCursor = (int) ($events->last()->id ?? $cursor);
        $rows = [];
        foreach ($events as $event) {
            $row = is_array($event->payload) ? $event->payload : [];
            $row['cursor'] = $event->id;
            $row['origin_device_id'] = $event->desktop_device_id;
            $rows[] = $row;
        }
        $this->touch($device, $nextCursor);

        return $this->envelope('incremental', $nextCursor, $hasMore, $applied, $rows, null);
    }

    /**
     * @param  list<mixed>  $changes
     * @return list<array<string, mixed>>
     */
    public function push(DesktopDevice $device, array $changes): array
    {
        $order = array_flip(Catalog::names());
        usort($changes, function ($a, $b) use ($order) {
            $a = is_array($a) ? $a : [];
            $b = is_array($b) ? $b : [];
            $aDelete = (($a['op'] ?? 'upsert') === 'delete') ? 1 : 0;
            $bDelete = (($b['op'] ?? 'upsert') === 'delete') ? 1 : 0;
            if ($aDelete !== $bDelete) {
                return $aDelete <=> $bDelete;
            }
            $aRank = $order[$a['resource'] ?? ''] ?? 99;
            $bRank = $order[$b['resource'] ?? ''] ?? 99;

            return $aDelete === 1 ? ($bRank <=> $aRank) : ($aRank <=> $bRank);
        });

        $results = [];
        self::$originDeviceId = $device->id;
        try {
            foreach ($changes as $change) {
                $results[] = $this->applyOne(is_array($change) ? $change : []);
            }
        } finally {
            self::$originDeviceId = null;
        }

        return $results;
    }

    public function presentable(Model $model): bool
    {
        return $this->ready($model);
    }

    public function record(Model $model, string $op): void
    {
        if (! $this->ready($model) || ! Schema::hasTable('sync_changes')) {
            return;
        }
        if (blank($model->getAttribute('sync_key'))) {
            if (! $model->exists) {
                return;
            }
            $model->setAttribute('sync_key', (string) Str::uuid());
            $model->saveQuietly();
        }

        $payload = $this->present($model, $op);
        SyncChange::query()->create([
            'desktop_device_id' => self::$originDeviceId,
            'resource' => $payload['resource'],
            'sync_key' => $payload['sync_key'],
            'op' => $op,
            'payload' => $payload,
            'record_updated_at' => $model->updated_at,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Model $model, string $op): array
    {
        $resource = Catalog::forModel($model);
        $data = [];
        if ($op !== 'delete' && $resource) {
            foreach ($resource['fields'] as $name => $type) {
                if (! $this->hasColumn($model->getTable(), $name)) {
                    continue;
                }
                $data[$name] = $this->exportValue($model->getAttribute($name), $type);
            }
            foreach ($resource['relations'] as $key => $relation) {
                $id = $model->getAttribute($relation['column']);
                $data[$key] = $id
                    ? $relation['model']::query()->whereKey($id)->value('sync_key')
                    : null;
            }
        }

        return [
            'resource' => $resource['name'] ?? $model->getTable(),
            'sync_key' => (string) $model->getAttribute('sync_key'),
            'server_id' => $model->getKey(),
            'op' => $op,
            'updated_at' => optional($model->updated_at)->toIso8601String(),
            'data' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $change
     * @return array<string, mixed>
     */
    private function applyOne(array $change): array
    {
        $resourceName = (string) ($change['resource'] ?? '');
        $syncKey = trim((string) ($change['sync_key'] ?? ''));
        $op = (string) ($change['op'] ?? 'upsert');
        $resource = Catalog::find($resourceName);

        if (! $resource) {
            return $this->result($resourceName, $syncKey, 'rejected', 'منبع ناشناخته است.');
        }
        if (! preg_match('/^[A-Za-z0-9_-]{16,64}$/', $syncKey)) {
            return $this->result($resourceName, $syncKey, 'rejected', 'شناسه همگام نامعتبر است.');
        }
        if (! in_array($op, ['upsert', 'delete'], true)) {
            return $this->result($resourceName, $syncKey, 'rejected', 'عملیات باید upsert یا delete باشد.');
        }

        try {
            return DB::transaction(function () use ($resource, $resourceName, $syncKey, $op, $change) {
                $model = $this->findModel($resource, $syncKey);
                $data = is_array($change['data'] ?? null) ? $change['data'] : [];
                $clientKey = $syncKey;

                if ($op === 'upsert' && ! $model && $resourceName === 'customers') {
                    $model = $this->matchCustomerByPhone($data);
                }

                if ($op === 'delete') {
                    return $this->applyDelete($resource, $model, $clientKey);
                }

                if ($model && $this->sameState($model, $resource, $data)) {
                    return $this->result($resourceName, (string) $model->getAttribute('sync_key'), 'unchanged', null, $this->present($model, 'upsert'), $clientKey);
                }
                if ($model && $this->isStale($model, $change['base_updated_at'] ?? null)) {
                    return $this->result($resourceName, (string) $model->getAttribute('sync_key'), 'kept_server', 'نسخه سرور جدیدتر است.', $this->present($model, $this->deletedOp($model)), $clientKey);
                }

                $created = ! $model;
                if (! $model) {
                    /** @var Model $model */
                    $model = new $resource['model'];
                    $model->setAttribute('sync_key', $syncKey);
                }

                $error = $this->fillModel($model, $resource, $data, $created);
                if ($error) {
                    return $this->result($resourceName, $syncKey, 'rejected', $error);
                }
                if ($this->usesSoftDeletes($resource['model']) && method_exists($model, 'trashed') && $model->trashed()) {
                    $model->setAttribute('deleted_at', null);
                }
                if ($resourceName === 'receptions') {
                    $this->keepTicketNumbersDistinct($model, $created);
                }
                if ($created && $resourceName === 'parts') {
                    $this->assignDefaultWarehouse($model);
                }
                if ($created && $resourceName === 'receptions' && blank($model->getAttribute('product_name'))) {
                    return $this->result($resourceName, $syncKey, 'rejected', 'نام کالا لازم است.');
                }
                if ($created && $resourceName === 'receptions' && blank($model->getAttribute('received_at'))) {
                    $model->setAttribute('received_at', now());
                }

                $model->save();
                if ($resourceName === 'payments' && $model instanceof Payment) {
                    $this->recordDesktopPayment($model);
                }
                $fresh = $model->fresh() ?: $model;

                return $this->result($resourceName, (string) $fresh->getAttribute('sync_key'), 'applied', null, $this->present($fresh, 'upsert'), $clientKey);
            });
        } catch (Throwable $e) {
            report($e);

            return $this->result($resourceName, $syncKey, 'rejected', 'ذخیره این ردیف روی سرور انجام نشد.');
        }
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function applyDelete(array $resource, ?Model $model, string $clientKey): array
    {
        $name = $resource['name'];
        if (! $model) {
            return $this->result($name, $clientKey, 'unchanged');
        }
        if (! $resource['deletable']) {
            return $this->result($name, (string) $model->getAttribute('sync_key'), 'rejected', 'حذف قبض و پرداخت از همگام‌سازی بسته است. وضعیت قبض را لغو کنید.', $this->present($model, 'upsert'), $clientKey);
        }
        if ($retired = $this->retireIfInUse($resource, $model, $clientKey)) {
            return $retired;
        }
        if ($name === 'customers' && $model instanceof Customer) {
            $this->releaseCustomerPhone($model);
        }
        $payload = $this->present($model, 'delete');
        $model->delete();

        return $this->result($name, (string) ($payload['sync_key'] ?? $clientKey), 'applied', null, $payload, $clientKey);
    }

    /**
     * @param  array<string, mixed>  $resource
     */
    private function findModel(array $resource, string $syncKey): ?Model
    {
        $query = $resource['model']::query();
        if ($this->usesSoftDeletes($resource['model'])) {
            $query->withTrashed();
        }

        return $query->where('sync_key', $syncKey)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function matchCustomerByPhone(array $data): ?Customer
    {
        $phone = User::normalizePhone($data['phone'] ?? null);
        if (! $phone) {
            return null;
        }

        return Customer::query()->where('phone', $phone)->orderByDesc('id')->first();
    }

    private function keepTicketNumbersDistinct(Model $model, bool $created): void
    {
        $ticket = trim((string) $model->getAttribute('ticket_no'));
        $taken = $ticket !== '' && Reception::query()
            ->where('ticket_no', $ticket)
            ->when($model->exists, fn ($query) => $query->whereKeyNot($model->getKey()))
            ->exists();
        if ($created && ($ticket === '' || $taken)) {
            $model->setAttribute('ticket_no', Reception::nextTicketNo());
        }
        if (! $created && $taken) {
            $model->setAttribute('ticket_no', $model->getOriginal('ticket_no'));
        }
    }

    private function assignDefaultWarehouse(Model $model): void
    {
        if (! $model instanceof Part || ! $this->hasColumn('parts', 'warehouse_id') || filled($model->getAttribute('warehouse_id'))) {
            return;
        }
        if (! Schema::hasTable('warehouses') || ! method_exists(Warehouse::class, 'defaultId')) {
            return;
        }
        $model->setAttribute('warehouse_id', Warehouse::defaultId());
    }

    private function signedPaymentAmount(string $type, mixed $amount): int
    {
        $value = abs((int) $amount);

        return $type === 'refund' ? -$value : $value;
    }

    private function recordDesktopPayment(Payment $payment): void
    {
        $reception = $payment->reception;
        if ($reception) {
            $reception->recalculateTotals();
        }
        app(AccountingService::class)->postPayment($payment->fresh(['reception', 'customer']));
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>|null
     */
    private function retireIfInUse(array $resource, Model $model, string $clientKey): ?array
    {
        if (! $this->recordInUse($resource['name'], $model)) {
            return null;
        }
        if (! $this->hasColumn($model->getTable(), 'is_active')) {
            return $this->result($resource['name'], (string) $model->getAttribute('sync_key'), 'rejected', 'این مورد در قبض‌ها استفاده شده و حذف نمی‌شود.', $this->present($model, 'upsert'), $clientKey);
        }
        $model->setAttribute('is_active', false);
        $model->save();
        $fresh = $model->fresh() ?: $model;

        return $this->result($resource['name'], (string) $fresh->getAttribute('sync_key'), 'applied', 'چون در قبض‌ها استفاده شده، به‌جای حذف غیرفعال شد.', $this->present($fresh, 'upsert'), $clientKey);
    }

    private function recordInUse(string $resource, Model $model): bool
    {
        $id = (int) $model->getKey();

        return match ($resource) {
            'fault_types' => Reception::query()->where('fault_type_id', $id)->exists(),
            'referral_sources' => Customer::query()->where('referral_source_id', $id)->exists(),
            'technicians' => Reception::query()
                ->where('technician_id', $id)
                ->when(
                    $this->hasColumn('receptions', 'custody_technician_id'),
                    fn ($query) => $query->orWhere('custody_technician_id', $id)
                )
                ->exists(),
            'parts' => (Schema::hasTable('reception_parts') && DB::table('reception_parts')->where('part_id', $id)->exists())
                || (Schema::hasTable('stock_movements') && DB::table('stock_movements')->where('part_id', $id)->exists()),
            'lookup_options' => $this->lookupInUse($model),
            default => false,
        };
    }

    private function lookupInUse(Model $lookup): bool
    {
        $column = match ((string) $lookup->getAttribute('group_key')) {
            'admission_type' => 'admission_type',
            'service_type' => 'service_type',
            'repair_type' => 'repair_type',
            'warranty_type' => 'warranty_type',
            'hdd_capacity' => 'hdd_capacity',
            default => null,
        };
        if ($column === null || ! $this->hasColumn('receptions', $column)) {
            return false;
        }

        return Reception::query()->where($column, (string) $lookup->getAttribute('name'))->exists();
    }

    private function releaseCustomerPhone(Customer $customer): void
    {
        $digits = preg_replace('/\D+/', '', (string) $customer->phone) ?? '';
        $customer->forceFill([
            'name' => mb_substr((string) $customer->name, 0, 100).' «حذف‌شده»',
            'phone' => mb_substr('del'.$customer->id.'_'.$digits, 0, 20),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $resource
     * @param  array<string, mixed>  $data
     */
    private function fillModel(Model $model, array $resource, array $data, bool $created): ?string
    {
        foreach ($resource['fields'] as $name => $type) {
            if (! array_key_exists($name, $data) || ! $this->hasColumn($model->getTable(), $name)) {
                continue;
            }
            [$value, $error] = $this->importValue($data[$name], $type, $name);
            if ($error) {
                return $error;
            }
            $model->setAttribute($name, $value);
        }

        if ($resource['name'] === 'customers') {
            $phone = User::normalizePhone($model->getAttribute('phone'));
            if ($phone) {
                $model->setAttribute('phone', $phone);
            }
            if ($created && (blank($model->getAttribute('name')) || blank($model->getAttribute('phone')))) {
                return 'نام و تلفن مشتری لازم است.';
            }
        }
        if ($created && in_array($resource['name'], ['technicians', 'fault_types', 'referral_sources', 'parts', 'lookup_options'], true) && blank($model->getAttribute('name'))) {
            return 'نام لازم است.';
        }
        if ($resource['name'] === 'lookup_options' && $created && blank($model->getAttribute('group_key'))) {
            return 'گروه منو لازم است.';
        }
        if ($resource['name'] === 'lookup_options' && filled($model->getAttribute('group_key')) && ! array_key_exists((string) $model->getAttribute('group_key'), LookupOption::GROUPS)) {
            return 'گروه منو شناخته نشد.';
        }
        if ($created && $resource['name'] === 'payments' && ($model->getAttribute('type') === null || $model->getAttribute('amount') === null)) {
            return 'نوع و مبلغ پرداخت لازم است.';
        }

        foreach ($resource['relations'] as $key => $relation) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $foreignKey = $this->resolveSyncKey($relation['model'], $data[$key]);
            if ($data[$key] !== null && $data[$key] !== '' && $foreignKey === null) {
                return 'رکورد مرتبط هنوز روی سرور نیست.';
            }
            if ($this->hasColumn($model->getTable(), $relation['column'])) {
                $model->setAttribute($relation['column'], $foreignKey);
            }
        }

        if ($created && $resource['name'] === 'receptions' && blank($model->getAttribute('customer_id'))) {
            return 'مشتری این قبض هنوز روی سرور نیست.';
        }
        if ($created && $resource['name'] === 'payments' && blank($model->getAttribute('reception_id'))) {
            return 'قبض این پرداخت هنوز روی سرور نیست.';
        }
        if ($resource['name'] === 'payments' && blank($model->getAttribute('customer_id')) && filled($model->getAttribute('reception_id'))) {
            $customerId = Reception::query()->whereKey($model->getAttribute('reception_id'))->value('customer_id');
            $model->setAttribute('customer_id', $customerId);
        }
        if ($created && $resource['name'] === 'payments' && blank($model->getAttribute('customer_id'))) {
            return 'مشتری این پرداخت هنوز روی سرور نیست.';
        }
        if ($resource['name'] === 'payments' && $model->getAttribute('amount') !== null && $model->getAttribute('amount') !== '') {
            $model->setAttribute(
                'amount',
                $this->signedPaymentAmount((string) $model->getAttribute('type'), $model->getAttribute('amount'))
            );
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @param  array<string, mixed>  $data
     */
    private function sameState(Model $model, array $resource, array $data): bool
    {
        foreach ($resource['fields'] as $name => $type) {
            if (! array_key_exists($name, $data) || ! $this->hasColumn($model->getTable(), $name)) {
                continue;
            }
            [$value] = $this->importValue($data[$name], $type, $name);
            $current = $this->exportValue($model->getAttribute($name), $type);
            $incoming = $this->exportValue($value, $type);
            if ($resource['name'] === 'payments' && $name === 'amount') {
                $paymentType = array_key_exists('type', $data)
                    ? (string) $data['type']
                    : (string) $model->getAttribute('type');
                $current = $this->signedPaymentAmount($paymentType, $current);
                $incoming = $this->signedPaymentAmount($paymentType, $incoming);
            }
            if ($current !== $incoming) {
                return false;
            }
        }
        foreach ($resource['relations'] as $key => $relation) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $currentId = $model->getAttribute($relation['column']);
            $currentKey = $currentId ? $relation['model']::query()->whereKey($currentId)->value('sync_key') : null;
            $incoming = $data[$key] === '' ? null : $data[$key];
            if ((string) $currentKey !== (string) $incoming && ! ($currentKey === null && $incoming === null)) {
                return false;
            }
        }

        return true;
    }

    private function isStale(Model $model, mixed $base): bool
    {
        if ($base === null || $base === '') {
            return true;
        }
        try {
            $baseTs = Carbon::parse((string) $base)->utc()->getTimestamp();
        } catch (Throwable) {
            return true;
        }
        $serverTs = $model->updated_at?->utc()->getTimestamp();

        return $serverTs !== null && $baseTs < $serverTs;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: list<array<string, mixed>>, 1: array<string, mixed>, 2: bool}
     */
    private function snapshot(array $state): array
    {
        $names = Catalog::names();
        $resource = (string) ($state['resource'] ?? $names[0]);
        $afterId = max(0, (int) ($state['after_id'] ?? 0));
        if (! in_array($resource, $names, true)) {
            $resource = $names[0];
            $afterId = 0;
        }

        [$rows, $full, $lastId] = $this->snapshotPage($resource, $afterId);
        $token = (int) $state['token'];
        if ($full) {
            return [$rows, ['token' => $token, 'resource' => $resource, 'after_id' => $lastId, 'done' => false], true];
        }

        $index = array_search($resource, $names, true);
        $next = $names[$index + 1] ?? null;
        if ($next) {
            return [$rows, ['token' => $token, 'resource' => $next, 'after_id' => 0, 'done' => false], true];
        }

        return [$rows, ['token' => $token, 'done' => true], false];
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: bool, 2: int}
     */
    private function snapshotPage(string $resourceName, int $afterId): array
    {
        $resource = Catalog::find($resourceName);
        $query = $resource['model']::query();
        if ($this->usesSoftDeletes($resource['model'])) {
            $query->withTrashed();
        }
        $models = $query->where('id', '>', $afterId)->orderBy('id')->limit(self::PAGE)->get();
        $rows = [];
        foreach ($models as $model) {
            if ($this->ready($model) && blank($model->getAttribute('sync_key'))) {
                $model->setAttribute('sync_key', (string) Str::uuid());
                $model->saveQuietly();
            }
            $op = $this->deletedOp($model);
            $rows[] = $this->present($model, $op);
        }
        $lastId = (int) ($models->last()->id ?? $afterId);

        return [$rows, $models->count() === self::PAGE, $lastId];
    }

    private function deletedOp(Model $model): string
    {
        return filled($model->getAttribute('deleted_at')) ? 'delete' : 'upsert';
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $applied
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>
     */
    private function envelope(string $mode, int $cursor, bool $hasMore, array $applied, array $rows, ?array $snapshot): array
    {
        return [
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'mode' => $mode,
            'cursor' => $cursor,
            'has_more' => $hasMore,
            'snapshot' => $snapshot,
            'applied' => $applied,
            'changes' => $rows,
        ];
    }

    private function touch(DesktopDevice $device, int $cursor): void
    {
        $device->forceFill([
            'last_seen_at' => now(),
            'last_cursor' => $cursor,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function result(string $resource, string $syncKey, string $result, ?string $reason = null, ?array $server = null, ?string $clientKey = null): array
    {
        return [
            'resource' => $resource,
            'sync_key' => $syncKey,
            'client_sync_key' => $clientKey ?: $syncKey,
            'result' => $result,
            'reason' => $reason,
            'server' => $server,
        ];
    }

    /**
     * @return array{0: mixed, 1: ?string}
     */
    private function importValue(mixed $value, string $type, string $field): array
    {
        if ($value === '' || $value === null) {
            return [null, null];
        }

        return match ($type) {
            'bool' => [filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false, null],
            'int' => $this->importInt($value, $field, false),
            'signed_int' => $this->importInt($value, $field, true),
            'date' => $this->importDate($value, $field, false),
            'datetime' => $this->importDate($value, $field, true),
            'text' => $this->importString($value, 5000, $field),
            default => $this->importString($value, 190, $field),
        };
    }

    /**
     * @return array{0: mixed, 1: ?string}
     */
    private function importInt(mixed $value, string $field, bool $allowNegative): array
    {
        if (! is_numeric($value)) {
            return [null, "مقدار {$field} عدد نیست."];
        }
        $number = (int) $value;
        if ($number < 0 && ! $allowNegative) {
            return [null, "مقدار {$field} نمی‌تواند منفی باشد."];
        }
        if ($field === 'commission_percent' && $number > 100) {
            return [null, 'درصد پورسانت بیشتر از ۱۰۰ است.'];
        }

        return [$number, null];
    }

    /**
     * @return array{0: mixed, 1: ?string}
     */
    private function importString(mixed $value, int $max, string $field): array
    {
        $text = trim((string) $value);
        if (mb_strlen($text) > $max) {
            return [null, "مقدار {$field} طولانی است."];
        }

        return [$text === '' ? null : $text, null];
    }

    /**
     * @return array{0: mixed, 1: ?string}
     */
    private function importDate(mixed $value, string $field, bool $withTime): array
    {
        try {
            $date = Carbon::parse((string) $value);
        } catch (Throwable) {
            return [null, "تاریخ {$field} نامعتبر است."];
        }

        return [$withTime ? $date : $date->toDateString(), null];
    }

    private function exportValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'bool' => (bool) $value,
            'int', 'signed_int' => (int) $value,
            'date' => $value instanceof Carbon ? $value->toDateString() : (string) $value,
            'datetime' => $value instanceof Carbon ? $value->toIso8601String() : (string) $value,
            default => (string) $value,
        };
    }

    private function resolveSyncKey(string $model, mixed $syncKey): ?int
    {
        if ($syncKey === null || $syncKey === '') {
            return null;
        }
        $query = $model::query();
        if ($this->usesSoftDeletes($model)) {
            $query->withTrashed();
        }
        $id = $query->where('sync_key', (string) $syncKey)->value('id');

        return $id ? (int) $id : null;
    }

    private function ready(Model $model): bool
    {
        return $this->hasColumn($model->getTable(), 'sync_key');
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $yes = [];
        $key = $table.'.'.$column;
        if (isset($yes[$key])) {
            return true;
        }
        $exists = Schema::hasTable($table) && Schema::hasColumn($table, $column);
        if ($exists) {
            $yes[$key] = true;
        }

        return $exists;
    }

    private function usesSoftDeletes(string $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }
}
