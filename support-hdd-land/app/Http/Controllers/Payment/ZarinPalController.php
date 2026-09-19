<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\GatewayTransaction;
use App\Models\Payment;
use App\Models\Reception;
use App\Services\AccountingService;
use App\Services\ZarinPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ZarinPalController extends Controller
{
    public function start(Request $request, Reception $reception, ZarinPalService $zarinpal)
    {
        if (! $zarinpal->isConfigured()) {
            return back()->withErrors(['payment' => 'درگاه زرین‌پال هنوز پیکربندی نشده است.']);
        }

        $source = 'staff';
        $portalCustomerId = $request->session()->get('portal_customer_id');

        if ($portalCustomerId) {
            abort_unless((int) $reception->customer_id === (int) $portalCustomerId, 403);
            $source = 'portal';
        } elseif (! Auth::check()) {
            return redirect()->route('gate');
        }

        $amount = (int) $reception->remainingAmount();
        if ($amount < 1000) {
            return back()->withErrors(['payment' => 'مانده قابل پرداخت کمتر از حداقل درگاه است.']);
        }

        $trx = GatewayTransaction::create([
            'gateway' => 'zarinpal',
            'reception_id' => $reception->id,
            'customer_id' => $reception->customer_id,
            'amount' => $amount,
            'currency' => $zarinpal->currency(),
            'status' => 'pending',
            'source' => $source,
            'meta' => [
                'ticket_no' => $reception->ticket_no,
                'started_by' => Auth::id(),
            ],
        ]);

        $callback = route('payments.zarinpal.callback', ['trx' => $trx->id]);
        $desc = 'پرداخت قبض '.$reception->ticket_no.' — سرزمین هارد';
        $mobile = $reception->customer?->phone;

        $result = $zarinpal->request($amount, $callback, $desc, $mobile);
        if (! ($result['ok'] ?? false)) {
            $trx->update([
                'status' => 'failed',
                'meta' => array_merge($trx->meta ?? [], ['error' => $result['message'] ?? 'request failed', 'raw' => $result['raw'] ?? null]),
            ]);

            return back()->withErrors(['payment' => $result['message'] ?? 'خطا در اتصال به زرین‌پال']);
        }

        $trx->update([
            'authority' => $result['authority'],
            'meta' => array_merge($trx->meta ?? [], ['fee' => $result['fee'] ?? 0]),
        ]);

        return redirect()->away($zarinpal->startPayUrl($result['authority']));
    }

    public function callback(Request $request, GatewayTransaction $trx, ZarinPalService $zarinpal)
    {
        $status = strtoupper((string) $request->query('Status', $request->query('status', '')));
        $authority = (string) $request->query('Authority', $request->query('authority', ''));

        if ($trx->status === 'paid') {
            return view('payments.zarinpal-result', [
                'ok' => true,
                'transaction' => $trx->load('reception'),
                'message' => 'این تراکنش قبلاً تأیید شده است.',
            ]);
        }

        if ($status !== 'OK' || $authority === '' || ! hash_equals((string) $trx->authority, $authority)) {
            $trx->update(['status' => $status === 'OK' ? 'failed' : 'cancelled']);

            return view('payments.zarinpal-result', [
                'ok' => false,
                'transaction' => $trx->load('reception'),
                'message' => 'پرداخت لغو شد یا ناموفق بود.',
            ]);
        }

        try {
            $verify = $zarinpal->verify($authority, (int) $trx->amount);
            if (! ($verify['ok'] ?? false)) {
                $trx->update([
                    'status' => 'failed',
                    'meta' => array_merge($trx->meta ?? [], ['verify' => $verify]),
                ]);

                return view('payments.zarinpal-result', [
                    'ok' => false,
                    'transaction' => $trx->load('reception'),
                    'message' => $verify['message'] ?? 'تأیید تراکنش ناموفق بود.',
                ]);
            }

            DB::transaction(function () use ($trx, $verify) {
                $locked = GatewayTransaction::query()->lockForUpdate()->findOrFail($trx->id);
                if ($locked->status === 'paid') {
                    return;
                }

                $reception = Reception::query()->lockForUpdate()->findOrFail($locked->reception_id);
                $type = $reception->remainingAmount() <= (int) $locked->amount ? 'final' : 'partial';

                $payment = Payment::create([
                    'reception_id' => $reception->id,
                    'customer_id' => $reception->customer_id,
                    'received_by' => null,
                    'type' => $type,
                    'method' => 'zarinpal',
                    'amount' => (int) $locked->amount,
                    'note' => 'زرین‌پال — Ref: '.($verify['ref_id'] ?? '-'),
                    'paid_at' => now(),
                ]);

                $reception->recalculateTotals();

                if ($reception->remainingAmount() === 0 && $reception->status === 'ready') {
                    $reception->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
                }

                $locked->update([
                    'status' => 'paid',
                    'payment_id' => $payment->id,
                    'ref_id' => (string) ($verify['ref_id'] ?? ''),
                    'card_pan' => (string) ($verify['card_pan'] ?? ''),
                    'paid_at' => now(),
                    'meta' => array_merge($locked->meta ?? [], ['verify' => $verify]),
                ]);

                app(AccountingService::class)->postPayment($payment->fresh(['reception', 'customer']));
            });

            $trx->refresh()->load('reception');

            return view('payments.zarinpal-result', [
                'ok' => true,
                'transaction' => $trx,
                'message' => 'پرداخت با موفقیت انجام شد.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return view('payments.zarinpal-result', [
                'ok' => false,
                'transaction' => $trx->load('reception'),
                'message' => 'نتیجه پرداخت در حال بررسی است؛ لطفاً دوباره پرداخت نکنید و با پذیرش تماس بگیرید.',
                'pending' => true,
            ]);
        }
    }
}
