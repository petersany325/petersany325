<?php

namespace Plugins\Studio\src\Http\Controllers;

use App\Support\StudioHomeConfig;
use App\Support\StudioPlugins;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Plugins\Studio\Plugin as StudioPlugin;
use Plugins\Studio\src\Support\StudioSms;

class StudioSupportController
{
    public function show(): View
    {
        StudioPlugin::ensureBooted();
        $home = class_exists(StudioHomeConfig::class) ? StudioHomeConfig::get() : [];
        $user = Auth::user();
        $uid = (int) ($user->id ?? 0);

        return view('studio::support', [
            'phone' => (string) ($home['phone'] ?? '01144447220'),
            'title' => 'پشتیبانی HDD Land | تیکت، راهنما و پرسش‌های رایج',
            'description' => 'پشتیبانی مشتریان HDD Land: حسابداری، دستیار هوشمند، اپ همراه، حسابداری تعمیرکاران، سایت و بازیابی اطلاعات. تیکت ثبت کنید و پیامک تأیید بگیرید.',
            'tickets' => $uid > 0 ? StudioPlugins::tickets($uid) : [],
            'editions' => ['شرکتی', 'فروشگاهی', 'سوپرمارکت', 'پخش', 'رستوران', 'کافی‌شاپ', 'تولیدی', 'پیمانکاری', 'خدماتی', 'اتوماسیون اداری'],
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $key = 'support-ticket:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return back()->withInput()->with('error', 'تعداد ثبت تیکت در این ساعت زیاد شد. کمی بعد دوباره تلاش کنید.');
        }

        $products = [
            'acc' => 'حسابداری HDD Land',
            'ai' => 'دستیار هوشمند',
            'app' => 'اپ همراه',
            'rep' => 'حسابداری تعمیرکاران',
            'web' => 'سایت',
            'rec' => 'بازیابی اطلاعات',
        ];
        $editions = ['شرکتی', 'فروشگاهی', 'سوپرمارکت', 'پخش', 'رستوران', 'کافی‌شاپ', 'تولیدی', 'پیمانکاری', 'خدماتی', 'اتوماسیون اداری'];
        $name = trim((string) $request->input('name', ''));
        $phone = $this->latinDigits((string) $request->input('phone', ''));
        $product = (string) $request->input('product', '');
        $edition = trim((string) $request->input('edition', ''));
        $urgency = (string) $request->input('urgency', 'normal');
        $body = trim((string) $request->input('body', ''));
        if ($name === '' || $body === '' || ! isset($products[$product])) {
            return back()->withInput()->with('error', 'نام، محصول و شرح مشکل را بنویسید.');
        }
        if ($product === 'acc' && ! in_array($edition, $editions, true)) {
            return back()->withInput()->with('error', 'نسخهٔ صنفی حسابداری را انتخاب کنید.');
        }
        try {
            $phone = StudioSms::normalizePhone($phone);
        } catch (\Throwable) {
            $phone = '';
        }
        if (! StudioSms::isMobile($phone)) {
            return back()->withInput()->with('error', 'شماره موبایل را به شکل ۰۹xxxxxxxxx وارد کنید.');
        }
        $attachmentNote = '';
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
            $mime = (string) ($file->getMimeType() ?: '');
            if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024 || ! in_array($mime, $allowed, true)) {
                return back()->withInput()->with('error', 'پیوست فقط عکس jpg، png، webp یا PDF تا ۵ مگابایت است.');
            }
            $stored = $file->store('support-tickets/'.date('Y/m'), 'local');
            $attachmentNote = "\n\nپیوست داخلی: ".$stored;
        }
        $urgent = $urgency === 'stopped' ? 'کارم متوقف شده' : 'عادی';
        $subject = $products[$product].($product === 'acc' ? ' · '.$edition : '').' · '.$urgent;
        $full = $body.$attachmentNote;
        $user = Auth::user();
        $ticket = StudioPlugins::openTicket((int) ($user->id ?? 0), $name, $phone, $subject, $full);
        RateLimiter::hit($key, 3600);

        return back()->with('ticket', [
            'id' => (string) ($ticket['id'] ?? ''),
            'phone' => $phone,
        ]);
    }

    private function latinDigits(string $value): string
    {
        return strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']);
    }
}
