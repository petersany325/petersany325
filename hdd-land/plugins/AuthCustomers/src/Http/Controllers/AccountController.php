<?php

namespace Plugins\AuthCustomers\src\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Plugins\AuthCustomers\Plugin;
use Plugins\AuthCustomers\src\Services\OtpService;
use Plugins\AuthCustomers\src\Services\SmsGateway;
use Plugins\AuthCustomers\src\Services\TotpAuthenticator;
use Plugins\CartCheckout\src\Models\Order;
use Plugins\Catalog\src\Models\Product;

class AccountController extends Controller
{
    public function index(): View
    {
        Plugin::ensureSchema();
        $user = Auth::user();
        $ordersCount = Order::query()->where('user_id', $user->id)->count();
        $preordersCount = DB::table('user_preorders')->where('user_id', $user->id)->count();
        $recentOrders = Order::query()->where('user_id', $user->id)->latest()->limit(5)->get();
        $walletBalance = 0;
        try {
            if (class_exists(\Plugins\AuthCustomers\src\Support\WalletSupport::class)
                && \Plugins\AuthCustomers\src\Support\WalletSupport::enabled()) {
                $walletBalance = \Plugins\AuthCustomers\src\Services\WalletService::balance((int) $user->id);
            }
        } catch (\Throwable) {
            //
        }

        return view('auth-customers::account.dashboard', compact('user', 'ordersCount', 'preordersCount', 'recentOrders', 'walletBalance'));
    }

    public function orders(): View
    {
        $orders = Order::query()->where('user_id', Auth::id())->latest()->paginate(12);

        return view('auth-customers::account.orders', compact('orders'));
    }

    public function orderShow(int $order): View|RedirectResponse
    {
        $item = Order::query()->with('items')->where('user_id', Auth::id())->where('id', $order)->first();
        if (! $item) {
            return redirect()->route('account.orders')->with('error', 'سفارش یافت نشد.');
        }

        return view('auth-customers::account.order-show', ['order' => $item]);
    }

    /**
     * Customer's owned serials from orders + desk sales matched by phone.
     */
    public function serials(): View
    {
        Plugin::ensureSchema();
        $user = Auth::user();
        $rows = collect();

        try {
            if (Schema::hasTable('order_items') && Schema::hasTable('orders')) {
                $orderIds = Order::query()->where('user_id', $user->id)->pluck('id');
                if ($orderIds->isNotEmpty()) {
                    $q = DB::table('order_items')
                        ->join('orders', 'orders.id', '=', 'order_items.order_id')
                        ->whereIn('order_items.order_id', $orderIds)
                        ->whereNotNull('order_items.serial')
                        ->where('order_items.serial', '!=', '')
                        ->orderByDesc('order_items.id')
                        ->select([
                            'order_items.serial',
                            'order_items.product_name',
                            'order_items.product_id',
                            'order_items.product_serial_id',
                            'order_items.order_id',
                            'orders.order_number',
                            'orders.created_at as purchased_at',
                        ]);
                    foreach ($q->get() as $row) {
                        $rows->push($this->enrichSerialRow($row, 'order'));
                    }
                }
            }

            if (Schema::hasTable('product_serials')) {
                $phone = preg_replace('/\D+/', '', (string) ($user->phone ?? $user->mobile ?? '')) ?: null;
                $serialQ = DB::table('product_serials')->where('status', 'sold');
                $serialQ->where(function ($w) use ($user, $phone) {
                    if (Schema::hasColumn('product_serials', 'sold_by')) {
                        $w->orWhere('sold_by', $user->id);
                    }
                    if ($phone && Schema::hasColumn('product_serials', 'buyer_phone')) {
                        $w->orWhere('buyer_phone', 'like', '%'.$phone.'%');
                    }
                    $orderIds = Order::query()->where('user_id', $user->id)->pluck('id');
                    if ($orderIds->isNotEmpty() && Schema::hasColumn('product_serials', 'order_id')) {
                        $w->orWhereIn('order_id', $orderIds);
                    }
                });
                foreach ($serialQ->orderByDesc('id')->limit(200)->get() as $row) {
                    if ($rows->contains(fn ($r) => ($r['serial'] ?? '') === ($row->serial ?? ''))) {
                        continue;
                    }
                    $rows->push($this->enrichSerialRow((object) [
                        'serial' => $row->serial,
                        'product_name' => $row->product_name,
                        'product_id' => $row->product_id,
                        'product_serial_id' => $row->id,
                        'order_id' => $row->order_id ?? null,
                        'order_number' => null,
                        'purchased_at' => $row->sold_at ?? $row->updated_at ?? $row->created_at,
                        'warranty_company' => $row->warranty_company ?? null,
                        'company_warranty_months' => $row->company_warranty_months ?? null,
                        'has_company_warranty' => $row->has_company_warranty ?? null,
                    ], 'desk'));
                }
            }
        } catch (\Throwable) {
            //
        }

        return view('auth-customers::account.serials', [
            'user' => $user,
            'serials' => $rows->values(),
        ]);
    }

