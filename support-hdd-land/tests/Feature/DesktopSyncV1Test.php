<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\DesktopDevice;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\SmsLog;
use App\Models\SmsStatusRule;
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

    public function test_rate_limit_returns_retry_after(): void
    {
        [, $token] = DesktopDevice::issue('محدود', null);
        $last = null;
        for ($i = 0; $i < 121; $i++) {
            $last = $this->withToken($token)->getJson('/api/desktop-sync/v1/ping');
        }
        $last->assertStatus(429);
        $this->assertTrue($last->headers->has('Retry-After'));
    }
}
