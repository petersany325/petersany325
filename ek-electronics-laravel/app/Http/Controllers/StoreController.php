<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Services\WhatsApp;
use App\Support\HomepageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function home(): View
    {
        $settings = $this->settings();

        return view('store.home', [
            'featured' => Product::query()->with('category')->where('is_active', true)->where('is_featured', true)->take((int) ($settings['bestsellers_count'] ?? 8))->get(),
            'settings' => $settings,
            'menus' => $this->menus(),
        ]);
    }

    public function shop(Request $request): View
    {
        abort_unless(Setting::bool('shop_enabled', true), 404);

        $q = Product::query()->with('category')->where('is_active', true);
        if ($request->filled('category')) {
            $q->whereHas('category', fn ($c) => $c->where('slug', $request->string('category')));
        }
        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('sku', 'like', $term));
        }

        return view('store.shop', [
            'products' => $q->orderBy('name')->paginate(24)->withQueryString(),
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'settings' => $this->settings(),
            'menus' => $this->menus(),
        ]);
    }

    public function services(): View
    {
        return view('store.services', ['settings' => $this->settings(), 'menus' => $this->menus()]);
    }

    public function about(): View
    {
        return view('store.about', ['settings' => $this->settings(), 'menus' => $this->menus()]);
    }

    public function contact(): View
    {
        return view('store.contact', ['settings' => $this->settings(), 'menus' => $this->menus()]);
    }

    public function page(string $slug): View
    {
        $page = Page::query()->with(['blocks' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if ($page) {
            return view('store.cms-page', [
                'page' => $page,
                'settings' => $this->settings(),
                'menus' => $this->menus(),
            ]);
        }

        abort_unless(in_array($slug, ['shipping', 'warranty', 'terms'], true), 404);

        return view('store.page', ['slug' => $slug, 'settings' => $this->settings(), 'menus' => $this->menus()]);
    }

    public function cart(): View
    {
        return view('store.cart', [
            'items' => $this->cartItems(),
            'settings' => $this->settings(),
            'menus' => $this->menus(),
        ]);
    }

    public function checkout(): View
    {
        abort_unless(Setting::bool('checkout_enabled', true), 404);

        return view('store.checkout', [
            'items' => $this->cartItems(),
            'settings' => $this->settings(),
            'menus' => $this->menus(),
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        abort_unless(Setting::bool('shop_enabled', true), 404);
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);
        $cart = session()->get('cart', []);
        $id = (string) $data['product_id'];
        $cart[$id] = ($cart[$id] ?? 0) + ($data['qty'] ?? 1);
        session()->put('cart', $cart);

        return back()->with('success', 'Added to cart.');
    }

    public function updateCart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'qty' => ['array'],
            'qty.*' => ['integer', 'min:0', 'max:99'],
        ]);
        $cart = [];
        foreach ($data['qty'] ?? [] as $id => $qty) {
            if ((int) $qty > 0) {
                $cart[(string) $id] = (int) $qty;
            }
        }
        session()->put('cart', $cart);

        return back()->with('success', 'Cart updated.');
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        abort_unless(Setting::bool('checkout_enabled', true), 404);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
        ]);

        $items = $this->cartItems();
        abort_if($items->isEmpty(), 422, 'Cart is empty.');

        $subtotal = $items->sum('line_total');
        $order = Order::query()->create([
            'number' => 'SO-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'company' => $data['company'] ?? null,
            'city' => $data['city'] ?? 'South Africa',
            'status' => 'new',
            'subtotal' => $subtotal,
            'total' => $subtotal,
        ]);

        foreach ($items as $row) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $row['product']->id,
                'product_name' => $row['product']->name,
                'sku' => $row['product']->sku,
                'qty' => $row['qty'],
                'unit_price' => $row['product']->price,
                'line_total' => $row['line_total'],
            ]);
        }

        $lines = $items->map(fn ($r) => '• '.$r['product']->name.' x'.$r['qty'].' = R '.number_format($r['line_total'], 2))->implode("\n");
        $template = Setting::getValue('whatsapp_order_template', "New order {number} from {name}\nPhone: {phone}\nShip to: {city}\n\n{lines}\n\nTotal: R {total}\nPlease confirm stock and courier.");
        $msg = strtr($template, [
            '{number}' => $order->number,
            '{name}' => $order->customer_name,
            '{phone}' => $order->customer_phone,
            '{city}' => (string) $order->city,
            '{lines}' => $lines,
            '{total}' => number_format((float) $order->total, 2),
        ]);

        $order->update(['whatsapp_payload' => $msg, 'whatsapp_sent_at' => now()]);
        if (Setting::bool('whatsapp_enabled', true) && Setting::bool('whatsapp_notify_orders', true)) {
            WhatsApp::logOutbound($msg, WhatsApp::number(), 'order', Order::class, $order->id);
        }

        Invoice::query()->create([
            'number' => 'INV-'.$order->number,
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'amount' => $order->total,
            'vat_amount' => round(((float) $order->total) * ((float) Setting::getValue('vat_rate', '15')) / (100 + (float) Setting::getValue('vat_rate', '15')), 2),
            'status' => 'open',
            'due_date' => now()->addDays(7),
        ]);

        session()->forget('cart');

        if (Setting::bool('whatsapp_enabled', true) && Setting::bool('whatsapp_notify_orders', true)) {
            return redirect()->away(WhatsApp::link($msg));
        }

        return redirect()->route('home')->with('success', 'Order '.$order->number.' placed. We will contact you shortly.');
    }

    public function contactWhatsApp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $msg = "Enquiry from {$data['name']}\n{$data['email']}\n\n{$data['message']}";
        if (Setting::bool('whatsapp_enabled', true) && Setting::bool('whatsapp_notify_contact', true)) {
            WhatsApp::logOutbound($msg, WhatsApp::number(), 'contact');

            return redirect()->away(WhatsApp::link($msg));
        }

        return back()->with('success', 'Message received. We will reply soon.');
    }

    private function cartItems()
    {
        $cart = session()->get('cart', []);
        $products = Product::query()->whereIn('id', array_keys($cart))->get()->keyBy('id');

        return collect($cart)->map(function ($qty, $id) use ($products) {
            $product = $products->get((int) $id);
            if (! $product) {
                return null;
            }

            return [
                'product' => $product,
                'qty' => (int) $qty,
                'line_total' => (float) $product->price * (int) $qty,
            ];
        })->filter()->values();
    }

    /** @return array<string, mixed> */
    private function settings(): array
    {
        return array_merge([
            'store_name' => Setting::getValue('store_name', 'EK Electronics'),
            'phone' => Setting::getValue('phone', '+27 10 500 2140'),
            'email' => Setting::getValue('email', 'info@ekelectronics.co.za'),
            'address' => Setting::getValue('address', 'Unit 15, Ground floor, Lone Creek Office Building D, 21 Mac-Mac Road and Howick Close, Waterfall Business Park, Midrand, 1685'),
            'tagline' => Setting::getValue('tagline', 'Innovation. Integrity. Impact.'),
            'hours' => Setting::getValue('hours', 'Mon–Fri 08:00–17:00 SAST'),
            'whatsapp' => WhatsApp::number(),
            'whatsapp_default_message' => Setting::getValue('whatsapp_default_message', 'Hi EK Electronics, I need help with a drive.'),
            'whatsapp_fab_enabled' => Setting::bool('whatsapp_fab_enabled', true) && Setting::bool('whatsapp_enabled', true),
            'whatsapp_chat_position' => Setting::getValue('whatsapp_chat_position', 'bottom-right'),
            'whatsapp_chat_color' => Setting::getValue('whatsapp_chat_color', '#25d366'),
            'whatsapp_chat_label' => Setting::getValue('whatsapp_chat_label', 'WhatsApp us'),
            'whatsapp_chat_subtitle' => Setting::getValue('whatsapp_chat_subtitle', 'Typically replies in minutes during business hours'),
            'whatsapp_chat_auto_open' => Setting::bool('whatsapp_chat_auto_open', false),
            'whatsapp_chat_agent_name' => Setting::getValue('whatsapp_chat_agent_name', 'EK Support'),
            'whatsapp_chat_show_agent' => Setting::bool('whatsapp_chat_show_agent', true),
            'whatsapp_welcome_message' => Setting::getValue('whatsapp_welcome_message', 'Welcome to EK Electronics.'),
            'whatsapp_quick_replies' => WhatsApp::quickReplies(),
            'footer_about' => Setting::getValue('footer_about', 'Hard drive refurbishment, secure erasure, data recovery, and computer components from Midrand.'),
            'footer_col1_title' => Setting::getValue('footer_col1_title', 'Customer services'),
            'footer_col2_title' => Setting::getValue('footer_col2_title', 'Shop'),
            'footer_col3_title' => Setting::getValue('footer_col3_title', 'Contact'),
            'footer_legal' => str_replace('{year}', (string) date('Y'), Setting::getValue('footer_legal', '© {year} EK Electronics · ekelectronics.co.za') ?? ''),
            'footer_show_socials' => Setting::bool('footer_show_socials', true),
            'footer_show_whatsapp' => Setting::bool('footer_show_whatsapp', true),
            'footer_facebook' => Setting::getValue('footer_facebook', ''),
            'footer_instagram' => Setting::getValue('footer_instagram', ''),
            'footer_tiktok' => Setting::getValue('footer_tiktok', ''),
            'footer_x' => Setting::getValue('footer_x', ''),
            'show_prices' => Setting::bool('show_prices', true),
            'customer_login_enabled' => Setting::bool('customer_login_enabled', true),
            'customer_register_enabled' => Setting::bool('customer_register_enabled', true),
            'staff_login_enabled' => Setting::bool('staff_login_enabled', true),
        ], HomepageContent::forView());
    }

    /** @return array<string, \Illuminate\Support\Collection<int, Menu>> */
    private function menus(): array
    {
        $withChildren = fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
        $tops = Menu::query()
            ->with(['children' => $withChildren])
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('location');

        $header = $tops->get('header', collect());
        if ($header->isEmpty()) {
            $header = collect([
                (object) ['label' => 'Home', 'url' => '/', 'target' => '_self', 'is_highlighted' => false, 'children' => collect()],
                (object) ['label' => 'Shop', 'url' => '/shop', 'target' => '_self', 'is_highlighted' => false, 'children' => collect()],
                (object) ['label' => 'Services', 'url' => '/services', 'target' => '_self', 'is_highlighted' => false, 'children' => collect()],
                (object) ['label' => 'About', 'url' => '/about', 'target' => '_self', 'is_highlighted' => false, 'children' => collect()],
                (object) ['label' => 'Contact', 'url' => '/contact', 'target' => '_self', 'is_highlighted' => false, 'children' => collect()],
            ]);
        }

        return [
            'header' => $header,
            'footer' => $tops->get('footer', collect()),
            'footer_shop' => $tops->get('footer_shop', collect()),
            'footer_services' => $tops->get('footer_services', collect()),
            'footer_legal' => $tops->get('footer_legal', collect()),
        ];
    }
}
