<?php

namespace Plugins\Studio\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\StudioChat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudioChatAdminController extends Controller
{
    public function edit(): View
    {
        return view('studio::admin.chat', [
            'chat' => StudioChat::get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->boolean('reset_chat')) {
            StudioChat::reset();

            return back()->with('success', 'تنظیمات چت به پیش‌فرض سایت برگشت.');
        }

        $data = $request->all();
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = $file && $file->isValid() ? (string) $file->getMimeType() : '';
            if (! $file || ! isset($map[$mime]) || $file->getSize() > 1500000) {
                return back()->withInput()->with('error', 'لوگوی چت باید jpg، png یا webp و کمتر از ۱.۵ مگابایت باشد.');
            }
            $dir = public_path('images/chat');
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = 'guide.'.$map[$mime];
            $dest = $dir.DIRECTORY_SEPARATOR.$name;
            if (is_file($dest)) {
                unlink($dest);
            }
            $file->move($dir, $name);
            $data['avatar'] = 'images/chat/'.$name;
        }

        StudioChat::save($data);

        return back()->with('success', 'تنظیمات چت ذخیره شد.');
    }
}
