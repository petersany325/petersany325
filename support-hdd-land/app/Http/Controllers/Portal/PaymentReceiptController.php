<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PaymentReceipt;
use App\Models\Reception;
use App\Support\BankTransferSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptController extends Controller
{
    public function store(Request $request, Reception $reception)
    {
        $customer = $this->customer($request);
        abort_unless((int) $reception->customer_id === (int) $customer->id, 404);
        abort_if($reception->status === 'cancelled', 422, 'این قبض لغو شده است.');

        if (! BankTransferSettings::isEnabled()) {
            return back()->withErrors(['receipt' => 'واریز کارت‌به‌کارت فعلاً فعال نیست.']);
        }

        $remaining = $reception->remainingAmount();
        if ($remaining < 1000) {
            return back()->withErrors(['receipt' => 'مانده قابل پرداخت برای ثبت فیش کافی نیست.']);
        }

        $pendingExists = PaymentReceipt::query()
            ->where('reception_id', $reception->id)
            ->where('status', PaymentReceipt::STATUS_PENDING)
            ->exists();
        if ($pendingExists) {
            return back()->withErrors(['receipt' => 'یک فیش در انتظار تأیید دارید؛ تا بررسی حسابداری فیش جدید ارسال نکنید.']);
        }

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1000', 'max:'.$remaining],
            'transfer_date' => ['nullable', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
            'receipt_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ], [
            'receipt_image.required' => 'تصویر فیش بانکی الزامی است.',
            'receipt_image.mimes' => 'فرمت مجاز: JPG، PNG، WEBP یا PDF.',
            'amount.max' => 'مبلغ نمی‌تواند بیشتر از مانده قبض باشد.',
        ]);

        $file = $request->file('receipt_image');
        $path = $file->store('payment-receipts/'.$reception->id, 'local');

        PaymentReceipt::create([
            'reception_id' => $reception->id,
            'customer_id' => $customer->id,
            'amount' => (int) $data['amount'],
            'transfer_date' => $data['transfer_date'] ?? now()->toDateString(),
            'note' => $data['note'] ?? null,
            'image_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'status' => PaymentReceipt::STATUS_PENDING,
        ]);

        return redirect()
            ->route('portal.show', $reception)
            ->with('success', 'فیش ارسال شد. تا تأیید حسابدار/مدیر، واریز قطعی محسوب نمی‌شود.');
    }

    public function image(Request $request, PaymentReceipt $receipt): StreamedResponse
    {
        $customer = $this->customer($request);
        abort_unless((int) $receipt->customer_id === (int) $customer->id, 404);
        abort_unless($receipt->hasImage(), 404);

        $mime = Storage::disk('local')->mimeType($receipt->image_path) ?: 'application/octet-stream';

        return Storage::disk('local')->response(
            $receipt->image_path,
            $receipt->original_name ?: basename($receipt->image_path),
            ['Content-Type' => $mime]
        );
    }

    private function customer(Request $request): Customer
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');

        return $customer;
    }
}
