<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DesktopDevice;
use App\Models\FaultType;
use App\Models\JournalEntry;
use App\Models\Part;
use App\Models\Payment;
use App\Models\Reception;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesktopSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_token_is_required(): void
    {
        $this->postJson('/api/desktop/v1/sync', ['cursor' => null, 'changes' => []])
            ->assertUnauthorized()
            ->assertJson(['ok' => false]);
    }

    public function test_snapshot_and_push_keep_both_sides_current(): void
    {
        $site = Customer::query()->create([
            'name' => 'مشتری سایت',
            'phone' => '09121111111',
        ]);

        [$device, $token] = DesktopDevice::issue('پذیرش ۱', null);

        $seen = [];
        $snapshot = null;
        $cursor = null;
        for ($i = 0; $i < 12; $i++) {
            $response = $this->withToken($token)->postJson('/api/desktop/v1/sync', [
                'cursor' => null,
                'snapshot' => $snapshot,
                'changes' => [],
            ])->assertOk()->json();

            $this->assertTrue($response['ok']);
            $this->assertSame('snapshot', $response['mode']);
            foreach ($response['changes'] as $change) {
                $seen[$change['resource'].':'.$change['sync_key']] = $change;
            }
            $cursor = $response['cursor'];
            if (! ($response['has_more'] ?? false)) {
                break;
            }
            $snapshot = $response['snapshot'];
        }

        $this->assertArrayHasKey('customers:'.$site->sync_key, $seen);
        $this->assertSame('مشتری سایت', $seen['customers:'.$site->sync_key]['data']['name']);

        $desktopKey = '6f1c0b2e-3a4d-4e5f-8a9b-0c1d2e3f4a5b';
        $pushed = $this->withToken($token)->postJson('/api/desktop/v1/sync', [
            'cursor' => $cursor,
            'changes' => [[
                'resource' => 'customers',
                'sync_key' => $desktopKey,
                'op' => 'upsert',
                'data' => ['name' => 'مشتری رایانه', 'phone' => '09122222222'],
            ]],
        ])->assertOk()->json();

        $this->assertSame('applied', $pushed['applied'][0]['result']);
        $this->assertNotNull(Customer::query()->where('phone', '09122222222')->first());

        $site->refresh();
        $stale = $this->withToken($token)->postJson('/api/desktop/v1/sync', [
            'cursor' => $pushed['cursor'],
            'changes' => [[
                'resource' => 'customers',
                'sync_key' => $site->sync_key,
                'op' => 'upsert',
                'base_updated_at' => now()->subDay()->toIso8601String(),
                'data' => ['name' => 'نام قدیمی', 'phone' => $site->phone],
            ]],
        ])->assertOk()->json();

        $this->assertSame('kept_server', $stale['applied'][0]['result']);
        $this->assertSame('مشتری سایت', $site->fresh()->name);
        $this->assertTrue($device->fresh()->last_seen_at !== null);
    }

    public function test_desktop_can_open_a_ticket_for_a_customer_it_just_sent(): void
    {
        [, $token] = DesktopDevice::issue('پذیرش', null);
        $customerKey = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';
        $ticketKey = 'bbbbbbbb-cccc-4ddd-8eee-ffffffffffff';

        $response = $this->withToken($token)->postJson('/api/desktop/v1/sync', [
            'cursor' => 0,
            'changes' => [
                [
                    'resource' => 'customers',
                    'sync_key' => $customerKey,
                    'op' => 'upsert',
                    'data' => ['name' => 'رضا', 'phone' => '09123334444'],
                ],
                [
                    'resource' => 'receptions',
                    'sync_key' => $ticketKey,
                    'op' => 'upsert',
                    'data' => [
                        'product_name' => 'هارد ۱ ترا',
                        'customer_sync_key' => $customerKey,
                        'status' => 'received',
                    ],
                ],
                [
                    'resource' => 'receptions',
                    'sync_key' => $ticketKey,
                    'op' => 'delete',
                ],
            ],
        ])->assertOk()->json();

        $this->assertSame('applied', $response['applied'][0]['result']);
        $this->assertSame('applied', $response['applied'][1]['result']);
        $this->assertSame('rejected', $response['applied'][2]['result']);
        $ticket = Reception::query()->where('sync_key', $ticketKey)->first();
        $this->assertNotNull($ticket);
        $this->assertSame('هارد ۱ ترا', $ticket->product_name);
        $this->assertNotSame('', (string) $ticket->ticket_no);
    }

    public function test_same_ticket_number_does_not_merge_two_jobs(): void
    {
        [, $token] = DesktopDevice::issue('پذیرش', null);
        $customer = Customer::query()->create(['name' => 'مونا', 'phone' => '09124445566']);
        $number = Reception::nextTicketNo();
        $existing = Reception::query()->create([
            'ticket_no' => $number,
            'customer_id' => $customer->id,
            'product_name' => 'قبض سایت',
            'received_at' => now(),
        ]);
        $desktopKey = 'cccccccc-dddd-4eee-8fff-aaaaaaaaaaaa';

        $response = $this->withToken($token)->postJson('/api/desktop/v1/sync', [
            'cursor' => 0,
            'changes' => [[
                'resource' => 'receptions',
                'sync_key' => $desktopKey,
                'op' => 'upsert',
                'data' => [
                    'ticket_no' => $number,
                    'product_name' => 'قبض رایانه',
                    'customer_sync_key' => $customer->sync_key,
                    'status' => 'received',
                ],
            ]],
        ])->assertOk()->json();

        $this->assertSame('applied', $response['applied'][0]['result']);
        $this->assertSame('قبض سایت', $existing->fresh()->product_name);
        $created = Reception::query()->where('sync_key', $desktopKey)->first();
        $this->assertNotNull($created);
        $this->assertNotSame($existing->id, $created->id);
        $this->assertNotSame($number, $created->ticket_no);
    }

    public function test_delete_retires_records_that_are_still_in_use_and_frees_a_phone(): void
    {
        [, $token] = DesktopDevice::issue('پذیرش', null);
        $customer = Customer::query()->create(['name' => 'ندا', 'phone' => '09125556677']);
        $fault = FaultType::query()->create(['name' => 'بدسکتور', 'is_active' => true]);
        Reception::query()->create([
            'ticket_no' => Reception::nextTicketNo(),
            'customer_id' => $customer->id,
            'fault_type_id' => $fault->id,
            'product_name' => 'هارد',
            'received_at' => now(),
        ]);
        $part = Part::query()->create(['name' => 'برد', 'stock' => 2, 'is_active' => true]);
        DB::table('stock_movements')->insert([
            'part_id' => $part->id,
            'type' => 'in',
            'quantity' => 2,
            'stock_after' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withToken($token)->postJson('/api/desktop/v1/sync', [
            'cursor' => 0,
            'changes' => [
                ['resource' => 'fault_types', 'sync_key' => $fault->sync_key, 'op' => 'delete'],
                ['resource' => 'parts', 'sync_key' => $part->sync_key, 'op' => 'delete'],
                ['resource' => 'customers', 'sync_key' => $customer->sync_key, 'op' => 'delete'],
            ],
        ])->assertOk()->json();

        $this->assertSame('applied', $response['applied'][0]['result']);
        $this->assertFalse((bool) $fault->fresh()->is_active);
        $this->assertFalse((bool) $part->fresh()->is_active);
        $this->assertNotNull(DB::table('stock_movements')->where('part_id', $part->id)->first());
        $this->assertNull(Customer::query()->where('phone', '09125556677')->first());

        $again = $this->withToken($token)->postJson('/api/desktop/v1/sync', [
            'cursor' => $response['cursor'],
            'changes' => [[
                'resource' => 'customers',
                'sync_key' => 'dddddddd-eeee-4fff-8aaa-bbbbbbbbbbbb',
                'op' => 'upsert',
                'data' => ['name' => 'ندای جدید', 'phone' => '09125556677'],
            ]],
        ])->assertOk()->json();

        $this->assertSame('applied', $again['applied'][0]['result']);
        $this->assertSame('ندای جدید', Customer::query()->where('phone', '09125556677')->value('name'));
    }

    public function test_desktop_payment_updates_the_ticket_and_the_journal(): void
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

        $response = $this->withToken($token)->postJson('/api/desktop/v1/sync', [
            'cursor' => 0,
            'changes' => [[
                'resource' => 'payments',
                'sync_key' => 'eeeeeeee-ffff-4aaa-8bbb-cccccccccccc',
                'op' => 'upsert',
                'data' => [
                    'reception_sync_key' => $reception->sync_key,
                    'customer_sync_key' => $customer->sync_key,
                    'type' => 'partial',
                    'method' => 'cash',
                    'amount' => 40000,
                ],
            ]],
        ])->assertOk()->json();

        $this->assertSame('applied', $response['applied'][0]['result']);
        $this->assertSame(40000, (int) $reception->fresh()->paid_amount);
        $payment = Payment::query()->where('reception_id', $reception->id)->first();
        $this->assertNotNull($payment);
        $this->assertTrue(
            JournalEntry::query()->where('source_type', 'payment')->where('source_id', $payment->id)->exists()
        );
    }

    public function test_new_part_lands_in_the_default_warehouse(): void
    {
        [, $token] = DesktopDevice::issue('انبار', null);

        $this->withToken($token)->postJson('/api/desktop/v1/sync', [
            'cursor' => 0,
            'changes' => [[
                'resource' => 'parts',
                'sync_key' => 'ffffffff-aaaa-4bbb-8ccc-dddddddddddd',
                'op' => 'upsert',
                'data' => ['name' => 'هد', 'stock' => 3],
            ]],
        ])->assertOk();

        $part = Part::query()->where('sync_key', 'ffffffff-aaaa-4bbb-8ccc-dddddddddddd')->first();
        $this->assertNotNull($part);
        $this->assertSame(Warehouse::defaultId(), $part->warehouse_id);
    }

    public function test_settings_page_lists_the_desktop_link(): void
    {
        $admin = User::query()->create([
            'name' => 'مدیر',
            'email' => 'admin@example.com',
            'password' => 'secret-secret',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/settings/desktop-sync')
            ->assertOk()
            ->assertSee('همگام‌سازی نرم‌افزار رایانه')
            ->assertSee('/api/desktop/v1/sync');
    }

    public function test_revoked_token_is_rejected(): void
    {
        [, $token] = DesktopDevice::issue('صندوق', null);
        DesktopDevice::findByToken($token)->forceFill(['revoked_at' => now()])->save();

        $this->withToken($token)->getJson('/api/desktop/v1/ping')->assertUnauthorized();
    }
}
