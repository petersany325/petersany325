<?php

namespace App\Http\Controllers;

use App\Models\DesktopDevice;
use App\Support\DesktopSync\Catalog;
use App\Support\DesktopSync\Engine;
use App\Support\DesktopSync\SyncException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesktopSyncController extends Controller
{
    public function spec(Request $request): JsonResponse
    {
        $device = $request->attributes->get('desktopDevice');

        return response()->json([
            'ok' => true,
            'device' => $device?->name,
            'server_time' => now()->toIso8601String(),
            'sync_url' => url('/api/desktop/v1/sync'),
            'ping_url' => url('/api/desktop/v1/ping'),
            'resources' => Catalog::names(),
            'rules' => [
                'اولین اتصال cursor را null بفرستید تا تصویر کامل داده‌ها، صفحه به صفحه، برسد.',
                'snapshot برگشتی را بدون تغییر در درخواست بعد بفرستید تا has_more تمام شود.',
                'بعد از آن cursor عددی را نگه دارید و فقط تغییرهای جدید را بفرستید و بگیرید.',
                'برای ویرایش، base_updated_at همان updated_at آخرین نسخه سرور باشد.',
                'اگر result برابر kept_server بود، ردیف محلی را با server عوض کنید.',
                'اگر sync_key سرور با client_sync_key فرق داشت، شناسه سرور را نگه دارید.',
                'حذف قبض و پرداخت پذیرفته نمی‌شود؛ وضعیت قبض را cancelled کنید.',
            ],
        ]);
    }

    public function ping(Request $request): JsonResponse
    {
        /** @var DesktopDevice $device */
        $device = $request->attributes->get('desktopDevice');
        $device->forceFill(['last_seen_at' => now()])->save();

        return response()->json([
            'ok' => true,
            'device' => $device->name,
            'server_time' => now()->toIso8601String(),
            'cursor' => (int) $device->last_cursor,
        ]);
    }

    public function sync(Request $request, Engine $engine): JsonResponse
    {
        /** @var DesktopDevice $device */
        $device = $request->attributes->get('desktopDevice');
        $changes = $request->json('changes', []);
        if (is_array($changes) && $changes !== [] && ! $device->allows('write')) {
            return response()->json([
                'ok' => false,
                'message' => 'این توکن اجازه این کار را ندارد.',
            ], 403);
        }

        try {
            $body = $engine->exchange($device, $request->json()->all());
        } catch (SyncException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], $e->status);
        }

        return response()->json($body);
    }
}