    /** @param  object  $row */
    protected function enrichSerialRow(object $row, string $source): array
    {
        $warrantyCompany = $row->warranty_company ?? null;
        $warrantyMonths = $row->company_warranty_months ?? null;
        $hasWarranty = $row->has_company_warranty ?? null;
        try {
            if ((! $warrantyCompany || $hasWarranty === null) && Schema::hasTable('product_serials')) {
                $sn = DB::table('product_serials')
                    ->when(! empty($row->product_serial_id), fn ($q) => $q->where('id', $row->product_serial_id))
                    ->when(empty($row->product_serial_id) && ! empty($row->serial), fn ($q) => $q->where('serial', $row->serial))
                    ->first();
                if ($sn) {
                    $warrantyCompany = $warrantyCompany ?: ($sn->warranty_company ?? null);
                    $warrantyMonths = $warrantyMonths ?? ($sn->company_warranty_months ?? null);
                    $hasWarranty = $hasWarranty ?? ($sn->has_company_warranty ?? null);
                }
            }
        } catch (\Throwable) {
            //
        }

        return [
            'serial' => (string) ($row->serial ?? ''),
            'product_name' => (string) ($row->product_name ?? 'محصول'),
            'product_id' => $row->product_id ?? null,
            'order_id' => $row->order_id ?? null,
            'order_number' => $row->order_number ?? null,
            'purchased_at' => $row->purchased_at ?? null,
            'warranty_company' => $warrantyCompany,
            'company_warranty_months' => (int) ($warrantyMonths ?? 0),
            'has_company_warranty' => (bool) $hasWarranty,
            'source' => $source,
            'lookup_url' => url('/serial-check?serial='.rawurlencode((string) ($row->serial ?? ''))),
        ];
    }

    public function invoices(): View
    {
        $orders = Order::query()->where('user_id', Auth::id())->latest()->paginate(15);

        return view('auth-customers::account.invoices', compact('orders'));
    }

    public function invoiceShow(Request $request, int $order): View|RedirectResponse
    {
        $item = Order::query()->with('items')->where('user_id', Auth::id())->where('id', $order)->first();
        if (! $item) {
            return redirect()->route('account.invoices')->with('error', 'فاکتور یافت نشد.');
        }

        $type = $request->get('type') === 'proforma' ? 'proforma' : 'invoice';
        $carrier = null;
        $trackingUrl = null;
        try {
            if (class_exists(\Plugins\AdminCore\src\Support\ShippingSupport::class)) {
                \Plugins\AdminCore\src\Support\ShippingSupport::ensureSchema();
                if (\Illuminate\Support\Facades\Schema::hasTable('shipping_carriers')) {
                    if (! empty($item->shipping_carrier_id)) {
                        $carrier = DB::table('shipping_carriers')->where('id', $item->shipping_carrier_id)->first();
                    } elseif (! empty($item->shipping_method)) {
                        $carrier = DB::table('shipping_carriers')->where('slug', $item->shipping_method)->first();
                    }
                    $trackingUrl = \Plugins\AdminCore\src\Support\ShippingSupport::trackingLink($carrier, $item->tracking_code ?? null);
                }
            }
        } catch (\Throwable) {
            //
        }

        $s = class_exists(\Plugins\AdminCore\src\Support\ShippingSupport::class)
            ? \Plugins\AdminCore\src\Support\ShippingSupport::invoiceSettings()
            : [];

        return view('admin-core::invoice.print', [
            's' => $s,
            'order' => $item,
            'type' => $type,
            'isPreview' => false,
            'isCustomer' => true,
            'carrier' => $carrier,
            'trackingUrl' => $trackingUrl,
        ]);
    }

