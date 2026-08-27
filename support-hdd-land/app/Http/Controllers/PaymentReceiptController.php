<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Reception;
use App\Services\AccountingService;
use App\Services\ReceptionSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptController extends Controller
{
    public function index(Request $request)
    {
        $status = trim((string) $request->get('status', 'pending'));
        $q = trim((string) $request->get('q', ''));

        if ($status !== '' && ! array_key_exists($status, PaymentReceipt::STATUSES)) {
            $status = 'pending';
        }

        $receipts = PaymentReceipt::query()
            ->with(['reception.customer', 'customer', 'reviewer', 'payment'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('note', 'like', "%{$q}%")
                        ->orWhere('review_note', 'like', "%{$q}%")
                        ->orWhereHas('reception', function ($r) use ($q) {
                            $r->where('ticket_no', 'like', "%{$q}%");
                        })
                        ->orWhereHas('customer', function ($c) use ($q) {
                            $c->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending' => PaymentReceipt::query()->where('status', PaymentReceipt::STATUS_PENDING)->count(),
            'approved' => PaymentReceipt::query()->where('status', PaymentReceipt::STATUS_APPROVED)->count(),
            'rejected' => PaymentReceipt::query()->where('status', PaymentReceipt::STATUS_REJECTED)->count(),
            'total' => PaymentReceipt::query()->count(),
        ];

        return view('payment-receipts.index', [
            'receipts' => $receipts,
            'stats' => $stats,
            'status' => $status,
            'q' => $q,
            'statusLabels' => PaymentReceipt::STATUSES,
        ]);
    }

    public function show(PaymentReceipt $receipt)
    {
        $receipt->load(['reception.customer', 'customer', 'reviewer', 'payment']);

        return view('payment-receipts.show', [
            'receipt' => $receipt,
            'statusLabels' => PaymentReceipt::STATUSES,
        ]);
    }

    public function approve(Request $request, PaymentReceipt $receipt, AccountingService $accounting)
    {
        abort_unless($receipt->isPending(), 422, 'این فیش قبلاً بررسی شده است.');

        $data = $request->validate([
            'amount' => ['nullable', 'integer', 'min:1000'],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $amount = (int) ($data['amount'] ?? $receipt->amount);
        if ($amount < 1000) {
            return back()->withErrors(['amount' => 'مبلغ تأیید باید حداقل ۱۰۰۰ تومان باشد.']);
        }

        if (! $receipt->hasImage()) {
            return back()->withErrors(['receipt' => 'تصویر فیش موجود نیست؛ امکان تأیید وجود ندارد.']);
        }

        DB::transaction(function () use ($receipt, $amount, $data, $accounting) {
            $locked = PaymentReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
            if ($locked->status !== PaymentReceipt::STATUS_PENDING) {
                return;
            }

            $reception = Reception::query()->lockForUpdate()->findOrFail($locked->reception_id);
            $type = $reception->remainingAmount() <= $amount ? 'final' : 'partial';

            $payment = Payment::create([
                'reception_id' => $reception->id,
                'customer_id' => $reception->customer_id,
                'received_by' => Auth::id(),
                'type' => $type,
                'method' => 'transfer',
                'amount' => $amount,
                'note' => trim('فیش کارت‌به‌کارت #'.$locked->id.(filled($data['review_note'] ?? null) ? ' — '.$data['review_note'] : '')),
                'paid_at' => now(),
            ]);

            $reception->recalculateTotals();

            if ($reception->remainingAmount() === 0 && $reception->status === 'ready') {
                $reception->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'settlement_mode' => ReceptionSettlementService::MODE_PAID,
                    'settled_at' => now(),
                    'settlement_note' => 'تسویه با تأیید فیش کارت‌به‌کارت',
                ]);
            }

            $locked->update([
                'amount' => $amount,
                'status' => PaymentReceipt::STATUS_APPROVED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_note' => $data['review_note'] ?? null,
                'payment_id' => $payment->id,
            ]);

            $accounting->postPayment($payment->fresh(['reception', 'customer']));
        });

        return redirect()
            ->route('payment-receipts.show', $receipt)
            ->with('success', 'فیش تأیید شد و پرداخت در صندوق/حسابداری ثبت گردید.');
    }

    public function reject(Request $request, PaymentReceipt $receipt)
    {
        abort_unless($receipt->isPending(), 422, 'این فیش قبلاً بررسی شده است.');

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($receipt, $data) {
            $locked = PaymentReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
            if ($locked->status !== PaymentReceipt::STATUS_PENDING) {
                return;
            }

            if ($locked->image_path && Storage::disk('local')->exists($locked->image_path)) {
                Storage::disk('local')->delete($locked->image_path);
            }

            $locked->update([
                'status' => PaymentReceipt::STATUS_REJECTED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'review_note' => $data['review_note'] ?? 'فیش بانکی معتبر نیست.',
                'image_path' => null,
                'original_name' => null,
            ]);
        });

        return redirect()
            ->route('payment-receipts.index', ['status' => 'rejected'])
            ->with('success', 'فیش رد شد و تصویر آن حذف گردید.');
    }

    public function image(PaymentReceipt $receipt): StreamedResponse
    {
        abort_unless($receipt->hasImage(), 404);

        $mime = Storage::disk('local')->mimeType($receipt->image_path) ?: 'application/octet-stream';

        return Storage::disk('local')->response(
            $receipt->image_path,
            $receipt->original_name ?: basename($receipt->image_path),
            ['Content-Type' => $mime]
        );
    }
}
