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
            ->with(['customer', 'partner', 'partnerReferredTo', 'technician', 'partnerSecondaries'])
            ->whereNotNull('partner_flow')
            ->where(function ($q) {
                $q->whereNull('partner_receipt_role')
                    ->orWhere('partner_receipt_role', '!=', Reception::PARTNER_ROLE_SECONDARY);
            });

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
            ->where('status', '!=', 'cancelled')
            ->latest('id')->limit(80)->get();

        $inbound = (clone $base)->where('partner_flow', Reception::PARTNER_FLOW_INBOUND)
            ->where('partner_approval_status', 'approved')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->latest('id')->limit(80)->get();

        $outbound = (clone $base)->where('partner_flow', Reception::PARTNER_FLOW_OUTBOUND)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->latest('id')->limit(80)->get();

        $ready = (clone $base)->where(function ($q) {
            $q->where(function ($i) {
                $i->where('partner_flow', Reception::PARTNER_FLOW_INBOUND)
                    ->where('partner_approval_status', 'approved')
                    ->whereIn('status', ['ready', 'unrepairable']);
            })->orWhere(function ($o) {
                $o->where('partner_flow', Reception::PARTNER_FLOW_RETURNED)
                    ->whereIn('partner_approval_status', ['returned', 'rejected']);
            });
        })->latest('id')->limit(80)->get();

        $all = (clone $base)->latest('id')->limit(120)->get();

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

    /** Step 1–2 wizard: search receipt first, then pick partner. */
    public function sendWizard(Request $request): View
    {
        $this->network->syncPeers();

        $q = trim((string) $request->input('q', ''));
        $partnerQ = trim((string) $request->input('partner_q', ''));
        $receptionId = (int) $request->input('reception_id', 0);
        $selected = $receptionId > 0
            ? Reception::query()->with('customer')->find($receptionId)
            : null;

        if ($selected && ! $this->receptionIsReferable($selected)) {
            $selected = null;
            $receptionId = 0;
        }

        $receptions = collect();
        if (! $selected) {
            $query = Reception::query()
                ->with('customer')
                ->whereNotIn('status', ['delivered', 'cancelled'])
                ->where(function ($inner) {
                    $inner->whereNull('partner_flow')
                        ->orWhere(function ($o) {
                            $o->where('partner_flow', Reception::PARTNER_FLOW_OUTBOUND)
                                ->whereIn('partner_approval_status', ['rejected', 'returned', 'returned_to_origin']);
                        });
                });
            if ($q !== '') {
                $query->where(function ($inner) use ($q) {
                    $inner->where('receipt_no', 'like', '%'.$q.'%')
                        ->orWhere('ticket_no', 'like', '%'.$q.'%')
                        ->orWhere('serial_number', 'like', '%'.$q.'%')
                        ->orWhere('product_name', 'like', '%'.$q.'%')
                        ->orWhere('brand', 'like', '%'.$q.'%')
                        ->orWhere('model', 'like', '%'.$q.'%')
                        ->orWhereHas('customer', function ($c) use ($q) {
                            $c->where('name', 'like', '%'.$q.'%')
                                ->orWhere('phone', 'like', '%'.$q.'%')
                                ->orWhere('alias', 'like', '%'.$q.'%');
                        });
                });
            }
            $receptions = $query->latest('id')->limit($q !== '' ? 100 : 60)->get();
        }

        $partners = collect();
        if ($selected) {
            $pq = Partner::query()->where('is_active', true)->orderBy('org_name')->orderBy('name');
            if ($partnerQ !== '') {
                $pq->where(function ($inner) use ($partnerQ) {
                    $inner->where('org_name', 'like', '%'.$partnerQ.'%')
                        ->orWhere('shop_name', 'like', '%'.$partnerQ.'%')
                        ->orWhere('name', 'like', '%'.$partnerQ.'%')
                        ->orWhere('address', 'like', '%'.$partnerQ.'%')
                        ->orWhere('domain', 'like', '%'.$partnerQ.'%')
                        ->orWhere('phone', 'like', '%'.$partnerQ.'%');
                });
            }
            $partners = $pq->limit(200)->get();
        }

        return view('partners.send', [
            'q' => $q,
            'partnerQ' => $partnerQ,
            'receptions' => $receptions,
            'selected' => $selected,
            'partners' => $partners,
            'step' => $selected ? 2 : 1,
        ]);
    }

    public function sendRefer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reception_id' => ['required', 'exists:receptions,id'],
            'partner_id' => ['required', 'exists:partners,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $reception = Reception::query()->findOrFail((int) $data['reception_id']);
        $partner = Partner::query()->findOrFail((int) $data['partner_id']);

        if (! $this->receptionIsReferable($reception)) {
            return back()->with('error', 'این قبض قابل ارجاع نیست.')->withInput();
        }
        if (! $partner->is_active) {
            return back()->with('error', 'این همکار غیرفعال است.')->withInput();
        }

        $result = $this->network->sendReferral($reception, $partner, $data['note'] ?? null);
        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'])->withInput();
        }

        return redirect()
            ->route('partners.cartable', ['tab' => 'outbound'])
            ->with('success', $result['message']);
    }

    private function receptionIsReferable(Reception $reception): bool
    {
        if (in_array($reception->status, ['delivered', 'cancelled'], true)) {
            return false;
        }
        if ($reception->isPartnerInbound()) {
            return false;
        }
        if ($reception->partner_flow === Reception::PARTNER_FLOW_OUTBOUND
            && ! in_array((string) $reception->partner_approval_status, ['rejected', 'returned', 'returned_to_origin'], true)) {
            return false;
        }

        return true;
    }

    public function report(Request $request): View
    {
        $this->network->refreshOutboundStatuses();
        $flow = (string) $request->input('flow', 'all');
        $q = trim((string) $request->input('q', ''));
        $from = (string) $request->input('from', now()->subDays(30)->toDateString());
        $to = (string) $request->input('to', now()->toDateString());

        $query = Reception::query()
            ->with(['customer', 'partner', 'partnerReferredTo'])
            ->whereNotNull('partner_flow')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->latest('id');

        if ($flow !== 'all') {
            $query->where('partner_flow', $flow);
        }
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('receipt_no', 'like', '%'.$q.'%')
                    ->orWhere('partner_peer_receipt_no', 'like', '%'.$q.'%')
                    ->orWhere('serial_number', 'like', '%'.$q.'%')
                    ->orWhere('ticket_no', 'like', '%'.$q.'%')
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$q.'%')->orWhere('phone', 'like', '%'.$q.'%'))
                    ->orWhereHas('partner', fn ($p) => $p->where('name', 'like', '%'.$q.'%')->orWhere('domain', 'like', '%'.$q.'%'));
            });
        }

        $rows = $query->limit(300)->get();

        return view('partners.report', [
            'rows' => $rows,
            'flow' => $flow,
            'q' => $q,
            'from' => $from,
            'to' => $to,
            'stats' => [
                'total' => $rows->count(),
                'pending' => $rows->where('partner_approval_status', 'pending')->count(),
                'approved' => $rows->where('partner_approval_status', 'approved')->count(),
                'rejected' => $rows->where('partner_approval_status', 'rejected')->count(),
                'returned' => $rows->whereIn('partner_approval_status', ['returned', 'returned_to_origin'])->count(),
            ],
        ]);
    }

    public function createInbound(): RedirectResponse
    {
        return redirect()
            ->route('partners.cartable', ['tab' => 'pending'])
            ->with('success', 'قبض ارجاع شبکه به‌صورت خودکار در «منتظر قطعه / تأیید» می‌آید.');
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
            return back()->with('error', 'برای برگشت به همکار مبدأ از دکمه «ارجاع برگشت به همکار اول» استفاده کنید.');
        }

        $partner = Partner::query()->findOrFail((int) $data['partner_id']);
        if (! $partner->is_active) {
            return back()->with('error', 'این همکار غیرفعال است.');
        }

        $result = $this->network->sendReferral($reception, $partner, $data['note'] ?? null);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Pick a receipt after choosing a colleague from network search. */
    public function referForm(Request $request, Partner $partner): View|RedirectResponse
    {
        if (! $partner->is_active) {
            return redirect()->route('partners.index')->with('error', 'این همکار غیرفعال است.');
        }

        $this->network->syncPeers();

        $q = trim((string) $request->input('q', ''));
        $query = Reception::query()
            ->with('customer')
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->where(function ($inner) {
                $inner->whereNull('partner_flow')
                    ->orWhere(function ($o) {
                        $o->where('partner_flow', Reception::PARTNER_FLOW_OUTBOUND)
                            ->whereIn('partner_approval_status', ['rejected', 'returned', 'returned_to_origin']);
                    });
            });

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('receipt_no', 'like', '%'.$q.'%')
                    ->orWhere('ticket_no', 'like', '%'.$q.'%')
                    ->orWhere('serial_number', 'like', '%'.$q.'%')
                    ->orWhere('product_name', 'like', '%'.$q.'%')
                    ->orWhere('brand', 'like', '%'.$q.'%')
                    ->orWhere('model', 'like', '%'.$q.'%')
                    ->orWhereHas('customer', function ($c) use ($q) {
                        $c->where('name', 'like', '%'.$q.'%')
                            ->orWhere('phone', 'like', '%'.$q.'%')
                            ->orWhere('alias', 'like', '%'.$q.'%');
                    });
            });
        }

        $receptions = $query->latest('id')->limit($q !== '' ? 100 : 80)->get();

        return view('partners.refer', [
            'partner' => $partner,
            'receptions' => $receptions,
            'q' => $q,
        ]);
    }

    public function referSend(Request $request, Partner $partner): RedirectResponse
    {
        if (! $partner->is_active) {
            return redirect()->route('partners.index')->with('error', 'این همکار غیرفعال است.');
        }

        $data = $request->validate([
            'reception_id' => ['required', 'exists:receptions,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $reception = Reception::query()->findOrFail((int) $data['reception_id']);
        if (in_array($reception->status, ['delivered', 'cancelled'], true)) {
            return back()->with('error', 'این قبض قابل ارجاع نیست.');
        }
        if ($reception->isPartnerInbound() && $reception->partner_approval_status === 'pending') {
            return back()->with('error', 'این قبض هنوز تأیید نشده است.');
        }
        if ($reception->isPartnerInbound()) {
            return back()->with('error', 'برای برگشت به همکار مبدأ از کارتابل استفاده کنید.');
        }
        if ($reception->partner_flow === Reception::PARTNER_FLOW_OUTBOUND
            && ! in_array((string) $reception->partner_approval_status, ['rejected', 'returned', 'returned_to_origin'], true)) {
            return back()->with('error', 'این قبض قبلاً به همکار ارجاع شده است.');
        }

        $result = $this->network->sendReferral($reception, $partner, $data['note'] ?? null);

        if (! ($result['ok'] ?? false)) {
            return back()->with('error', $result['message'])->withInput();
        }

        return redirect()
            ->route('partners.cartable', ['tab' => 'outbound'])
            ->with('success', $result['message']);
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
        // Destination shop: return repaired device/receipt to originating colleague.
        if ($reception->isPartnerInbound() && $reception->partner_approval_status === 'approved') {
            $result = $this->network->returnToOrigin($reception);

            return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
        }

        // Origin shop: mark local outbound as physically returned / ready for customer exit.
        if ($reception->partner_flow === Reception::PARTNER_FLOW_OUTBOUND
            || $reception->partner_flow === Reception::PARTNER_FLOW_RETURNED) {
            if ($reception->blocksCustomerExitForPartner()) {
                return back()->with('error', 'تا برگشت از نماینده مقصد، خروج مشتری قفل است.');
            }
            $reception->update([
                'partner_flow' => Reception::PARTNER_FLOW_RETURNED,
                'partner_returned_at' => now(),
                'partner_approval_status' => $reception->partner_approval_status === 'returned'
                    ? 'returned'
                    : ($reception->partner_approval_status ?: 'returned'),
                'status' => in_array($reception->status, ['delivered', 'cancelled'], true) ? $reception->status : 'ready',
            ]);

            return back()->with('success', 'برگشت از همکار ثبت شد؛ آماده خروج/تحویل به مشتری.');
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
