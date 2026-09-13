<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Reception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Partner (colleague shop) referral cartable + reception actions.
 * End customer deals only with partner shop #1; this shop bills that partner.
 */
class PartnerReferralController extends Controller
{
    public function cartable(Request $request): View
    {
        $tab = (string) $request->input('tab', 'inbound');
        if (! in_array($tab, ['inbound', 'outbound', 'ready', 'all'], true)) {
            $tab = 'inbound';
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
                    ->orWhere('partner_end_customer_note', 'like', '%'.$q.'%')
                    ->orWhereHas('partner', fn ($p) => $p->where('name', 'like', '%'.$q.'%')->orWhere('shop_name', 'like', '%'.$q.'%'))
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$q.'%')->orWhere('phone', 'like', '%'.$q.'%'));
            });
        }

        $inbound = (clone $base)->where('partner_flow', Reception::PARTNER_FLOW_INBOUND)
            ->whereNotIn('status', ['delivered'])
            ->latest('id')->limit(80)->get();

        $outbound = (clone $base)->where('partner_flow', Reception::PARTNER_FLOW_OUTBOUND)
            ->whereNotIn('status', ['delivered'])
            ->latest('id')->limit(80)->get();

        $ready = (clone $base)->where('partner_flow', Reception::PARTNER_FLOW_INBOUND)
            ->whereIn('status', ['ready', 'unrepairable'])
            ->latest('id')->limit(80)->get();

        $all = (clone $base)->latest('id')->limit(100)->get();

        $list = match ($tab) {
            'outbound' => $outbound,
            'ready' => $ready,
            'all' => $all,
            default => $inbound,
        };

        return view('partners.cartable', [
            'tab' => $tab,
            'q' => $q,
            'items' => $list,
            'stats' => [
                'inbound' => $inbound->count(),
                'outbound' => $outbound->count(),
                'ready' => $ready->count(),
            ],
            'partners' => Partner::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function createInbound(): View
    {
        return view('partners.intake', [
            'partners' => Partner::query()->where('is_active', true)->orderBy('name')->get(),
            'nextReceipt' => Reception::nextReceiptNo(),
            'nextTicket' => Reception::nextTicketNo(),
        ]);
    }

    public function storeInbound(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => ['required', 'exists:partners,id'],
            'partner_peer_receipt_no' => ['nullable', 'string', 'max:60'],
            'partner_end_customer_note' => ['nullable', 'string', 'max:190'],
            'product_name' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'reported_fault' => ['nullable', 'string', 'max:500'],
            'appearance_notes' => ['nullable', 'string', 'max:500'],
            'accessories' => ['nullable', 'string', 'max:500'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $partner = Partner::query()->findOrFail((int) $data['partner_id']);
        if (! $partner->is_active) {
            return back()->withInput()->with('error', 'این نماینده غیرفعال است.');
        }
        $customer = $partner->ensureCustomer();

        $reception = Reception::query()->create([
            'ticket_no' => Reception::nextTicketNo(),
            'receipt_no' => Reception::nextReceiptNo(),
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'partner_flow' => Reception::PARTNER_FLOW_INBOUND,
            'partner_peer_receipt_no' => $data['partner_peer_receipt_no'] ?? null,
            'partner_end_customer_note' => $data['partner_end_customer_note'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'serial_number' => $data['serial_number'] ?? null,
            'reported_fault' => $data['reported_fault'] ?? null,
            'appearance_notes' => $data['appearance_notes'] ?? null,
            'accessories' => $data['accessories'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? 0,
            'status' => 'received',
            'custody' => 'front_desk',
            'created_by' => $request->user()->id,
            'received_at' => now(),
            'referrer' => 'ارجاع نماینده: '.$partner->displayName(),
        ]);

        return redirect()
            ->route('receptions.show', $reception)
            ->with('success', 'قبض ارجاع نماینده ثبت شد. طرف حساب = نماینده (نه مشتری نهایی).');
    }

    public function referOut(Request $request, Reception $reception): RedirectResponse
    {
        $data = $request->validate([
            'partner_id' => ['required', 'exists:partners,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($reception->isPartnerInbound()) {
            return back()->with('error', 'این قبض خودش ارجاع ورودی نماینده است؛ دوباره به نماینده دیگر ارجاع ندهید مگر برگشت.');
        }

        $partner = Partner::query()->findOrFail((int) $data['partner_id']);
        $note = trim((string) ($data['note'] ?? ''));
        $tech = trim((string) $reception->technician_notes);
        if ($note !== '') {
            $tech = trim($tech."\n".'ارجاع به نماینده '.$partner->displayName().': '.$note);
        }

        $reception->update([
            'partner_flow' => Reception::PARTNER_FLOW_OUTBOUND,
            'partner_referred_to_id' => $partner->id,
            'partner_referred_at' => now(),
            'status' => $reception->status === 'delivered' ? $reception->status : 'waiting_part',
            'technician_notes' => $tech !== '' ? $tech : $reception->technician_notes,
        ]);

        return back()->with('success', 'قبض به نماینده «'.$partner->displayName().'» ارجاع شد. مشتری نهایی فقط با شما طرف است.');
    }

    public function markReturned(Request $request, Reception $reception): RedirectResponse
    {
        if ($reception->partner_flow === Reception::PARTNER_FLOW_OUTBOUND) {
            $reception->update([
                'partner_flow' => Reception::PARTNER_FLOW_RETURNED,
                'partner_returned_at' => now(),
                'status' => in_array($reception->status, ['delivered', 'cancelled'], true) ? $reception->status : 'ready',
            ]);

            return back()->with('success', 'برگشت از نماینده ثبت شد؛ آماده تحویل به مشتری نهایی.');
        }

        if ($reception->isPartnerInbound()) {
            $reception->update([
                'partner_returned_at' => now(),
                'status' => 'ready',
            ]);

            return back()->with('success', 'آماده برگشت به نماینده مبدأ علامت خورد.');
        }

        return back()->with('error', 'این قبض ارجاع نماینده نیست.');
    }
}
