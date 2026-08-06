<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Services\Installer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallController extends Controller
{
    public function show(): View
    {
        $requirements = [
            'PHP >= 8.3' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'OpenSSL' => extension_loaded('openssl'),
            'Mbstring' => extension_loaded('mbstring'),
            'Tokenizer' => extension_loaded('tokenizer'),
            'XML' => extension_loaded('xml'),
            'Ctype' => extension_loaded('ctype'),
            'JSON' => extension_loaded('json'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'writable: storage' => is_writable(storage_path()),
            'writable: bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            'writable: .env' => is_writable(base_path()) || (file_exists(base_path('.env')) && is_writable(base_path('.env'))),
        ];

        return view('install.show', [
            'requirements' => $requirements,
            'ready' => ! in_array(false, $requirements, true),
        ]);
    }

    public function store(Request $request): RedirectResponse|View
    {
        $data = $request->validate([
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ], [], [
            'db_host' => 'هاست دیتابیس',
            'db_port' => 'پورت',
            'db_database' => 'نام دیتابیس',
            'db_username' => 'نام کاربری',
            'db_password' => 'رمز عبور',
        ]);

        $appUrl = rtrim($request->getSchemeAndHttpHost().$request->getBasePath(), '/');

        $result = Installer::install([
            'host' => $data['db_host'],
            'port' => (string) $data['db_port'],
            'database' => $data['db_database'],
            'username' => $data['db_username'],
            'password' => $data['db_password'] ?? '',
        ], $appUrl);

        if (! $result['ok']) {
            return back()->withInput()->with('error', $result['message']);
        }

        return view('install.done', [
            'adminEmail' => $result['admin_email'],
            'adminPassword' => $result['admin_password'],
            'adminUrl' => url('/admin'),
            'siteUrl' => url('/'),
        ]);
    }
}
