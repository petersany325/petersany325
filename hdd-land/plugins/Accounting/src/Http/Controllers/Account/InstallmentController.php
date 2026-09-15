<?php

namespace Plugins\Accounting\src\Http\Controllers\Account;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Plugins\Accounting\Plugin;
use Plugins\Accounting\src\Support\AccEngine;

class InstallmentController extends Controller
{
    public function __construct()
    {
        Plugin::ensureSchema();
    }

    public function index()
    {
        $uid = Auth::id();
        $items = Schema::hasTable('acc_installment_requests')
            ? DB::table('acc_installment_requests')->where('user_id', $uid)->orderByDesc('id')->limit(50)->get()
            : collect();

        return view('accounting::account.installments', [
            'items' => $items,
            'statuses' => AccEngine::INSTALLMENT_STATUSES,
        ]);
    }

    public function create(Request $request)
    {
        $products = collect();
        if (Schema::hasTable('products')) {
            $q = trim((string) $request->get('q', ''));
            $query = DB::table('products')->orderByDesc('id')->limit(40);
            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%");
                    if (Schema::hasColumn('products', 'sku')) {
                        $w->orWhere('sku', 'like', "%{$q}%");
                    }
                });
            }
            $cols = ['id', 'name'];
            if (Schema::hasColumn('products', 'sku')) {
                $cols[] = 'sku';
            }
            if (Schema::hasColumn('products', 'price')) {
                $cols[] = 'price';
            } elseif (Schema::hasColumn('products', 'sale_price')) {
                $cols[] = 'sale_price as price';
            }
            $products = $query->get($cols);
        }

        $selected = null;
        $productId = (int) $request->get('product_id', 0);
        if ($productId > 0 && Schema::hasTable('products')) {
            $selected = DB::table('products')->where('id', $productId)->first();
        }

        return view('accounting::account.installment-form', [
            'products' => $products,
            'selected' => $selected,
            'q' => (string) $request->get('q', ''),
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('acc_installment_requests'), 404);
        $user = Auth::user();
        $uid = (int) Auth::id();

        $title = trim((string) $request->input('product_title', ''));
        $productId = $request->filled('product_id') ? (int) $request->input('product_id') : null;
        $price = (int) preg_replace('/\D+/', '', (string) $request->input('product_price', 0));
        $down = (int) preg_replace('/\D+/', '', (string) $request->input('down_payment', 0));
        $months = max(1, min(24, (int) $request->input('months', 3)));
        $note = trim((string) $request->input('customer_note', ''));

        if ($productId && Schema::hasTable('products')) {
            $p = DB::table('products')->where('id', $productId)->first();
            if ($p) {
                $title = $title !== '' ? $title : (string) ($p->name ?? '');
                if ($price <= 0) {
                    $price = (int) ($p->price ?? $p->sale_price ?? 0);
                }
            }
        }

        if ($title === '' || $price <= 0) {
            return back()->withInput()->with('error', 'کالا و قیمت را مشخص کنید.');
        }

        $remain = max(0, $price - $down);
        $monthly = (int) ceil($remain / $months);
        $number = AccEngine::nextInstallmentNumber();
        $name = trim((string) ($user->name ?? $user->full_name ?? 'مشتری'));
        $mobile = trim((string) ($user->mobile ?? $request->input('customer_mobile', '')));

        $body = "درخواست اقساط {$number}\n"
            . "مشتری: {$name}\n"
            . "کالا: {$title}\n"
            . 'قیمت: ' . number_format($price) . " تومان\n"
            . 'پیش‌پرداخت: ' . number_format($down) . " تومان\n"
            . "تعداد اقساط: {$months}\n"
            . 'قسط ماهانه تقریبی: ' . number_format($monthly) . " تومان\n"
            . ($note !== '' ? "توضیح مشتری: {$note}\n" : '');

        $ticketId = AccEngine::createInstallmentTicket($uid, "درخواست اقساط — {$title}", $body, ['number' => $number]);

        $id = (int) DB::table('acc_installment_requests')->insertGetId([
            'number' => $number,
            'user_id' => $uid,
            'customer_name' => $name,
            'customer_mobile' => $mobile !== '' ? $mobile : null,
            'customer_national_id' => trim((string) $request->input('customer_national_id', '')) ?: null,
            'product_title' => $title,
            'product_id' => $productId,
            'product_price' => $price,
            'down_payment' => $down,
            'months' => $months,
            'monthly_amount' => $monthly,
            'total_amount' => $down + ($monthly * $months),
            'status' => 'pending',
            'ticket_id' => $ticketId,
            'approved_by' => null,
            'customer_note' => $note !== '' ? $note : null,
            'admin_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $msg = 'درخواست اقساط ثبت شد.';
        if ($ticketId) {
            $msg .= ' تیکت پشتیبانی نیز باز شد.';
        } else {
            $msg .= ' برای پیگیری می‌توانید از بخش تیکت پشتیبانی هم پیام بگذارید.';
        }

        return redirect()->route('account.installments.show', $id)->with('success', $msg);
    }

    public function show(int $id)
    {
        $uid = Auth::id();
        $row = DB::table('acc_installment_requests')->where('id', $id)->where('user_id', $uid)->first();
        abort_unless($row, 404);
        $schedules = Schema::hasTable('acc_installment_schedules')
            ? DB::table('acc_installment_schedules')->where('request_id', $id)->orderBy('installment_no')->get()
            : collect();

        return view('accounting::account.installment-show', [
            'row' => $row,
            'schedules' => $schedules,
            'statuses' => AccEngine::INSTALLMENT_STATUSES,
        ]);
    }
}
