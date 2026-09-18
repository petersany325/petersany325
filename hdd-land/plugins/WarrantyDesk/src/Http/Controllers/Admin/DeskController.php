<?php

namespace Plugins\WarrantyDesk\src\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\WarrantyDesk\Plugin;
use Plugins\WarrantyDesk\src\Models\WarrantyRequest;
use Plugins\WarrantyDesk\src\Support\PageCopy;
use Plugins\WarrantyDesk\src\Support\SmsHook;

class DeskController extends Controller
{
    public function index(Request $request)
    {
        Plugin::ensureSchema();
        $copy = PageCopy::get();
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $rows = WarrantyRequest::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('public_code', 'like', '%'.$q.'%')
                        ->orWhere('org_name', 'like', '%'.$q.'%')
                        ->orWhere('contact_name', 'like', '%'.$q.'%')
                        ->orWhere('mobile', 'like', '%'.$q.'%');
                });
            })
            ->when($status !== '' && isset(PageCopy::statuses()[$status]), fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        return view('warranty-desk::admin.index', [
            'copy' => $copy,
            'rows' => $rows,
            'q' => $q,
            'status' => $status,
            'statuses' => PageCopy::statuses(),
            'types' => PageCopy::applicantTypes(),
            'packages' => PageCopy::packages($copy),
        ]);
    }

    public function savePage(Request $request)
    {
        PageCopy::save($request->all());

        return redirect()
            ->to(url('/admin/warranty-register'))
            ->with('success', 'متن و تنظیمات صفحه ثبت گارانتی ذخیره شد.');
    }

    public function show(int $id)
    {
        Plugin::ensureSchema();
        $row = WarrantyRequest::query()->findOrFail($id);

        return view('warranty-desk::admin.show', [
            'row' => $row,
            'copy' => PageCopy::get(),
            'statuses' => PageCopy::statuses(),
            'types' => PageCopy::applicantTypes(),
            'packages' => PageCopy::packages(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        Plugin::ensureSchema();
        $row = WarrantyRequest::query()->findOrFail($id);
        $copy = PageCopy::get();
        $valid = implode(',', array_keys(PageCopy::statuses()));

        $data = $request->validate([
            'status' => 'required|in:'.$valid,
            'quote_amount' => 'nullable|integer|min:0',
            'quote_months' => 'nullable|integer|min:0|max:60',
            'package_name' => 'nullable|string|max:160',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $old = $row->status;
        $row->fill([
            'status' => $data['status'],
            'quote_amount' => $data['quote_amount'] !== null && $data['quote_amount'] !== '' ? (int) $data['quote_amount'] : null,
            'quote_months' => $data['quote_months'] !== null && $data['quote_months'] !== '' ? (int) $data['quote_months'] : null,
            'package_name' => trim((string) ($data['package_name'] ?? '')) ?: null,
            'admin_note' => trim((string) ($data['admin_note'] ?? '')) ?: null,
            'reviewed_by' => optional($request->user())->id,
            'reviewed_at' => now(),
        ]);
        $row->save();

        if ($old !== $row->status && ! empty($copy['sms_on_status'])) {
            $extra = '';
            if ($row->status === 'quote' && $row->quote_amount) {
                $extra = 'مبلغ پیشنهادی: '.number_format((int) $row->quote_amount).' تومان';
            }
            $ok = SmsHook::notifyStatus($row->mobile, $row->public_code, $row->statusLabel(), $extra);
            $row->appendSmsLog($ok ? 'ارسال شد: '.$row->statusLabel() : 'ارسال ناموفق: '.$row->statusLabel());
            $row->save();
        }

        return redirect()
            ->to(url('/admin/warranty-register/'.$row->id))
            ->with('success', 'پرونده به‌روز شد.');
    }
}
