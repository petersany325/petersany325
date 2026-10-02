<?php

namespace Plugins\BizCard\src\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Plugins\BizCard\src\CardConfig;
use Plugins\BizCard\src\ClubStore;
use Plugins\BizCard\src\SmsSender;

class PageController extends Controller
{
    public function show(): View|Response
    {
        $s = CardConfig::get();
        if (empty($s['enabled'])) {
            abort(404);
        }

        return view('biz-card::card', [
            's' => $s,
            'links' => CardConfig::links($s),
            'contactLinks' => array_values(array_filter(CardConfig::links($s), fn ($l) => ($l['group'] ?? '') === 'contact')),
            'siteLinks' => array_values(array_filter(CardConfig::links($s), fn ($l) => ($l['group'] ?? '') !== 'contact')),
        ]);
    }

    public function vcard(): Response
    {
        $s = CardConfig::get();
        if (empty($s['enabled']) || empty($s['show_save'])) {
            abort(404);
        }
        $name = (string) ($s['vcard_filename'] ?? 'sarzamin-hard.vcf');
        if (! str_ends_with(strtolower($name), '.vcf')) {
            $name .= '.vcf';
        }

        return response(CardConfig::buildVcard($s), 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function club(Request $request): RedirectResponse
    {
        $s = CardConfig::get();
        if (empty($s['enabled']) || empty($s['show_club'])) {
            return back()->with('club_error', 'عضویت باشگاه فعلاً فعال نیست.');
        }

        $name = trim((string) $request->input('name', ''));
        $phone = trim((string) $request->input('phone', ''));
        $res = ClubStore::requestJoin($name, $phone, 'card');
        if (empty($res['ok'])) {
            return back()->withInput()->with('club_error', $res['error'] ?? 'ثبت درخواست ممکن نشد.');
        }

        if (! empty($res['already'])) {
            return back()->with('club_ok', 'این شماره قبلاً در باشگاه مشتری تأیید شده است.');
        }

        $member = $res['member'];
        $confirm = ClubStore::confirmUrl($member);
        $sms = SmsSender::send($phone, (string) ($s['sms_tpl_club'] ?? ''), [
            'name' => $name,
            'link' => $confirm,
            'card' => CardConfig::publicCardUrl(),
        ], 'club_request');

        if (empty($sms['ok'])) {
            return back()->with('club_ok', ($s['club_success'] ?? 'درخواست ثبت شد.').' اگر پیامک نرسید، از پشتیبانی بخواهید لینک تأیید را دوباره بفرستد.');
        }

        return back()->with('club_ok', $s['club_success'] ?? 'درخواست ثبت شد. پیامک تأیید ارسال شد.');
    }

    public function confirm(string $token): View
    {
        $s = CardConfig::get();
        $before = ClubStore::findByToken($token);
        $wasPending = $before && ($before->status ?? '') === 'pending';
        $member = $before ? ClubStore::confirm($token) : null;
        $ok = $member !== null;
        if ($ok && $wasPending) {
            SmsSender::send((string) ($member->phone ?? ''), (string) ($s['sms_tpl_confirm'] ?? ''), [
                'name' => (string) ($member->name ?? ''),
                'link' => CardConfig::publicCardUrl(),
                'card' => CardConfig::publicCardUrl(),
            ], 'club_confirm');
        }

        return view('biz-card::confirm', [
            's' => $s,
            'ok' => $ok,
            'member' => $member,
        ]);
    }
}
