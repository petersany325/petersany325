<?php

namespace App\Http\Controllers;

use App\Models\DesktopDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DesktopDeviceController extends Controller
{
    public function index()
    {
        $ready = Schema::hasTable('desktop_devices');

        return view('settings.desktop-sync', [
            'ready' => $ready,
            'devices' => $ready
                ? DesktopDevice::query()->latest('id')->get()
                : collect(),
            'copyId' => session('desktop_copy_id'),
            'syncUrl' => url('/api/desktop/v1/sync'),
            'pingUrl' => url('/api/desktop/v1/ping'),
            'specUrl' => url('/api/desktop/v1/spec'),
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('desktop_devices')) {
            return back()->with('error', 'جدول همگام‌سازی هنوز ساخته نشده. یک بار به‌روزرسانی نرم‌افزار را نصب کنید.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $device = DesktopDevice::issue($data['name'], $request->user()?->id)[0];

        return redirect()
            ->route('settings.desktop-sync')
            ->with('success', 'توکن «'.$device->name.'» ساخته شد. از منوی همان ردیف، کپی توکن را بزنید.')
            ->with('desktop_copy_id', $device->id);
    }

    public function reissue(DesktopDevice $device)
    {
        $device->reissue();

        return redirect()
            ->route('settings.desktop-sync')
            ->with('success', 'توکن جدید «'.$device->name.'» ساخته شد. توکن قبلی دیگر وصل نمی‌شود.')
            ->with('desktop_copy_id', $device->id);
    }

    public function revoke(DesktopDevice $device)
    {
        if ($device->revoked_at === null) {
            $device->forceFill(['revoked_at' => now()])->save();
        }

        return redirect()
            ->route('settings.desktop-sync')
            ->with('success', 'اتصال «'.$device->name.'» قطع شد.');
    }
}
