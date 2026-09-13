<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Reception;
use App\Services\PartnerNetworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Network partner referral cartable: auto peers + approve/reject inbound receipts.
 */
class PartnerReferralController extends Controller
{
    public function __construct(private PartnerNetworkService $network)
    {
    }

    public function cartable(Request $request): View
    {
        $this->network->syncPeers();
        $pull = $this->network->pullInbox();
        $this->network->refreshOutboundStatuses();

        $tab = (string) $request->input('tab', 'pending');
        if (! in_array($tab, ['pending', 'inbound', 'outbound', 'ready', 'all'], true)) {
            $tab = 'pending';
        }
        $q = trim((string) $request->input('q', ''));

        $base = Reception::query()
            ->with(['customer', 'partner', 'partnerReferredTo', 'technician'])
            ->whereNotNull('partner_flow')
            ->whereNotIn('status', ['cancelled']);

        if ($q !== '') {
            $base->where(function ($inner) use ($q) {
                $inner->where('ticket_no', 'like', '%'.$q.'%')
                    ->orWhere('receipt_no', 'like', '%'.$q.'%')
                    ->orWhere('serial_number', 'like', '%'.$q.'%')
                    ->orWhere('partner_peer_receipt_no', 'like', '%'.$q.'%')
                    ->orWhereHas('partner', fn ($p) => $p->where('name', 'like', '%'.$q.'%')->orWhere('shop_name', 'like', '%'.$q.'%')->orWhere('domain', 'like', '%'.$q.'%'))
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$q.'%')->orWhere('phone', 'like', '%'.$q.'%'));
            });
        }

        $pending = (clone $base)->where('partner_flow', Reception::PARTNER_FLOW_INBOUND)
            ->where('partner_approval_status', 'pending')
            ->latest('id')->limit(80)->get();

        $inbound = (clone $base)->where('partner_flow', Reception::PARTNER_FLOW_INBOUND)
            ->where(function ($q) {
                $q->whereNull('partner_approval_status')
                    ->orWhere('partner_approval_status', 'approved');
            })
            ->whereNotIn('status', ['delivered'])
            ->latest('id')->limit(80)->get();

        $outbound = (clone $base)->where('partner_flow', Reception::PARTNER_FLOW_OUTBOUND)
            ->whereNotIn('status', ['delivered'])
            ->latest('id')->limit(80)->get();

        $ready = (clone $base)->where('partner_flow', Reception::PARTNER_FLOW_INBOUND)
            ->where('partner_approval_status', 'approved')
            ->whereIn('status', ['ready', 'unrepairable'])
            ->latest('id')->limit(80)->get();

        $all = (clone $base)->latest('id')->limit(100)->get();

        $list = match ($tab) {
            'inbound' => $inbound,
            'outbound' => $outbound,
            'ready' => $ready,
            'all' => $all,
            default => $pending,
        };

        return view('partners.cartable', [
            'tab' => $tab,
            'q' => $q,
            'items' => $list,
            'stats' => [
                'pending' => $pending->count(),
                'inbound' => $inbound->count(),
                'outbound' => $outbound->count(),
                'ready' => $ready->count(),
            ],
            'partners' => Partner::query()->where('is_active', true)->orderBy('name')->get(),
            'pull' => $pull,
        ]);
    }

    public function createInbound(): RedirectResponse
    {
        return redirect()
            ->route('partners.cartable', ['tab' => 'pending'])
            ->with('success', 'قبض ارجاع شبکه به‌صورت خودکار در «در انتظار تأیید» می‌آید؛ پذیرش دستی لازم نیست.');
    }

    public function storeInbound(): RedirectResponse
    {
        return $this->createInbound();
    }

    public function referOut(Request $request, Reception $reception): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => ['required', 'exists:partners,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($reception->isPartnerInbound() && $reception->partner_approval_status === 'pending') {
            return back()->with('error', 'این قبض هنوز تأیید نشده است.');
        }
        if ($reception->isPartnerInbound()) {
            return back()->with('error', 'قبض ورودی شبکه را دوباره به همکار دیگر ارجاع ندهید؛ ابتدا کار را تمام و برگردانید.');
        }

        $partner = Partner::query()->findOrFail((int) $data['partner_id']);
        if (! $partner->is_active) {
            return back()->with('error', 'این همکار غیرفعال است.');
        }

        $result = $this->network->sendReferral($reception, $partner, $data['note'] ?? null);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function approve(Request $request, Reception $reception): RedirectResponse
    {
        $result = $this->network->approveInbound($reception);

        return redirect()
            ->route('receptions.show', $reception)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function reject(Request $request, Reception $reception): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $result = $this->network->rejectInbound($reception, (string) ($data['reason'] ?? ''));

        return redirect()
            ->route('partners.cartable', ['tab' => 'pending'])
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function markReturned(Request $request, Reception $reception): RedirectResponse
    {
        if ($reception->partner_flow === Reception::PARTNER_FLOW_OUTBOUND) {
            $reception->update([
                'partner_flow' => Reception::PARTNER_FLOW_RETURNED,
                'partner_returned_at' => now(),
                'status' => in_array($reception->status, ['delivered', 'cancelled'], true) ? $reception->status : 'ready',
            ]);

            return back()->with('success', 'برگشت از همکار ثبت شد؛ آماده تحویل به مشتری.');
        }

        if ($reception->isPartnerInbound() && $reception->partner_approval_status === 'approved') {
            $reception->update([
                'partner_returned_at' => now(),
                'status' => 'ready',
            ]);

            return back()->with('success', 'آماده برگشت به همکار مبدأ علامت خورد.');
        }

        return back()->with('error', 'این قبض برای برگشت آماده نیست.');
    }

    public function pull(): RedirectResponse
    {
        $this->network->syncPeers();
        $pull = $this->network->pullInbox();
        $this->network->refreshOutboundStatuses();

        return back()->with($pull['ok'] ? 'success' : 'error', $pull['message']);
    }
}
