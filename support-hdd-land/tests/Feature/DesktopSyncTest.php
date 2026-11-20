<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DesktopDevice;
use App\Models\Reception;
use App\Models\User;
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
