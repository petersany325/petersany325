<?php

namespace Plugins\BizCard\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Plugins\BizCard\src\CardConfig;
use Plugins\BizCard\src\ClubStore;
use Plugins\BizCard\src\SmsSender;

class CardController extends Controller
{
    public function edit(): View
    {
        $s = CardConfig::get();

        return view('biz-card::admin.index', [
            's' => $s,
            'members' => ClubStore::members(),
            'counts' => ClubStore::counts(),
            'logs' => ClubStore::smsLogs(),
            'preview' => url('/card'),
            'vcard' => url('/card/vcard'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        CardConfig::save($request->all());

        return back()->with('success', 'تنظیمات کارت ویزیت ذخیره شد.');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $s = CardConfig::get();
        $phone = trim((string) $request->input('phone', ''));
        $name = trim((string) $request->input('name', ''));
        if (! SmsSender::isMobile($phone)) {
            return back()->with('sms_error', 'شماره موبایل معتبر وارد کنید.');
        }

        $sms = SmsSender::send($phone, (string) ($s['sms_tpl_link'] ?? ''), [
            'name' => $name,
            'link' => CardConfig::publicCardUrl(),
            'card' => CardConfig::publicCardUrl(),
        ], 'card_link');

        if (empty($sms['ok'])) {
            return back()->with('sms_error', 'ارسال لینک انجام نشد: '.($sms['response'] ?? 'خطای نامشخص'));
        }

        return back()->with('success', 'لینک کارت ویزیت برای '.$phone.' پیامک شد.');
    }

    public function sendClub(Request $request): RedirectResponse
    {
        $s = CardConfig::get();
        $name = trim((string) $request->input('name', ''));
        $phone = trim((string) $request->input('phone', ''));
        $res = ClubStore::requestJoin($name, $phone, 'admin');
        if (empty($res['ok'])) {
            return back()->with('sms_error', $res['error'] ?? 'ثبت درخواست ممکن نشد.');
        }
        if (! empty($res['already'])) {
            return back()->with('success', 'این شماره قبلاً تأیید شده است.');
        }

        $sms = SmsSender::send($phone, (string) ($s['sms_tpl_club'] ?? ''), [
            'name' => $name,
            'link' => ClubStore::confirmUrl($res['member']),
            'card' => CardConfig::publicCardUrl(),
        ], 'club_request');

        if (empty($sms['ok'])) {
            return back()->with('success', 'درخواست ثبت شد اما پیامک ارسال نشد: '.($sms['response'] ?? ''));
        }

        return back()->with('success', 'درخواست عضویت ثبت و پیامک تأیید ارسال شد.');
    }

    public function confirmMember(int $id): RedirectResponse
    {
        if (! ClubStore::confirmById($id)) {
            return back()->with('sms_error', 'تأیید عضو انجام نشد.');
        }

        return back()->with('success', 'عضویت تأیید شد.');
    }

    public function deleteMember(int $id): RedirectResponse
    {
        ClubStore::deleteMember($id);

        return back()->with('success', 'عضو حذف شد.');
    }
}
