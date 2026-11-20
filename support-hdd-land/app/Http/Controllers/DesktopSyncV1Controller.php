<?php

namespace App\Http\Controllers;

use App\Models\DesktopDevice;
use App\Support\DesktopSync\SiteSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DesktopSyncV1Controller extends Controller
{
    public function ping(Request $request, SiteSync $sync): JsonResponse
    {
        return response()->json($sync->ping($this->device($request)));
    }

    public function changes(Request $request, SiteSync $sync): JsonResponse
    {
        $tables = $request->query('tables');
        $only = is_string($tables) && trim($tables) !== ''
            ? array_values(array_filter(array_map('trim', explode(',', $tables))))
            : null;

        return response()->json($sync->changes(
            $this->device($request),
            $request->query('since') !== null ? (string) $request->query('since') : null,
            (int) $request->query('limit', 500),
            $only,
        ));
    }

    public function snapshot(Request $request, SiteSync $sync): JsonResponse
    {
        $table = trim((string) $request->query('table', ''));
        if ($table === '') {
            return response()->json([
                'ok' => false,
                'message' => 'نام جدول لازم است.',
            ], 422);
        }
        $payload = $sync->snapshot(
            $this->device($request),
            $table,
            (int) $request->query('page', 1),
            (int) $request->query('per_page', 500),
        );
        $status = (int) ($payload['status'] ?? 200);
        unset($payload['status']);

        return response()->json($payload, $status);
    }

    public function status(SiteSync $sync): JsonResponse
    {
        return response()->json($sync->status());
    }

    public function push(Request $request, SiteSync $sync): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'batch_id' => ['required', 'uuid'],
            'items' => ['required', 'array', 'max:200'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'اطلاعات ارسالی کامل نیست.',
                'errors' => $validator->errors(),
            ], 422);
        }
        $data = $validator->validated();

        return response()->json($sync->push(
            $this->device($request),
            $data['batch_id'],
            $data['items'],
            $request->ip(),
        ));
    }

    private function device(Request $request): DesktopDevice
    {
        $device = $request->attributes->get('desktopDevice');
        if (! $device instanceof DesktopDevice) {
            abort(401, 'توکن همگام‌سازی معتبر نیست.');
        }

        return $device;
    }
}
