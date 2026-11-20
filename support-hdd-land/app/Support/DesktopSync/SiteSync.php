<?php

namespace App\Support\DesktopSync;

use App\Models\Customer;
use App\Models\DesktopDevice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SiteSync
{
    /** @var list<string> */
    private const MONEY = [
        'amount', 'deposit', 'pos_amount', 'admission_fee', 'estimated_cost', 'labor_cost',
        'parts_cost', 'stages_cost', 'discount', 'total_amount', 'paid_amount', 'commission',
        'customer_cost_approved_amount', 'purchase_price', 'sale_price', 'quantity', 'unit_price',
    ];

    /** @var list<string> */
    private const SECRETS = [
        'password', 'remember_token', 'token_hash', 'token_encrypted', 'otp', 'otp_code',
    ];

    /**
     * @return array<string, mixed>
     */
    public function ping(DesktopDevice $device): array
    {
        return [
            'ok' => true,
            'api_version' => 1,
            'site' => [
                'name' => shop_name(),
                'domain' => (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: request()->getHost()),
                'receipt_prefix' => Reception::receiptPrefix(),
            ],
            'server_time' => $this->iso(now()),
            'money_unit' => 'IRT',
            'token' => [
                'label' => $device->name,
                'scopes' => $device->tokenScopes(),
                'last_used_at' => $this->iso($device->last_seen_at),
            ],
            'tables' => $this->existingTables(),
        ];
    }

    /**
     * @param  list<string>|null  $tables
     * @return array<string, mixed>
     */
    public function changes(DesktopDevice $device, ?string $since, int $limit, ?array $tables): array
    {
        $cursor = $this->decodeCursor($since);
        $limit = $this->limit($limit);
        $items = $this->collect($device, $cursor, $limit + 1, $tables);
        $hasMore = count($items) > $limit;
        $items = array_slice($items, 0, $limit);
        $last = $items[array_key_last($items)] ?? null;
        $next = $last['cursor'] ?? $since;
        foreach ($items as &$item) {
            unset($item['cursor']);
        }
        unset($item);

        return [
            'ok' => true,
            'items' => $items,
            'next_cursor' => $next,
            'has_more' => $hasMore,
            'server_time' => $this->iso(now()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(DesktopDevice $device, string $table, int $page, int $perPage): array
    {
        if (! SyncTableRegistry::readable($table) || ! Schema::hasTable($table)) {
            return ['ok' => false, 'message' => 'این جدول در همگام‌سازی نیست.', 'status' => 422];
        }
        $page = max(1, $page);
        $perPage = max(1, min(1000, $perPage));
        $query = $this->baseQuery($table);
        $total = (clone $query)->count();
        $rows = $query->orderBy('id')->forPage($page, $perPage)->get();
        $data = [];
        $last = null;
        foreach ($rows as $row) {
            $item = $this->present($device, $table, $row, 'upsert');
            $data[] = $item['data'];
            $last = $item;
        }

        return [
            'ok' => true,
            'table' => $table,
            'data' => $data,
            'page' => $page,
            'per_page' => $perPage,
            'next_page' => ($page * $perPage) < $total ? $page + 1 : null,
            'total' => $total,
            'cursor' => $last['cursor'] ?? null,
            'server_time' => $this->iso(now()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $counts = [];
        $latest = null;
        foreach ($this->existingTables() as $table) {
            $counts[$table] = (int) $this->baseQuery($table)->count();
            $time = $this->timeColumn($table);
            $row = $this->baseQuery($table)
                ->orderByDesc($time ?: 'id')
                ->orderByDesc('id')
                ->first();
            if (! $row) {
                continue;
            }
            $stamp = $time ? (string) data_get($row, $time) : '';
            $candidate = ['t' => $this->stamp($stamp), 'table' => $table, 'id' => (int) data_get($row, 'id')];
            if ($latest === null || $this->tuple($candidate) > $this->tuple($latest)) {
                $latest = $candidate;
            }
        }

        return [
            'ok' => true,
            'server_time' => $this->iso(now()),
            'latest_cursor' => $latest ? $this->encodeCursor($latest['t'], $latest['table'], $latest['id']) : null,
            'counts' => $counts,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function push(DesktopDevice $device, string $batchId, array $items, ?string $ip): array
    {
        $this->prune();
        $results = [];
        foreach ($items as $item) {
            $results[] = $this->one($device, $batchId, is_array($item) ? $item : [], $ip);
        }

        return [
            'ok' => true,
            'batch_id' => $batchId,
            'results' => $results,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function one(DesktopDevice $device, string $batchId, array $item, ?string $ip): array
    {
        $uid = substr(trim((string) ($item['desktop_uid'] ?? '')), 0, 80);
        if ($uid === '') {
            return [
                'desktop_uid' => null,
                'status' => 'rejected',
                'message' => 'شناسه ردیف نرم‌افزار لازم است.',
            ];
        }

        $stored = DB::table('desktop_sync_receipts')
            ->where('token_id', $device->id)
            ->where('batch_id', $batchId)
            ->where('desktop_uid', $uid)
            ->value('result');
        if (is_string($stored)) {
            $decoded = json_decode($stored, true);

            return is_array($decoded) ? $decoded : ['desktop_uid' => $uid, 'status' => 'rejected', 'message' => 'نتیجه قبلی خوانده نشد.'];
        }

        try {
            $result = DB::transaction(function () use ($device, $batchId, $item, $uid, $ip) {
                $result = $this->apply($device, $item, $uid, $ip);
                DB::table('desktop_sync_receipts')->insert([
                    'token_id' => $device->id,
                    'batch_id' => $batchId,
                    'desktop_uid' => $uid,
                    'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                ]);

                return $result;
            });
        } catch (Throwable $e) {
            report($e);

            return [
                'desktop_uid' => $uid,
                'status' => 'rejected',
                'message' => 'ثبت این ردیف انجام نشد.',
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function apply(DesktopDevice $device, array $item, string $uid, ?string $ip): array
    {
        $table = (string) ($item['table'] ?? '');
        $op = (string) ($item['op'] ?? '');
        $ops = SyncTableRegistry::ops($table);
        if ($ops === [] || ! Schema::hasTable($table)) {
            return $this->rejected($uid, 'این جدول از نرم‌افزار قابل تغییر نیست.');
        }
        if (! in_array($op, $ops, true)) {
            return $this->rejected($uid, 'این عمل برای این جدول مجاز نیست.');
        }

        $data = is_array($item['data'] ?? null) ? $item['data'] : [];
        $resolved = $this->resolveRefs($device, $data);
        if (isset($resolved['error'])) {
            return $this->rejected($uid, $resolved['error'], $resolved['errors'] ?? null);
        }
        $data = $resolved['data'];
        $existing = $this->findRow($device, $table, $item['site_id'] ?? null, $uid);
        if (($item['site_id'] ?? null) && ! $existing) {
            return $this->rejected($uid, 'ردیف پیدا نشد.');
        }
        if ($op === 'update' && ! $existing) {
            return $this->rejected($uid, 'این جدول از نرم‌افزار قابل تغییر نیست.');
        }

        $blocked = $this->guard($table, $existing, $data, $item['base_updated_at'] ?? null);
        if ($blocked) {
            $blocked['desktop_uid'] = $uid;

            return $blocked;
        }
        if ($duplicate = $this->duplicate($table, $existing, $data)) {
            return $this->rejected($uid, $duplicate['message'], $duplicate['errors']);
        }

        $before = $existing ? $this->raw($table, $existing) : null;
        if ($op === 'delete') {
            $this->remove($table, $existing);
            $this->remember($device, $table, (int) $this->key($existing), $uid);
            $this->audit($device, 'push', $table, (int) $this->key($existing), 'delete', 'deleted', $before, ['id' => $this->key($existing)], $ip);

            return [
                'desktop_uid' => $uid,
                'status' => 'deleted',
                'site_id' => (int) $this->key($existing),
                'updated_at' => $this->iso(now()),
            ];
        }

        $created = $existing === null;
        $saved = $this->save($table, $existing, $data);
        $id = (int) $this->key($saved);
        $this->afterSave($table, $saved);
        $this->remember($device, $table, $id, $uid);
        $fresh = $this->reload($table, $id);
        $this->audit($device, 'push', $table, $id, 'upsert', $created ? 'created' : 'updated', $before, $this->raw($table, $fresh), $ip);

        return [
            'desktop_uid' => $uid,
            'status' => $created ? 'created' : 'updated',
            'site_id' => $id,
            'updated_at' => $this->iso(data_get($fresh, 'updated_at') ?: now()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{data: array<string, mixed>}|array{error: string, errors?: array<string, string>}
     */
    private function resolveRefs(DesktopDevice $device, array $data): array
    {
        foreach ($data as $key => $value) {
            if (! is_string($key) || ! str_ends_with($key, '_ref') || ! is_array($value)) {
                continue;
            }
            $uid = trim((string) ($value['desktop_uid'] ?? ''));
            unset($data[$key]);
            if ($uid === '') {
                continue;
            }
            $mapped = DB::table('desktop_sync_map')
                ->where('token_id', $device->id)
                ->where('desktop_uid', $uid)
                ->first();
            if (! $mapped) {
                return ['error' => 'ارجاع پیدا نشد.', 'errors' => [$key => 'ردیف ارجاع‌شده هنوز روی سایت نیست.']];
            }
            $data[substr($key, 0, -4).'_id'] = (int) $mapped->row_id;
        }

        return ['data' => $data];
    }

    private function findRow(DesktopDevice $device, string $table, mixed $siteId, string $uid): mixed
    {
        if (is_numeric($siteId) && (int) $siteId > 0) {
            return $this->baseQuery($table)->where('id', (int) $siteId)->first();
        }
        $mapped = DB::table('desktop_sync_map')
            ->where('token_id', $device->id)
            ->where('table_name', $table)
            ->where('desktop_uid', $uid)
            ->first();
        if (! $mapped) {
            return null;
        }

        return $this->baseQuery($table)->where('id', $mapped->row_id)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function guard(string $table, mixed $existing, array &$data, mixed $base): ?array
    {
        $review = SyncTableRegistry::REVIEW_FIELDS[$table] ?? null;
        if ($review !== null) {
            $data = array_intersect_key($data, array_flip($review));
        }
        if (! $existing || $base === null || $base === '') {
            return null;
        }
        $current = data_get($existing, 'updated_at');
        if (! $current || abs(Carbon::parse($current)->diffInSeconds(Carbon::parse((string) $base))) <= 1) {
            return null;
        }
        if (! $this->siteWins($table, $existing, $data)) {
            return null;
        }

        return [
            'status' => 'conflict',
            'message' => 'نسخه سایت جدیدتر است و نگه داشته شد.',
            'current' => $this->raw($table, $existing),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function siteWins(string $table, mixed $existing, array $data): bool
    {
        if ($table !== 'customer_messages') {
            return false;
        }
        if ((string) data_get($existing, 'direction', 'inbound') === 'outbound') {
            return false;
        }

        return array_diff(array_keys($data), ['staff_read_at', 'handled_by']) !== [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{message: string, errors: array<string, string>}|null
     */
    private function duplicate(string $table, mixed $existing, array &$data): ?array
    {
        if ($table === 'customers' && isset($data['phone'])) {
            $data['phone'] = User::normalizePhone((string) $data['phone']) ?: $data['phone'];
            $dup = Customer::findByPhone((string) $data['phone']);
            $id = $existing ? (int) $this->key($existing) : 0;
            if ($dup && $dup->id !== $id) {
                return ['message' => 'شماره موبایل تکراری است. مشتری دیگری با همین موبایل ثبت شده.', 'errors' => ['phone' => 'شماره موبایل تکراری است.']];
            }
        }
        if ($table === 'receptions' && filled($data['receipt_no'] ?? null)) {
            $id = $existing ? (int) $this->key($existing) : 0;
            $taken = Reception::query()->where('receipt_no', $data['receipt_no'])->whereKeyNot($id)->exists();
            if ($taken) {
                return ['message' => 'شماره قبض تکراری است. شماره را عوض کنید.', 'errors' => ['receipt_no' => 'شماره قبض تکراری است.']];
            }
        }
        if ($table === 'receptions') {
            $ticket = trim((string) ($data['ticket_no'] ?? ''));
            $id = $existing ? (int) $this->key($existing) : 0;
            $taken = $ticket !== '' && Reception::query()->where('ticket_no', $ticket)->whereKeyNot($id)->exists();
            if ($ticket === '' || $taken) {
                if ($existing) {
                    unset($data['ticket_no']);
                } else {
                    $data['ticket_no'] = Reception::nextTicketNo();
                }
            }
        }
        if ($table === 'payments' && array_key_exists('amount', $data)) {
            $type = (string) ($data['type'] ?? data_get($existing, 'type', ''));
            $amount = abs((int) $data['amount']);
            $data['amount'] = $type === 'refund' ? -$amount : $amount;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function save(string $table, mixed $existing, array $data): mixed
    {
        $payload = $this->payload($table, $data);
        $model = SyncTableRegistry::model($table);
        if ($model) {
            /** @var Model $row */
            $row = $existing instanceof Model ? $existing : new $model;
            $row->forceFill($payload)->save();

            return $row;
        }
        if ($existing) {
            $payload['updated_at'] = now();
            DB::table($table)->where('id', $this->key($existing))->update($payload);

            return $this->baseQuery($table)->where('id', $this->key($existing))->first();
        }
        $now = now();
        $payload['created_at'] = $payload['created_at'] ?? $now;
        $payload['updated_at'] = $now;
        $id = DB::table($table)->insertGetId($payload);

        return $this->baseQuery($table)->where('id', $id)->first();
    }

    private function remove(string $table, mixed $existing): void
    {
        if ($table === 'payments' && $existing instanceof Payment) {
            $reception = $existing->reception;
            JournalEntry::query()->where('source_type', 'payment')->where('source_id', $existing->id)->delete();
            $existing->delete();
            $reception?->recalculateTotals();

            return;
        }
        if ($existing instanceof Model && Schema::hasColumn($table, 'deleted_at')) {
            $fill = ['deleted_at' => now()];
            if (Schema::hasColumn($table, 'delete_reason')) {
                $fill['delete_reason'] = 'حذف از نرم‌افزار حسابداری';
            }
            $existing->forceFill($fill)->save();

            return;
        }
        if ($existing instanceof Model) {
            $existing->delete();

            return;
        }
        DB::table($table)->where('id', $this->key($existing))->delete();
        if (Schema::hasTable('desktop_sync_tombstones')) {
            DB::table('desktop_sync_tombstones')->insert([
                'table_name' => $table,
                'row_id' => (int) $this->key($existing),
                'deleted_at' => now(),
            ]);
        }
    }

    private function afterSave(string $table, mixed $saved): void
    {
        if (! $saved instanceof Payment) {
            return;
        }
        $saved->reception?->recalculateTotals();
        app(AccountingService::class)->postPayment($saved->fresh(['reception', 'customer']));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(string $table, array $data): array
    {
        $columns = Schema::getColumnListing($table);
        $out = [];
        foreach ($data as $key => $value) {
            if (! is_string($key) || ! in_array($key, $columns, true)) {
                continue;
            }
            if (in_array($key, ['id', 'password', 'remember_token', 'token_hash', 'token_encrypted', 'image_path'], true)) {
                continue;
            }
            if (is_array($value) && ! SyncTableRegistry::model($table)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            if (in_array($key, self::MONEY, true) && is_numeric($value)) {
                $value = (int) $value;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    private function remember(DesktopDevice $device, string $table, int $id, string $uid): void
    {
        $now = now();
        $key = [
            'token_id' => $device->id,
            'table_name' => $table,
            'desktop_uid' => $uid,
        ];
        $found = DB::table('desktop_sync_map')->where($key)->first();
        if ($found) {
            DB::table('desktop_sync_map')->where('id', $found->id)->update([
                'row_id' => $id,
                'last_origin' => 'desktop',
                'last_synced_at' => $now,
            ]);

            return;
        }
        DB::table('desktop_sync_map')->insert($key + [
            'row_id' => $id,
            'last_origin' => 'desktop',
            'last_synced_at' => $now,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function audit(DesktopDevice $device, string $endpoint, string $table, int $rowId, string $op, string $status, ?array $before, ?array $after, ?string $ip): void
    {
        if (! Schema::hasTable('desktop_sync_log')) {
            return;
        }
        DB::table('desktop_sync_log')->insert([
            'token_id' => $device->id,
            'endpoint' => $endpoint,
            'table_name' => $table,
            'row_id' => $rowId,
            'op' => $op,
            'status' => $status,
            'before' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'after' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $ip ? substr($ip, 0, 64) : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array{t: string, table: string, id: int}|null  $cursor
     * @param  list<string>|null  $only
     * @return list<array<string, mixed>>
     */
    private function collect(DesktopDevice $device, ?array $cursor, int $take, ?array $only): array
    {
        $items = [];
        foreach ($this->existingTables() as $table) {
            if ($only && ! in_array($table, $only, true)) {
                continue;
            }
            foreach ($this->fetch($table, $cursor, $take) as $row) {
                $items[] = $this->present($device, $table, $row, $this->opFor($row));
            }
        }
        if (Schema::hasTable('desktop_sync_tombstones')) {
            $query = DB::table('desktop_sync_tombstones')->orderBy('deleted_at')->orderBy('id');
            if ($cursor) {
                $query->where(function ($inner) use ($cursor) {
                    $inner->where('deleted_at', '>', $cursor['t'])
                        ->orWhere(function ($same) use ($cursor) {
                            $same->where('deleted_at', '=', $cursor['t'])
                                ->where(function ($tail) use ($cursor) {
                                    $tail->where('table_name', '>', $cursor['table'])
                                        ->orWhere(function ($row) use ($cursor) {
                                            $row->where('table_name', $cursor['table'])->where('row_id', '>', $cursor['id']);
                                        });
                                });
                        });
                });
            }
            if ($only) {
                $query->whereIn('table_name', $only);
            }
            foreach ($query->limit($take)->get() as $tomb) {
                $stamp = $this->stamp((string) $tomb->deleted_at);
                $tuple = ['t' => $stamp, 'table' => (string) $tomb->table_name, 'id' => (int) $tomb->row_id];
                if ($cursor && $this->tuple($tuple) <= $this->tuple($cursor)) {
                    continue;
                }
                $map = $this->mapFor($device, (string) $tomb->table_name, (int) $tomb->row_id);
                $items[] = [
                    'table' => (string) $tomb->table_name,
                    'id' => (int) $tomb->row_id,
                    'op' => 'delete',
                    'updated_at' => $this->iso($tomb->deleted_at),
                    'origin' => $map->last_origin ?? 'site',
                    'desktop_uid' => $map->desktop_uid ?? null,
                    'data' => ['id' => (int) $tomb->row_id],
                    'cursor' => $this->encodeCursor($stamp, (string) $tomb->table_name, (int) $tomb->row_id),
                    '_tuple' => $this->tuple($tuple),
                ];
            }
        }
        usort($items, fn ($a, $b) => $a['_tuple'] <=> $b['_tuple']);
        foreach ($items as &$item) {
            unset($item['_tuple']);
        }

        return $items;
    }

    /**
     * @param  array{t: string, table: string, id: int}|null  $cursor
     * @return list<object>
     */
    private function fetch(string $table, ?array $cursor, int $take): array
    {
        $time = $this->timeColumn($table);
        $query = $this->baseQuery($table)->orderBy($time ?: 'id')->orderBy('id');
        if ($table === 'app_settings') {
            $query->whereIn('key', SyncTableRegistry::SETTING_KEYS);
        }
        if ($cursor && $time) {
            if ($cursor['table'] < $table) {
                $query->where($time, '>=', $cursor['t']);
            } elseif ($cursor['table'] > $table) {
                $query->where($time, '>', $cursor['t']);
            } else {
                $query->where(function ($inner) use ($time, $cursor) {
                    $inner->where($time, '>', $cursor['t'])
                        ->orWhere(function ($same) use ($time, $cursor) {
                            $same->where($time, '=', $cursor['t'])->where('id', '>', $cursor['id']);
                        });
                });
            }
        } elseif ($cursor && $cursor['table'] === $table) {
            $query->where('id', '>', $cursor['id']);
        }
        $rows = [];
        foreach ($query->limit($take)->get() as $row) {
            $stamp = $time ? $this->stamp((string) data_get($row, $time)) : '';
            $tuple = ['t' => $stamp, 'table' => $table, 'id' => (int) data_get($row, 'id')];
            if ($cursor && $this->tuple($tuple) <= $this->tuple($cursor)) {
                continue;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(DesktopDevice $device, string $table, object $row, string $op): array
    {
        $time = $this->timeColumn($table);
        $stamp = $time ? $this->stamp((string) data_get($row, $time)) : '';
        $id = (int) data_get($row, 'id');
        $map = $this->mapFor($device, $table, $id);
        $data = $this->raw($table, $row);
        if ($op === 'delete' && ! isset($data['deleted_at'])) {
            $data = ['id' => $id];
        }

        return [
            'table' => $table,
            'id' => $id,
            'op' => $op,
            'updated_at' => $this->iso($time ? data_get($row, $time) : null),
            'origin' => $map->last_origin ?? 'site',
            'desktop_uid' => $map->desktop_uid ?? null,
            'data' => $data,
            'cursor' => $this->encodeCursor($stamp, $table, $id),
            '_tuple' => $this->tuple(['t' => $stamp, 'table' => $table, 'id' => $id]),
        ];
    }

    private function opFor(object $row): string
    {
        return filled(data_get($row, 'deleted_at')) ? 'delete' : 'upsert';
    }

    /**
     * @return array<string, mixed>
     */
    private function raw(string $table, object $row): array
    {
        $data = $row instanceof Model ? $row->getAttributes() : (array) $row;
        if ($table === 'users') {
            $data = array_intersect_key($data, array_flip(SyncTableRegistry::USER_COLUMNS));
        }
        foreach (self::SECRETS as $secret) {
            unset($data[$secret]);
        }
        if (isset($data['image_path'])) {
            $path = (string) $data['image_path'];
            unset($data['image_path']);
            $data['image_url'] = null;
            if ($path !== '') {
                try {
                    if (Storage::providesTemporaryUrls()) {
                        $data['image_url'] = Storage::temporaryUrl($path, now()->addMinutes(20));
                    }
                } catch (Throwable) {
                    $data['image_url'] = null;
                }
            }
        }
        foreach (self::MONEY as $column) {
            if (isset($data[$column]) && is_numeric($data[$column])) {
                $data[$column] = (int) $data[$column];
            }
        }
        if (isset($data['permissions']) && is_string($data['permissions'])) {
            $decoded = json_decode($data['permissions'], true);
            if (is_array($decoded)) {
                $data['permissions'] = $decoded;
            }
        }

        return $data;
    }

    private function baseQuery(string $table): mixed
    {
        $model = SyncTableRegistry::model($table);
        if ($model) {
            $query = $model::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $query->withTrashed();
            }

            return $query;
        }

        return DB::table($table);
    }

    private function reload(string $table, int $id): object
    {
        return $this->baseQuery($table)->where('id', $id)->first();
    }

    private function mapFor(DesktopDevice $device, string $table, int $id): ?object
    {
        if (! Schema::hasTable('desktop_sync_map')) {
            return null;
        }

        return DB::table('desktop_sync_map')
            ->where('token_id', $device->id)
            ->where('table_name', $table)
            ->where('row_id', $id)
            ->first();
    }

    private function timeColumn(string $table): ?string
    {
        if (Schema::hasColumn($table, 'updated_at')) {
            return 'updated_at';
        }
        if (Schema::hasColumn($table, 'created_at')) {
            return 'created_at';
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function existingTables(): array
    {
        return array_values(array_filter(SyncTableRegistry::READ, fn (string $table) => Schema::hasTable($table)));
    }

    private function key(mixed $row): int|string
    {
        return $row instanceof Model ? $row->getKey() : data_get($row, 'id');
    }

    /**
     * @param  array<string, string>|null  $errors
     * @return array<string, mixed>
     */
    private function rejected(string $uid, string $message, ?array $errors = null): array
    {
        $result = [
            'desktop_uid' => $uid,
            'status' => 'rejected',
            'message' => $message,
        ];
        if ($errors) {
            $result['errors'] = $errors;
        }

        return $result;
    }

    private function prune(): void
    {
        if (Schema::hasTable('desktop_sync_receipts')) {
            DB::table('desktop_sync_receipts')->where('created_at', '<', now()->subDays(30))->delete();
        }
        if (Schema::hasTable('desktop_sync_log')) {
            DB::table('desktop_sync_log')->where('created_at', '<', now()->subDays(90))->delete();
        }
    }

    private function limit(int $limit): int
    {
        if ($limit < 1) {
            return 500;
        }

        return min(1000, $limit);
    }

    private function stamp(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }

        return Carbon::parse($value)->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
    }

    private function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->timezone('Asia/Tehran')->format('Y-m-d\TH:i:sP');
    }

    /**
     * @param  array{t: string, table: string, id: int}  $cursor
     */
    private function tuple(array $cursor): string
    {
        return $cursor['t']."\n".$cursor['table']."\n".str_pad((string) $cursor['id'], 12, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{t: string, table: string, id: int}|null
     */
    private function decodeCursor(?string $since): ?array
    {
        if ($since === null || trim($since) === '') {
            return null;
        }
        $pad = strlen($since) % 4;
        $json = base64_decode(strtr($since.($pad ? str_repeat('=', 4 - $pad) : ''), '-_', '+/'), true);
        $data = is_string($json) ? json_decode($json, true) : null;
        if (! is_array($data) || ! isset($data['t'], $data['table'], $data['id'])) {
            abort(response()->json([
                'ok' => false,
                'message' => 'نشانگر همگام‌سازی معتبر نیست.',
            ], 422));
        }

        return ['t' => (string) $data['t'], 'table' => (string) $data['table'], 'id' => (int) $data['id']];
    }

    private function encodeCursor(string $time, string $table, int $id): string
    {
        $json = json_encode(['t' => $time, 'table' => $table, 'id' => $id], JSON_UNESCAPED_UNICODE);

        return rtrim(strtr(base64_encode((string) $json), '+/', '-_'), '=');
    }
}
