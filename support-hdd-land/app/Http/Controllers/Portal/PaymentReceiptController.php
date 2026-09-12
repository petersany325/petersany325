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

        merge_jalali_dates($request, ['transfer_date']);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1000', 'max:'.$remaining],
            'transfer_date' => ['nullable', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
            'receipt_image' => ['required', 'file', 'max:12288'],
        ], [
            'receipt_image.required' => 'تصویر فیش بانکی الزامی است.',
            'receipt_image.max' => 'حجم فایل حداکثر ۱۲ مگابایت باشد.',
            'amount.max' => 'مبلغ نمی‌تواند بیشتر از مانده قبض باشد.',
        ]);

        $file = $request->file('receipt_image');
        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: ''));
        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'heic', 'heif'];
        $allowedMimePrefix = ['image/', 'application/pdf'];
        $okMime = $mime === '' || collect($allowedMimePrefix)->contains(fn ($p) => str_starts_with($mime, $p)) || in_array($mime, [
            'image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence', 'application/octet-stream',
        ], true);
        if (($ext !== '' && ! in_array($ext, $allowedExt, true)) || ! $okMime) {
            return back()->withInput()->withErrors([
                'receipt_image' => 'فرمت مجاز: JPG، PNG، WEBP، HEIC یا PDF. از دوربین گوشی یا گالری عکس بگیرید.',
            ]);
        }

        // Convert HEIC/HEIF to JPEG when Imagick is available (common iPhone gallery picks).
        if (in_array($ext, ['heic', 'heif'], true) || str_contains($mime, 'heic') || str_contains($mime, 'heif')) {
            if (class_exists(\Imagick::class)) {
                try {
                    $imagick = new \Imagick($file->getRealPath());
                    $imagick->setImageFormat('jpeg');
                    $imagick->setImageCompressionQuality(85);
                    $tmp = tempnam(sys_get_temp_dir(), 'heic').'.jpg';
                    $imagick->writeImage($tmp);
                    $imagick->clear();
                    $imagick->destroy();
                    $storedName = 'payment-receipts/'.$reception->id.'/'.uniqid('rcpt_', true).'.jpg';
                    Storage::disk('local')->put($storedName, file_get_contents($tmp));
                    @unlink($tmp);
                    $path = $storedName;
                    $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.jpg';
                } catch (\Throwable $e) {
                    return back()->withInput()->withErrors([
                        'receipt_image' => 'تبدیل عکس HEIC ناموفق بود. لطفاً از دوربین با فرمت JPG بگیرید یا در گالری به JPG ذخیره کنید.',
                    ]);
                }
            } else {
                return back()->withInput()->withErrors([
                    'receipt_image' => 'این گوشی عکس HEIC فرستاد. لطفاً از دکمه «عکس با دوربین» استفاده کنید یا فایل را به JPG تبدیل کنید.',
                ]);
            }
        } else {
            $path = $file->store('payment-receipts/'.$reception->id, 'local');
            $originalName = $file->getClientOriginalName();
        }

        PaymentReceipt::create([
            'reception_id' => $reception->id,
            'customer_id' => $customer->id,
            'amount' => (int) $data['amount'],
            'transfer_date' => $data['transfer_date'] ?? now()->toDateString(),
            'note' => $data['note'] ?? null,
            'image_path' => $path,
            'original_name' => $originalName,
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
