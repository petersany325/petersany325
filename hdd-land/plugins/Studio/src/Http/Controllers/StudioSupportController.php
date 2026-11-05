<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\StudioDesignCopy;
use App\Support\StudioHomeConfig;
use App\Support\StudioStaffDesk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Plugins\Studio\Plugin as StudioPlugin;
use Plugins\Studio\src\Support\StudioSms;

class StudioSupportController
{
    public function show(): View
    {
        StudioPlugin::ensureBooted();
        $home = class_exists(StudioHomeConfig::class) ? StudioHomeConfig::get() : [];
        $faq = [];
        if (class_exists(StudioDesignCopy::class)) {
            foreach (StudioDesignCopy::reasons() as $i => $row) {
                $faq[] = [
                    'q' => 'چرا فراز نت آمل؟',
                    'a' => $row,
                ];
                if ($i >= 4) {
                    break;
                }
            }
            $faq[] = [
                'q' => 'چطور سفارش طراحی سایت بدهم؟',
                'a' => 'از منوی طراحی سایت، نوع سایت را باز کنید و دکمه فروش یا تماس را بزنید. صفحه تماس همیشه باز است.',
            ];
            $faq[] = [
                'q' => 'نماینده چطور سهم می‌گیرد؟',
                'a' => 'بعد از ثبت فروش در کارتابل، درصد سهم همان فاکتور به حساب نماینده ثبت می‌شود. پرداخت بانکی واقعی بعد از دادن کلید درگاه فعال می‌شود.',
            ];
        }

        return view('studio::support', [
            'phone' => (string) ($home['phone'] ?? '01144447220'),
            'faq' => $faq,
            'title' => 'پشتیبانی فراز نت آمل',
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $name = trim((string) $request->input('name', ''));
        $phone = trim((string) $request->input('phone', ''));
        $subject = trim((string) $request->input('subject', 'پیام پشتیبانی'));
        $body = trim((string) $request->input('body', ''));
        if ($name === '' || $body === '') {
            return back()->withInput()->with('error', 'نام و متن پیام را بنویسید.');
        }
        try {
            $phone = StudioSms::normalizePhone($phone);
        } catch (\Throwable) {
        }
        $user = Auth::user();
        StudioStaffDesk::openTicket(
            (int) ($user->id ?? 0),
            $name,
            $phone,
            $subject,
            $body
        );

        return back()->with('success', 'پیام پشتیبانی ثبت شد. اگر شماره موبایل درست باشد پیامک تأیید می‌آید.');
    }
}
