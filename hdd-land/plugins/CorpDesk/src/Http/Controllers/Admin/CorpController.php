<?php

namespace Plugins\CorpDesk\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugins\CorpDesk\src\Support\CorpCopy;

class CorpController extends Controller
{
    public function edit(string $page = 'enterprise')
    {
        if (! in_array($page, ['enterprise', 'cctv'], true)) {
            $page = 'enterprise';
        }
        try {
            \Plugins\CorpDesk\Plugin::registerViews();
        } catch (\Throwable) {
        }

        return view('corp-desk::admin', [
            'page' => $page,
            'copy' => CorpCopy::get($page),
            'preview' => $page === 'cctv' ? url('/cctv-projects') : url('/enterprise-storage'),
            'title' => $page === 'cctv' ? 'صفحه پروژه‌های نظارتی و CCTV' : 'صفحه تأمین هارد سازمانی',
        ]);
    }

    public function save(Request $request, string $page)
    {
        if (! in_array($page, ['enterprise', 'cctv'], true)) {
            $page = 'enterprise';
        }
        CorpCopy::save($page, $request->all());

        return back()->with('success', 'متن و لینک‌های صفحه ذخیره شد.');
    }
}
