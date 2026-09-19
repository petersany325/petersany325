<?php

namespace App\Http\Controllers;

use App\Services\CloudBackup\CloudBackupManager;
use App\Support\BackupSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BackupCloudController extends Controller
{
    public function __construct(private CloudBackupManager $clouds)
    {
    }

    public function connect(Request $request, string $provider)
    {
        if (! in_array($provider, BackupSettings::CLOUD_KEYS, true)) {
            abort(404);
        }

        if ($provider === 'mega') {
            $result = $this->clouds->mega()->connect();

            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->withFragment('backup')
                ->with($result['ok'] ? 'success' : 'error', $result['message'])
                ->with('settings_tab', 'backup');
        }

        $state = Str::random(40);
        $request->session()->put('backup_cloud_oauth_state', $state);
        $request->session()->put('backup_cloud_oauth_provider', $provider);

        $url = $provider === 'google'
            ? $this->clouds->google()->authUrl($state)
            : $this->clouds->onedrive()->authUrl($state);

        if (! $url) {
            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->withFragment('backup')
                ->with('error', 'ابتدا Client ID و Secret را ذخیره کنید، بعد اتصال را بزنید.')
                ->with('settings_tab', 'backup');
        }

        return redirect()->away($url);
    }

    public function callback(Request $request, string $provider)
    {
        if (! in_array($provider, ['google', 'onedrive'], true)) {
            abort(404);
        }

        $state = (string) $request->query('state', '');
        $expected = (string) $request->session()->pull('backup_cloud_oauth_state', '');
        $request->session()->forget('backup_cloud_oauth_provider');
        if ($state === '' || $expected === '' || ! hash_equals($expected, $state)) {
            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->withFragment('backup')
                ->with('error', 'اعتبارسنجی OAuth نامعتبر بود. دوباره تلاش کنید.')
                ->with('settings_tab', 'backup');
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->withFragment('backup')
                ->with('error', 'اجازه دسترسی داده نشد: '.$request->query('error'))
                ->with('settings_tab', 'backup');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()
                ->route('settings.index', ['tab' => 'backup'])
                ->withFragment('backup')
                ->with('error', 'کد مجوز از کلود دریافت نشد.')
                ->with('settings_tab', 'backup');
        }

        $result = $provider === 'google'
            ? $this->clouds->google()->handleCallback($code)
            : $this->clouds->onedrive()->handleCallback($code);

        return redirect()
            ->route('settings.index', ['tab' => 'backup'])
            ->withFragment('backup')
            ->with($result['ok'] ? 'success' : 'error', $result['message'])
            ->with('settings_tab', 'backup');
    }

    public function disconnect(string $provider)
    {
        if (! in_array($provider, BackupSettings::CLOUD_KEYS, true)) {
            abort(404);
        }
        BackupSettings::disconnectCloud($provider);

        return redirect()
            ->route('settings.index', ['tab' => 'backup'])
            ->withFragment('backup')
            ->with('success', 'اتصال '.BackupSettings::CLOUD_LABELS[$provider].' قطع شد.')
            ->with('settings_tab', 'backup');
    }

    public function test(string $provider)
    {
        if (! in_array($provider, BackupSettings::CLOUD_KEYS, true)) {
            abort(404);
        }

        $result = match ($provider) {
            'google' => $this->clouds->google()->test(),
            'onedrive' => $this->clouds->onedrive()->test(),
            'mega' => $this->clouds->mega()->test(),
        };

        return redirect()
            ->route('settings.index', ['tab' => 'backup'])
            ->withFragment('backup')
            ->with($result['ok'] ? 'success' : 'error', $result['message'])
            ->with('settings_tab', 'backup');
    }
}