    public function profile(): View
    {
        Plugin::ensureSchema();

        return view('auth-customers::account.profile', ['user' => Auth::user(), 'settings' => Plugin::settings()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        Plugin::ensureSchema();
        $user = Auth::user();
        $s = Plugin::settings();
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'province' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'bank_name' => ['nullable', 'string', 'max:80'],
            'bank_card' => ['nullable', 'string', 'max:24'],
            'bank_iban' => ['nullable', 'string', 'max:34'],
            'bank_account_holder' => ['nullable', 'string', 'max:160'],
        ];
        if (! empty($s['bank_refund_enabled']) && ! empty($s['show_bank_fields'])) {
            $reqAll = ! empty($s['require_bank_fields']);
            if ($reqAll || ! empty($s['require_bank_name'])) {
                $rules['bank_name'] = ['required', 'string', 'max:80'];
            }
            if ($reqAll || ! empty($s['require_bank_card'])) {
                $rules['bank_card'] = ['required', 'string', 'max:24'];
            }
            if ($reqAll || ! empty($s['require_bank_iban'])) {
                $rules['bank_iban'] = ['required', 'string', 'max:34'];
            }
            if ($reqAll || ! empty($s['require_bank_account_holder'])) {
                $rules['bank_account_holder'] = ['required', 'string', 'max:160'];
            }
        }
        $data = $request->validate($rules, [
            'bank_name.required' => 'نام بانک را انتخاب کنید.',
            'bank_card.required' => 'شماره کارت را وارد کنید.',
            'bank_iban.required' => 'شماره شبا را وارد کنید.',
            'bank_account_holder.required' => 'نام صاحب حساب را وارد کنید.',
        ]);
        if (! empty($data['province']) && ! \Plugins\AuthCustomers\src\Support\IranLocations::isValidProvince($data['province'])) {
            return back()->withErrors(['province' => 'استان نامعتبر است.'])->withInput();
        }
        if (! empty($data['province']) && ! empty($data['city'])
            && ! \Plugins\AuthCustomers\src\Support\IranLocations::isValidCity($data['province'], $data['city'])) {
            return back()->withErrors(['city' => 'شهر با استان مطابقت ندارد.'])->withInput();
        }
        if (! empty($data['phone'])) {
            $phone = SmsGateway::normalizePhone($data['phone']);
            if ($phone !== $user->phone) {
                $data['phone'] = $phone;
                $data['phone_verified_at'] = null;
            }
        }
        if (! empty($data['bank_card'])) {
            $data['bank_card'] = preg_replace('/\D+/', '', (string) $data['bank_card']);
        }
        if (! empty($data['bank_iban'])) {
            $iban = strtoupper(preg_replace('/\s+/', '', (string) $data['bank_iban']));
            if (! str_starts_with($iban, 'IR')) {
                $iban = 'IR'.$iban;
            }
            $data['bank_iban'] = $iban;
        }
        $user->fill($data)->save();

        return back()->with('success', 'پروفایل به‌روزرسانی شد.');
    }

    public function passwordForm(): View
    {
        return view('auth-customers::account.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $user = Auth::user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->with('error', 'رمز فعلی اشتباه است.');
        }
        $user->password = $data['password'];
        $user->save();

        return back()->with('success', 'رمز عبور تغییر کرد.');
    }

    public function security(): View
    {
        Plugin::ensureSchema();
        $user = Auth::user();
        $s = Plugin::settings();
        $qr = null;
        $otpauth = null;
        if ($user->two_factor_secret && $user->two_factor_method === 'authenticator') {
            $otpauth = TotpAuthenticator::provisioningUri(
                $user->two_factor_secret,
                $user->email,
                $s['shop_name_2fa'] ?? 'HDD Land'
            );
            $qr = TotpAuthenticator::qrUrl($otpauth);
        }

        return view('auth-customers::account.security', compact('user', 's', 'qr', 'otpauth'));
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $s = Plugin::settings();
        $data = $request->validate([
            'method' => ['required', 'in:sms,email,authenticator,none'],
        ]);
        $user = Auth::user();
        $method = $data['method'];

        if ($method === 'none') {
            $user->update([
                'two_factor_enabled' => false,
                'two_factor_method' => 'none',
                'two_factor_secret' => null,
            ]);

            return back()->with('success', 'ورود دو مرحله‌ای خاموش شد.');
        }

        if ($method === 'sms' && empty($s['enable_sms_otp'])) {
            return back()->with('error', 'ورود با SMS در تنظیمات غیرفعال است.');
        }
        if ($method === 'email' && empty($s['enable_email_otp'])) {
            return back()->with('error', 'ورود با ایمیل در تنظیمات غیرفعال است.');
        }
        if ($method === 'authenticator' && empty($s['enable_authenticator'])) {
            return back()->with('error', 'Authenticator در تنظیمات غیرفعال است.');
        }
        if ($method === 'sms' && ! $user->phone) {
            return back()->with('error', 'ابتدا شماره موبایل را در پروفایل ذخیره کنید.');
        }

        $secret = $method === 'authenticator' ? TotpAuthenticator::generateSecret() : null;
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_method' => $method,
            'two_factor_secret' => $secret,
        ]);

