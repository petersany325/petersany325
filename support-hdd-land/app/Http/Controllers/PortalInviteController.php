<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PortalInviteBatch;
use App\Models\PortalInviteSend;
use App\Services\PortalInviteService;
use App\Support\PortalInviteTemplates;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PortalInviteController extends Controller
{
    public function index(PortalInviteService $service)
    {
        $stats = $service->stats();
        $batches = PortalInviteBatch::query()
            ->with('creator:id,name')
            ->latest('id')
            ->limit(12)
            ->get();

        $previewCounts = [];
        foreach (array_keys(PortalInviteBatch::FILTERS) as $filter) {
            $previewCounts[$filter] = $service->recipientIds($filter)->count();
        }

        return view('portal-invites.index', [
            'stats' => $stats,
            'batches' => $batches,
            'filters' => PortalInviteBatch::FILTERS,
            'previewCounts' => $previewCounts,
            'template' => PortalInviteTemplates::template(),
            'loginUrl' => PortalInviteTemplates::loginUrl(),
            'officePhone' => shop_office_phone(),
        ]);
    }

    public function saveTemplate(Request $request)
    {
        $data = $request->validate([
            'template' => ['required', 'string', 'max:1000'],
        ]);
        PortalInviteTemplates::save($data['template']);

        return back()->with('success', 'متن پیامک لینک کارتابل ذخیره شد.');
    }

    public function start(Request $request, PortalInviteService $service)
    {
        $data = $request->validate([
            'filter' => ['required', Rule::in(array_keys(PortalInviteBatch::FILTERS))],
            'template' => ['nullable', 'string', 'max:1000'],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'برای شروع ارسال باید تأیید کنید.',
        ]);

        if (filled($data['template'] ?? null)) {
            PortalInviteTemplates::save((string) $data['template']);
        }

        $batch = $service->createBatch($data['filter'], $data['template'] ?? null);

        if ($batch->total === 0) {
            return redirect()
                ->route('portal-invites.index')
                ->with('error', 'با این فیلتر گیرنده‌ای نیست.');
        }

        return redirect()->route('portal-invites.run', $batch);
    }

    public function run(PortalInviteBatch $batch, PortalInviteService $service)
    {
        if ($batch->isFinished()) {
            return redirect()
                ->route('portal-invites.report', ['batch_id' => $batch->id])
                ->with('success', 'ارسال دسته '.$batch->code.' تمام شد. موفق: '.$batch->sent_ok.' · ناموفق: '.$batch->sent_fail);
        }

        $result = $service->processChunk($batch);

        if ($result['done']) {
            return redirect()
                ->route('portal-invites.report', ['batch_id' => $batch->id])
                ->with('success', 'ارسال دسته '.$batch->code.' تمام شد. موفق: '.$result['batch']->sent_ok.' · ناموفق: '.$result['batch']->sent_fail);
        }

        return view('portal-invites.run', [
            'batch' => $result['batch'],
            'chunk' => $result,
        ]);
    }

    public function report(Request $request)
    {
        $status = trim((string) $request->get('status', '')); // ''|ok|fail
        $batchId = $request->filled('batch_id') ? (int) $request->get('batch_id') : null;
        $q = trim((string) $request->get('q', ''));

        $sends = PortalInviteSend::query()
            ->with(['customer', 'sender:id,name', 'batch:id,code'])
            ->when($batchId, fn ($query) => $query->where('batch_id', $batchId))
            ->when($status === 'ok', fn ($query) => $query->where('ok', true))
            ->when($status === 'fail', fn ($query) => $query->where('ok', false))
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('phone', 'like', $like)
                        ->orWhere('message', 'like', $like)
                        ->orWhereHas('customer', function ($c) use ($like) {
                            $c->where('name', 'like', $like)->orWhere('phone', 'like', $like);
                        });
                });
            })
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $batches = PortalInviteBatch::query()->latest('id')->limit(40)->get(['id', 'code', 'created_at']);

        return view('portal-invites.report', [
            'sends' => $sends,
            'batches' => $batches,
            'batchId' => $batchId,
            'status' => $status,
            'q' => $q,
            'stats' => [
                'ok' => PortalInviteSend::query()->when($batchId, fn ($q) => $q->where('batch_id', $batchId))->where('ok', true)->count(),
                'fail' => PortalInviteSend::query()->when($batchId, fn ($q) => $q->where('batch_id', $batchId))->where('ok', false)->count(),
                'total' => PortalInviteSend::query()->when($batchId, fn ($q) => $q->where('batch_id', $batchId))->count(),
            ],
        ]);
    }

    
    public function sendSingle(Request $request, PortalInviteService $service)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'name' => ['nullable', 'string', 'max:120'],
            'template' => ['nullable', 'string', 'max:1000'],
        ], [
            'phone.required' => 'شماره موبایل را وارد کنید.',
        ]);

        $result = $service->sendToManualPhone(
            (string) $data['phone'],
            $data['name'] ?? null,
            $data['template'] ?? null,
        );

        if (! $result['ok'] && ! $result['customer']) {
            return back()->withInput()->with('error', $result['message'] ?: 'شماره موبایل معتبر نیست.');
        }

        $who = $result['customer']?->name ?: $data['phone'];
        $extra = $result['created'] ? ' (مشتری جدید ثبت شد)' : '';

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'لینک کارتابل برای '.$who.' ارسال شد.'.$extra
                : 'ارسال ناموفق برای '.$who.': '.($result['message'] ?: 'خطای پنل پیامک')
        );
    }

    public function resend(Customer $customer, PortalInviteService $service)

    {
        $result = $service->sendToCustomer($customer);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? 'لینک کارتابل دوباره برای '.$customer->name.' ارسال شد.'
                : 'ارسال ناموفق: '.($result['message'] ?: 'خطای پنل پیامک')
        );
    }

    public function resendFailed(PortalInviteService $service)
    {
        $ids = $service->lastFailedCustomerIds();
        if ($ids->isEmpty()) {
            return back()->with('error', 'ارسال ناموفق بازی برای ارسال مجدد نیست.');
        }

        $batch = $service->createBatch(PortalInviteBatch::FILTER_FAILED);

        return redirect()->route('portal-invites.run', $batch);
    }
}
