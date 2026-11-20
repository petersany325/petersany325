<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\DesktopDevice;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LookupOption;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\SmsLog;
use App\Models\StockMovement;
use App\Models\SmsStatusRule;
use App\Models\Technician;
use App\Models\User;
use App\Services\SmsNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DesktopSyncV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_ping_requires_a_valid_token_and_describes_the_site(): void
    {
        $this->getJson('/api/desktop-sync/v1/ping')
            ->assertUnauthorized()
            ->assertJson(['ok' => false, 'message' => 'توکن همگام‌سازی معتبر نیست.']);

        $this->withToken('hds_not-a-real-token-value')
            ->getJson('/api/desktop-sync/v1/ping')
            ->assertUnauthorized();

        [$device, $token] = DesktopDevice::issue('حسابداری دفتر', null);
        $device->forceFill(['revoked_at' => now()])->save();
        $this->withToken($token)->getJson('/api/desktop-sync/v1/ping')->assertUnauthorized();

        [$device, $token] = DesktopDevice::issue('حسابداری دفتر', null);
        $body = $this->withToken($token)->getJson('/api/desktop-sync/v1/ping')->assertOk()->json();

        $this->assertTrue($body['ok']);
        $this->assertSame(1, $body['api_version']);
        $this->assertSame('IRT', $body['money_unit']);
        $this->assertSame('حسابداری دفتر', $body['token']['label']);
        $this->assertSame(['read', 'write'], $body['token']['scopes']);
        $this->assertContains('customers', $body['tables']);
        $this->assertNotNull($device->fresh()->last_seen_at);

        $device->forceFill(['scopes' => ['read']])->save();
        $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => '6f0c2c1e-9d7a-4c55-8a3e-0b0f6a1c2d3e',
            'items' => [],
        ])->assertForbidden();
    }

    public function test_changes_keep_same_second_rows_and_hide_secrets(): void
    {
        [, $token] = DesktopDevice::issue('دفتر', null);
        $stamp = now()->startOfSecond();
        foreach (['اول', 'دوم', 'سوم'] as $index => $name) {
            Customer::query()->create(['name' => $name, 'phone' => '0912100000'.$index]);
        }
        DB::table('customers')->update(['created_at' => $stamp, 'updated_at' => $stamp]);

        $seen = [];
        $cursor = null;
        for ($page = 0; $page < 5; $page++) {
            $response = $this->withToken($token)->getJson('/api/desktop-sync/v1/changes?'.http_build_query([
                'since' => $cursor,
                'limit' => 1,
                'tables' => 'customers',
            ]))->assertOk()->json();
            foreach ($response['items'] as $item) {
                $seen[] = $item['id'];
                $this->assertArrayNotHasKey('password', $item['data']);
            }
            $cursor = $response['next_cursor'];
            if (! $response['has_more']) {
                break;
            }
        }

        $this->assertCount(3, $seen);
        $this->assertSame($seen, array_values(array_unique($seen)));

        $user = User::query()->create([
            'name' => 'کاربر',
            'email' => 'secret-user@example.com',
            'password' => 'secret-secret',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $users = $this->withToken($token)->getJson('/api/desktop-sync/v1/changes?tables=users&limit=50')
            ->assertOk()->json('items');
        $row = collect($users)->firstWhere('id', $user->id);
        $this->assertNotNull($row);
        $this->assertArrayNotHasKey('password', $row['data']);
        $this->assertArrayNotHasKey('remember_token', $row['data']);
        $this->assertSame('کاربر', $row['data']['name']);
    }

    public function test_push_is_idempotent_resolves_refs_and_rejects_a_duplicate_receipt(): void
    {
        [, $token] = DesktopDevice::issue('دفتر', null);
        $batch = '11111111-2222-4333-8444-555555555555';
        $payload = [
            'batch_id' => $batch,
            'items' => [
                [
                    'table' => 'customers',
                    'op' => 'upsert',
                    'desktop_uid' => 'people:1',
                    'data' => ['name' => 'رضا', 'phone' => '09123334444'],
                ],
                [
                    'table' => 'receptions',
                    'op' => 'upsert',
                    'desktop_uid' => 'jobs:1',
                    'data' => [
                        'product_name' => 'هارد',
                        'customer_ref' => ['desktop_uid' => 'people:1'],
                        'receipt_no' => 'T-20N900',
                        'status' => 'received',
                    ],
                ],
            ],
        ];

        $first = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', $payload)->assertOk()->json();
        $this->assertSame('created', $first['results'][0]['status']);
        $this->assertSame('created', $first['results'][1]['status']);
        $reception = Reception::query()->find($first['results'][1]['site_id']);
        $this->assertSame($first['results'][0]['site_id'], $reception->customer_id);
        $this->assertSame('T-20N900', $reception->receipt_no);

        $again = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', $payload)->assertOk()->json();
        $this->assertSame($first['results'], $again['results']);
        $this->assertSame(1, Customer::query()->where('phone', '09123334444')->count());

        $dup = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => '22222222-3333-4444-8555-666666666666',
            'items' => [[
                'table' => 'receptions',
                'op' => 'upsert',
                'desktop_uid' => 'jobs:2',
                'data' => ['product_name' => 'هارد دیگر', 'receipt_no' => 'T-20N900', 'customer_id' => $reception->customer_id],
            ]],
        ])->assertOk()->json();
        $this->assertSame('rejected', $dup['results'][0]['status']);
        $this->assertArrayHasKey('receipt_no', $dup['results'][0]['errors']);

        $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => '33333333-4444-4555-8666-777777777777',
            'items' => [[
                'table' => 'users',
                'op' => 'upsert',
                'desktop_uid' => 'users:1',
                'data' => ['name' => 'نباید'],
            ]],
        ])->assertOk()->assertJsonPath('results.0.message', 'این جدول از نرم‌افزار قابل تغییر نیست.');
    }

    public function test_payment_push_updates_the_journal_and_a_hard_delete_is_visible(): void
    {
        [, $token] = DesktopDevice::issue('صندوق', null);
        $customer = Customer::query()->create(['name' => 'کیان', 'phone' => '09126667788']);
        $reception = Reception::query()->create([
            'ticket_no' => Reception::nextTicketNo(),
            'customer_id' => $customer->id,
            'product_name' => 'لپ‌تاپ',
            'labor_cost' => 150000,
            'total_amount' => 150000,
            'paid_amount' => 0,
            'received_at' => now(),
        ]);

        $pushed = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => '44444444-5555-4666-8777-888888888888',
            'items' => [[
                'table' => 'payments',
                'op' => 'upsert',
                'desktop_uid' => 'pay:1',
                'data' => [
                    'reception_id' => $reception->id,
                    'customer_id' => $customer->id,
                    'type' => 'refund',
                    'method' => 'cash',
                    'amount' => 25000,
                ],
            ]],
        ])->assertOk()->json();

        $this->assertSame('created', $pushed['results'][0]['status']);
        $payment = Payment::query()->find($pushed['results'][0]['site_id']);
        $this->assertSame(-25000, (int) $payment->amount);
        $this->assertTrue(JournalEntry::query()->where('source_type', 'payment')->where('source_id', $payment->id)->exists());

        $payment->delete();
        $items = $this->withToken($token)->getJson('/api/desktop-sync/v1/changes?tables=payments&limit=20')
            ->assertOk()->json('items');
        $deleted = collect($items)->firstWhere('op', 'delete');
        $this->assertNotNull($deleted);
        $this->assertSame($payment->id, $deleted['id']);
        $this->assertSame(['id' => $payment->id], $deleted['data']);
    }

    public function test_inbound_message_conflict_keeps_the_site_copy(): void
    {
        [, $token] = DesktopDevice::issue('دفتر', null);
        $customer = Customer::query()->create(['name' => 'مشتری', 'phone' => '09125556677']);
        $message = CustomerMessage::query()->create([
            'customer_id' => $customer->id,
            'body' => 'متن سایت',
        ]);

        $result = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => '55555555-6666-4777-8888-999999999999',
            'items' => [[
                'table' => 'customer_messages',
                'op' => 'upsert',
                'site_id' => $message->id,
                'desktop_uid' => 'msg:1',
                'base_updated_at' => now()->subDay()->toIso8601String(),
                'data' => ['body' => 'متن رایانه'],
            ]],
        ])->assertOk()->json('results.0');

        $this->assertSame('conflict', $result['status']);
        $this->assertSame('متن سایت', $message->fresh()->body);

        $read = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => '66666666-7777-4888-8999-aaaaaaaaaaaa',
            'items' => [[
                'table' => 'customer_messages',
                'op' => 'upsert',
                'site_id' => $message->id,
                'desktop_uid' => 'msg:1',
                'base_updated_at' => now()->subDay()->toIso8601String(),
                'data' => ['staff_read_at' => now()->toIso8601String()],
            ]],
        ])->assertOk()->json('results.0');
        $this->assertSame('updated', $read['status']);
        $this->assertNotNull($message->fresh()->staff_read_at);
    }

    public function test_desktop_origin_does_not_send_sms_and_snapshot_pages(): void
    {
        [, $token] = DesktopDevice::issue('دفتر', null);
        Customer::query()->create(['name' => 'دوم', 'phone' => '09129998876']);
        $customer = Customer::query()->create(['name' => 'نادر', 'phone' => '09129998877']);
        $reception = Reception::query()->create([
            'ticket_no' => Reception::nextTicketNo(),
            'customer_id' => $customer->id,
            'product_name' => 'فلش',
            'received_at' => now(),
        ]);
        $rule = SmsStatusRule::query()->create([
            'code' => 'received-test',
            'title' => 'پذیرش',
            'status_key' => 'received',
            'message_template' => 'سلام',
            'auto_send' => true,
            'on_create' => true,
        ]);
        app()->instance('sync.origin', 'desktop');
        $sent = app(SmsNotificationService::class)->sendForReception($reception, $rule);
        $this->assertTrue($sent['skipped']);
        $this->assertSame(0, SmsLog::query()->count());

        $page = $this->withToken($token)->getJson('/api/desktop-sync/v1/snapshot?table=customers&per_page=1')
            ->assertOk()->json();
        $this->assertSame(1, count($page['data']));
        $this->assertSame(2, $page['next_page']);
        $this->assertNotEmpty($page['cursor']);

        $status = $this->withToken($token)->getJson('/api/desktop-sync/v1/status')->assertOk()->json();
        $this->assertArrayHasKey('customers', $status['counts']);
        $this->assertNotEmpty($status['latest_cursor']);
    }

    public function test_menus_parts_and_books_sync_and_money_rows_are_not_deleted(): void
    {
        [$device, $token] = DesktopDevice::issue('دفتر', null);
        $ping = $this->withToken($token)->getJson('/api/desktop-sync/v1/ping')->assertOk()->json();
        foreach (['lookup_options', 'fault_types', 'parts', 'payments', 'journal_entries'] as $table) {
            $this->assertContains($table, $ping['tables']);
        }

        $pushed = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => '77777777-8888-4999-8aaa-bbbbbbbbbbbb',
            'items' => [[
                'table' => 'lookup_options',
                'op' => 'upsert',
                'desktop_uid' => 'menu:admission:1',
                'data' => ['group_key' => 'admission_type', 'name' => 'حضوری', 'sort_order' => 1, 'is_active' => true],
            ], [
                'table' => 'parts',
                'op' => 'upsert',
                'desktop_uid' => 'part:1',
                'data' => ['name' => 'هد هارد', 'code' => 'HDD-1', 'sale_price' => 250000],
            ]],
        ])->assertOk()->json();

        $this->assertSame('created', $pushed['results'][0]['status']);
        $this->assertSame('created', $pushed['results'][1]['status']);
        $this->assertSame('حضوری', LookupOption::query()->find($pushed['results'][0]['site_id'])->name);
        $this->assertNotNull(Part::query()->find($pushed['results'][1]['site_id'])->warehouse_id);

        $menus = $this->withToken($token)->getJson('/api/desktop-sync/v1/changes?tables=lookup_options&limit=50')
            ->assertOk()->json('items');
        $this->assertTrue(collect($menus)->contains(fn ($row) => ($row['data']['name'] ?? null) === 'حضوری'));

        $customer = Customer::query()->create(['name' => 'حذف‌شده', 'phone' => '09120001122']);
        $customer->delete();
        $live = $this->withToken($token)->getJson('/api/desktop-sync/v1/snapshot?table=customers&per_page=100')
            ->assertOk()->json('data');
        $this->assertFalse(collect($live)->contains(fn ($row) => (int) ($row['id'] ?? 0) === $customer->id));

        $payer = Customer::query()->create(['name' => 'پرداخت', 'phone' => '09120001123']);
        $ticket = Reception::query()->create([
            'ticket_no' => Reception::nextTicketNo(),
            'customer_id' => $payer->id,
            'product_name' => 'هارد',
            'received_at' => now(),
        ]);
        $payment = Payment::query()->create([
            'customer_id' => $payer->id,
            'reception_id' => $ticket->id,
            'type' => 'partial',
            'method' => 'cash',
            'amount' => 1000,
            'paid_at' => now(),
        ]);
        $blocked = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => '88888888-9999-4aaa-8bbb-cccccccccccc',
            'items' => [[
                'table' => 'payments',
                'op' => 'delete',
                'site_id' => $payment->id,
                'desktop_uid' => 'pay:keep',
            ]],
        ])->assertOk()->json('results.0');
        $this->assertSame('rejected', $blocked['status']);
        $this->assertNotNull(Payment::query()->find($payment->id));

        AppSetting::setValue('backup_cron_token', 'cron-secret-value');
        AppSetting::setValue('receipt_prefix', 'T-20N');
        $settings = $this->withToken($token)->getJson('/api/desktop-sync/v1/snapshot?table=app_settings&per_page=100')
            ->assertOk()->json('data');
        $blob = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('cron-secret-value', $blob);
        $this->assertStringContainsString('T-20N', $blob);
        $changes = json_encode($this->withToken($token)->getJson('/api/desktop-sync/v1/changes?tables=app_settings&limit=100')->assertOk()->json('items'), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('cron-secret-value', $changes);

        $device->reissue();
        $this->assertSame(substr($device->fresh()->plainToken(), 0, 12), $device->fresh()->prefix);
    }

    public function test_stock_and_replaced_journal_lines_stay_in_sync(): void
    {
        [, $token] = DesktopDevice::issue('انبار', null);
        $created = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => '99999999-aaaa-4bbb-8ccc-dddddddddddd',
            'items' => [[
                'table' => 'parts',
                'op' => 'upsert',
                'desktop_uid' => 'part:stock',
                'data' => ['name' => 'برد', 'purchase_price' => 1000, 'sale_price' => 2000, 'stock' => 4],
            ]],
        ])->assertOk()->json('results.0');
        $this->assertSame('created', $created['status']);
        $part = Part::query()->find($created['site_id']);
        $this->assertSame(4, (int) $part->stock);
        $opening = StockMovement::query()->where('part_id', $part->id)->first();
        $this->assertSame(4, (int) $opening->quantity);
        $this->assertSame('opening', $opening->doc_type);

        $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            'items' => [[
                'table' => 'parts',
                'op' => 'upsert',
                'desktop_uid' => 'part:stock',
                'data' => ['stock' => 6],
            ]],
        ])->assertOk()->assertJsonPath('results.0.status', 'updated');
        $this->assertSame(6, (int) $part->fresh()->stock);
        $this->assertSame(2, StockMovement::query()->where('part_id', $part->id)->count());

        $customer = Customer::query()->create(['name' => 'سند', 'phone' => '09124445566']);
        $reception = Reception::query()->create([
            'ticket_no' => Reception::nextTicketNo(),
            'customer_id' => $customer->id,
            'product_name' => 'قطعه',
            'labor_cost' => 10000,
            'total_amount' => 10000,
            'received_at' => now(),
        ]);
        $firstPay = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => 'bbbbbbbb-cccc-4ddd-8eee-ffffffffffff',
            'items' => [[
                'table' => 'payments',
                'op' => 'upsert',
                'desktop_uid' => 'pay:replace',
                'data' => [
                    'reception_id' => $reception->id,
                    'customer_id' => $customer->id,
                    'type' => 'partial',
                    'method' => 'cash',
                    'amount' => 4000,
                ],
            ]],
        ])->assertOk()->json('results.0');
        $oldLines = JournalLine::query()
            ->whereIn('journal_entry_id', JournalEntry::query()->where('source_type', 'payment')->where('source_id', $firstPay['site_id'])->pluck('id'))
            ->pluck('id');
        $this->assertNotEmpty($oldLines);

        $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => 'cccccccc-dddd-4eee-8fff-000000000001',
            'items' => [[
                'table' => 'payments',
                'op' => 'upsert',
                'desktop_uid' => 'pay:replace',
                'data' => ['amount' => 7000, 'type' => 'partial', 'method' => 'cash'],
            ]],
        ])->assertOk()->assertJsonPath('results.0.status', 'updated');

        $deleted = collect($this->withToken($token)->getJson('/api/desktop-sync/v1/changes?tables=journal_lines&limit=100')->assertOk()->json('items'))
            ->where('op', 'delete')
            ->pluck('id');
        foreach ($oldLines as $id) {
            $this->assertTrue($deleted->contains($id));
        }

        AppSetting::setValue('sms_panel_password', 'hidden-secret');
        AppSetting::setValue('receipt_prefix', 'R');
        $statusCount = $this->withToken($token)->getJson('/api/desktop-sync/v1/status')->assertOk()->json('counts.app_settings');
        $snapshotTotal = $this->withToken($token)->getJson('/api/desktop-sync/v1/snapshot?table=app_settings&per_page=100')->assertOk()->json('total');
        $this->assertSame($snapshotTotal, $statusCount);
    }

    public function test_rate_limit_returns_retry_after(): void
    {
        [, $token] = DesktopDevice::issue('محدود', null);
        $last = null;
        for ($i = 0; $i < 121; $i++) {
            $last = $this->withToken($token)->getJson('/api/desktop-sync/v1/ping');
        }
        $last->assertStatus(429);
        $this->assertTrue($last->headers->has('Retry-After'));
        $last->assertJsonPath('message', 'تعداد درخواست بیش از حد است. کمی بعد دوباره تلاش کنید.');
    }

    public function test_push_cannot_relink_staff_rewrite_sms_or_forge_cost_approval(): void
    {
        [, $token] = DesktopDevice::issue('دفتر', null);
        $user = User::query()->create([
            'name' => 'تعمیرکار سایت',
            'email' => 'tech-link@example.com',
            'password' => 'secret-secret',
            'role' => 'technician',
            'is_active' => true,
        ]);
        $customer = Customer::query()->create(['name' => 'مشتری', 'phone' => '09123334455']);
        $reception = Reception::query()->create([
            'ticket_no' => Reception::nextTicketNo(),
            'customer_id' => $customer->id,
            'product_name' => 'هارد',
            'received_at' => now(),
            'cost_approval_status' => 'pending',
        ]);
        $rule = SmsStatusRule::query()->create([
            'code' => 'ready-test',
            'title' => 'آماده',
            'status_key' => 'ready',
            'message_template' => 'قالب سایت',
            'auto_send' => false,
        ]);

        $pushed = $this->withToken($token)->postJson('/api/desktop-sync/v1/push', [
            'batch_id' => 'dddddddd-eeee-4fff-8000-111111111111',
            'items' => [
                [
                    'table' => 'technicians',
                    'op' => 'upsert',
                    'desktop_uid' => 'tech:linked',
                    'data' => ['name' => 'رضا', 'phone' => '09120001122', 'user_id' => $user->id, 'is_active' => true],
                ],
                [
                    'table' => 'sms_status_rules',
                    'op' => 'upsert',
                    'site_id' => $rule->id,
                    'desktop_uid' => 'sms:ready',
                    'data' => [
                        'title' => 'آماده تحویل',
                        'message_template' => 'قالب رایانه',
                        'auto_send' => true,
                        'send_coworker' => true,
                    ],
                ],
                [
                    'table' => 'receptions',
                    'op' => 'upsert',
                    'site_id' => $reception->id,
                    'desktop_uid' => 'receipt:approval',
                    'data' => [
                        'product_name' => 'هارد اکسترنال',
                        'cost_approval_status' => 'approved',
                        'customer_cost_approved_amount' => 900000,
                        'cost_confirmed_at' => now()->toIso8601String(),
                    ],
                ],
            ],
        ])->assertOk()->json('results');

        $this->assertSame(['created', 'updated', 'updated'], array_column($pushed, 'status'));
        $technician = Technician::query()->where('name', 'رضا')->first();
        $this->assertNotNull($technician);
        $this->assertNull($technician->user_id);
        $rule->refresh();
        $this->assertSame('آماده تحویل', $rule->title);
        $this->assertSame('قالب سایت', $rule->message_template);
        $this->assertFalse($rule->auto_send);
        $this->assertFalse((bool) $rule->send_coworker);
        $reception->refresh();
        $this->assertSame('هارد اکسترنال', $reception->product_name);
        $this->assertSame('pending', $reception->cost_approval_status);
        $this->assertNull($reception->customer_cost_approved_amount);
        $this->assertNull($reception->cost_confirmed_at);
    }

    public function test_status_cursor_stays_ahead_of_deletes(): void
    {
        [, $token] = DesktopDevice::issue('حذف', null);
        $kept = Customer::query()->create(['name' => 'مانده', 'phone' => '09121110001']);
        $kept->forceFill(['updated_at' => now()->subDay()])->save();
        $removed = Customer::query()->create(['name' => 'حذف‌شده', 'phone' => '09121110002']);
        $removed->delete();

        $cursor = $this->withToken($token)->getJson('/api/desktop-sync/v1/status')->assertOk()->json('latest_cursor');
        $this->assertNotEmpty($cursor);
        $after = $this->withToken($token)->getJson('/api/desktop-sync/v1/changes?'.http_build_query([
            'since' => $cursor,
            'tables' => 'customers',
            'limit' => 50,
        ]))->assertOk()->json('items');
        $this->assertFalse(collect($after)->contains(fn ($item) => (int) $item['id'] === $removed->id && $item['op'] === 'delete'));

        $option = LookupOption::query()->create([
            'group_key' => 'admission_type',
            'name' => 'حذف منو',
            'sort_order' => 9,
            'is_active' => true,
        ]);
        $option->delete();
        $cursor = $this->withToken($token)->getJson('/api/desktop-sync/v1/status')->assertOk()->json('latest_cursor');
        $after = $this->withToken($token)->getJson('/api/desktop-sync/v1/changes?'.http_build_query([
            'since' => $cursor,
            'tables' => 'lookup_options',
            'limit' => 50,
        ]))->assertOk()->json('items');
        $this->assertFalse(collect($after)->contains(fn ($item) => (int) $item['id'] === $option->id && $item['op'] === 'delete'));
    }

    public function test_main_site_does_not_install_the_customer_package(): void
    {
        config(['license.key' => '']);
        $this->assertTrue(\App\Support\ReleasePolicy::isMainSite());
        $this->assertFalse(\App\Support\ReleasePolicy::mayInstallCustomerPackage());

        config(['license.key' => 'shop-license']);
        $this->assertFalse(\App\Support\ReleasePolicy::isMainSite());
        $this->assertTrue(\App\Support\ReleasePolicy::mayInstallCustomerPackage());
    }

    public function test_unchecked_first_publish_is_not_the_stable_release(): void
    {
        $this->assertSame('1.3.81', \App\Support\ReleasePolicy::stableLatest(true, '1.3.81', ''));
        $this->assertSame('', \App\Support\ReleasePolicy::stableLatest(false, '1.3.81', ''));
        $this->assertSame('1.3.79', \App\Support\ReleasePolicy::stableLatest(false, '1.3.81', '1.3.79'));

        $dir = storage_path('app/releases');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = $dir.'/manifest.json';
        $previous = is_file($path) ? file_get_contents($path) : null;
        file_put_contents($path, json_encode([
            'channel' => 'stable',
            'latest' => '',
            'releases' => [[
                'version' => '1.3.81',
                'file' => 'hddland-1.3.81.zip',
                'sha256' => 'abc',
                'changelog' => ['نگه‌داشته شد'],
            ]],
        ], JSON_UNESCAPED_UNICODE));

        try {
            $local = app(\App\Services\AppUpdateService::class)->localLatest();
            $this->assertFalse($local['ok']);
            $this->assertNull($local['latest']);
        } finally {
            if ($previous === null) {
                @unlink($path);
            } else {
                file_put_contents($path, $previous);
            }
        }
    }
}
