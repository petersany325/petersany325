<?php

namespace App\Http\Controllers;

use App\Models\DeviceBlacklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceBlacklistController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $items = DeviceBlacklist::query()
            ->with('creator')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('serial_number', 'like', "%{$q}%")
                        ->orWhere('brand', 'like', "%{$q}%")
                        ->orWhere('model', 'like', "%{$q}%")
                        ->orWhere('reason', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('device-blacklists.index', compact('items', 'q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'serial_number' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $serial = isset($data['serial_number']) ? strtoupper(trim((string) $data['serial_number'])) : '';
        $brand = isset($data['brand']) ? trim((string) $data['brand']) : '';
        $model = isset($data['model']) ? strtoupper(trim((string) $data['model'])) : '';

        if ($serial === '' && $brand === '' && $model === '') {
            return back()->withErrors(['serial_number' => 'حداقل سریال یا برند/مدل لازم است.'])->withInput();
        }

        DeviceBlacklist::create([
            'serial_number' => $serial !== '' ? $serial : null,
            'brand' => $brand !== '' ? $brand : null,
            'model' => $model !== '' ? $model : null,
            'reason' => $data['reason'] ?? null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('device-blacklists.index')->with('success', 'دستگاه به لیست سیاه اضافه شد.');
    }

    public function toggle(DeviceBlacklist $deviceBlacklist)
    {
        $deviceBlacklist->update(['is_active' => ! $deviceBlacklist->is_active]);

        return back()->with('success', $deviceBlacklist->is_active ? 'مورد فعال شد.' : 'مورد غیرفعال شد.');
    }

    public function destroy(DeviceBlacklist $deviceBlacklist)
    {
        $deviceBlacklist->delete();

        return back()->with('success', 'مورد از لیست سیاه حذف شد.');
    }
}