        $msg = 'ورود دو مرحله‌ای فعال شد.';
        if ($method === 'authenticator') {
            $msg .= ' QR کد را با اپ Authenticator اسکن کنید.';
        }

        return back()->with('success', $msg);
    }

    public function showVerifyPhone(): View|RedirectResponse
    {
        $user = Auth::user();
        if ($user->phone_verified_at) {
            return redirect()->route('account.security')->with('success', 'موبایل قبلاً تأیید شده است.');
        }

        return view('auth-customers::account.verify-phone', compact('user'));
    }

    public function sendPhoneOtp(): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->phone) {
            return back()->with('error', 'شماره موبایل ثبت نشده است.');
        }
        $issued = OtpService::issue($user->id, 'sms', SmsGateway::normalizePhone($user->phone), 'verify_phone');
        if (! $issued['ok']) {
            return back()->with('error', $issued['message']);
        }

        return back()->with('success', $issued['message']);
    }

    public function confirmPhoneOtp(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = Auth::user();
        $ok = OtpService::verify($user->id, SmsGateway::normalizePhone((string) $user->phone), 'verify_phone', $request->input('code'));
        if (! $ok) {
            return back()->with('error', 'کد نادرست است.');
        }
        $user->phone_verified_at = now();
        $user->save();

        return redirect()->route('account.index')->with('success', 'شماره موبایل تأیید شد.');
    }

    public function preorders(): View
    {
        Plugin::ensureSchema();
        $items = DB::table('user_preorders')->where('user_id', Auth::id())->orderByDesc('id')->paginate(15);
        $products = class_exists(Product::class)
            ? Product::query()->where('is_active', true)->orderBy('name')->limit(200)->get(['id', 'name', 'slug'])
            : collect();

        return view('auth-customers::account.preorders', compact('items', 'products'));
    }

    public function storePreorder(Request $request): RedirectResponse
    {
        Plugin::ensureSchema();
        $data = $request->validate([
            'product_id' => ['nullable', 'integer'],
            'product_name' => ['nullable', 'string', 'max:200'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $name = $data['product_name'] ?? null;
        $url = null;
        if (! empty($data['product_id']) && class_exists(Product::class)) {
            $p = Product::query()->find($data['product_id']);
            if ($p) {
                $name = $p->name;
                $url = url('/products/'.$p->slug);
            }
        }
        if (! $name) {
            return back()->with('error', 'محصول را انتخاب یا نام آن را وارد کنید.');
        }
        DB::table('user_preorders')->insert([
            'user_id' => Auth::id(),
            'product_id' => $data['product_id'] ?? null,
            'product_name' => $name,
            'product_url' => $url,
            'qty' => (int) ($data['qty'] ?? 1),
            'note' => $data['note'] ?? null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'درخواست پیش‌خرید ثبت شد.');
    }

    public function shop(Request $request): View
    {
        if (! class_exists(Product::class)) {
            $products = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 24);

            return view('auth-customers::account.shop', [
                'products' => $products,
                'categories' => collect(),
                'brands' => collect(),
                'filters' => [],
                'partTypes' => [],
                'conditions' => [],
                'totalFound' => 0,
            ]);
        }

        $query = Product::query()->published()->with('category');

        if ($request->filled('q')) {
            $q = trim((string) $request->string('q'));
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('short_description', 'like', "%{$q}%");
                if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'brand')) {
                    $builder->orWhere('brand', 'like', "%{$q}%");
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'display_serial')) {
                    $builder->orWhere('display_serial', 'like', "%{$q}%");
                }
            });
        }

        if ($request->filled('category') && class_exists(\Plugins\Catalog\src\Models\Category::class)) {
            $cat = \Plugins\Catalog\src\Models\Category::query()->where('slug', $request->string('category'))->first();
            if ($cat) {
                $childIds = method_exists($cat, 'children') ? $cat->children()->pluck('id') : collect();
                $ids = collect([$cat->id])->merge($childIds);
                $query->whereIn('category_id', $ids);
            }
        }

        if ($request->filled('brand') && \Illuminate\Support\Facades\Schema::hasColumn('products', 'brand')) {
            $query->where('brand', $request->string('brand'));
        }

        if ($request->filled('part_type') && \Illuminate\Support\Facades\Schema::hasColumn('products', 'part_type')) {
            $query->where('part_type', $request->string('part_type'));
        }

        if ($request->filled('condition') && \Illuminate\Support\Facades\Schema::hasColumn('products', 'condition')) {
            $query->where('condition', $request->string('condition'));
        }

        if ($request->filled('warranty') && \Illuminate\Support\Facades\Schema::hasColumn('products', 'warranty_type')) {
            if ($request->string('warranty')->toString() === 'yes') {
                $query->whereNotNull('warranty_type')->where('warranty_type', '!=', '')->where('warranty_type', '!=', 'none');
            } elseif ($request->string('warranty')->toString() === 'no') {
                $query->where(function ($b) {
                    $b->whereNull('warranty_type')->orWhere('warranty_type', '')->orWhere('warranty_type', 'none');
                });
            }
        }

        if ($request->filled('stock')) {
            if ($request->string('stock')->toString() === 'in') {
                $query->where(function ($b) {
                    $b->where('stock_status', 'instock')
                        ->orWhere(function ($x) {
                            $x->whereNull('stock_status')->where('stock', '>', 0);
                        });
                });
            } elseif ($request->string('stock')->toString() === 'out') {
                $query->where(function ($b) {
                    $b->where('stock_status', 'outofstock')->orWhere('stock', '<=', 0);
                });
            }
        }

        if ($request->filled('serial') && \Illuminate\Support\Facades\Schema::hasColumn('products', 'requires_serial')) {
            $query->where('requires_serial', $request->string('serial')->toString() === '1' ? 1 : 0);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (int) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int) $request->input('max_price'));
        }

        if ($request->boolean('sale_only')) {
            $query->whereNotNull('compare_price')->whereColumn('compare_price', '>', 'price');
        }

        $sort = (string) $request->input('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->orderBy('price')->orderByDesc('id'),
            'price_desc' => $query->orderByDesc('price')->orderByDesc('id'),
            'name' => $query->orderBy('name'),
            'oldest' => $query->orderBy('id'),
            default => $query->latest(),
        };

        $perPage = in_array((int) $request->input('per_page'), [12, 24, 36], true) ? (int) $request->input('per_page') : 24;
        $products = $query->paginate($perPage)->withQueryString();

        $categories = class_exists(\Plugins\Catalog\src\Models\Category::class)
            ? \Plugins\Catalog\src\Models\Category::query()->where('is_active', true)->whereNull('parent_id')->with('activeChildren')->orderBy('sort_order')->get()
            : collect();

        $brands = collect();
        if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'brand')) {
            $brands = Product::query()->published()->whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand');
        }

        $priceRange = Product::query()->published()->selectRaw('MIN(price) as min_p, MAX(price) as max_p')->first();

        return view('auth-customers::account.shop', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'filters' => $request->only(['q', 'category', 'brand', 'part_type', 'condition', 'warranty', 'stock', 'serial', 'min_price', 'max_price', 'sale_only', 'sort', 'per_page']),
            'partTypes' => Product::PART_TYPES,
            'conditions' => Product::CONDITIONS,
            'totalFound' => $products->total(),
            'priceMin' => (int) ($priceRange->min_p ?? 0),
            'priceMax' => (int) ($priceRange->max_p ?? 0),
        ]);
    }
}
