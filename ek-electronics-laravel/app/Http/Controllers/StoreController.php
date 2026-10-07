<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Services\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function home(): View
    {
        return view('store.home', [
            'featured' => Product::query()->with('category')->where('is_active', true)->where('is_featured', true)->take(8)->get(),
            'settings' => $this->settings(),
        ]);
    }

    public function shop(Request $request): View
    {
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
        ]);
    }

    public function services(): View
    {
        return view('store.services', ['settings' => $this->settings()]);
    }

    public function about(): View
    {
        return view('store.about', ['settings' => $this->settings()]);
    }

    public function contact(): View
    {
        return view('store.contact', ['settings' => $this->settings()]);
    }

    public function page(string $slug): View
    {
        abort_unless(in_array($slug, ['shipping', 'warranty', 'terms'], true), 404);

        return view('store.page', ['slug' => $slug, 'settings' => $this->settings()]);
    }

    public function cart(): View
    {
        return view('store.cart', [
            'items' => $this->cartItems(),
            'settings' => $this->settings(),
        ]);
    }

    public function checkout(): View
    {
        return view('store.checkout', [
            'items' => $this->cartItems(),
            'settings' => $this->settings(),
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
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
        $msg = "New order {$order->number} from {$order->customer_name}\nPhone: {$order->customer_phone}\nShip to: {$order->city}\n\n{$lines}\n\nTotal: R ".number_format((float) $order->total, 2)."\nPlease confirm stock and courier.";

        $order->update(['whatsapp_payload' => $msg, 'whatsapp_sent_at' => now()]);
        WhatsApp::logOutbound($msg, WhatsApp::number(), 'order', Order::class, $order->id);

        Invoice::query()->create([
            'number' => 'INV-'.$order->number,
            'order_id' => $order->id,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'amount' => $order->total,
            'vat_amount' => round(((float) $order->total) * 15 / 115, 2),
            'status' => 'open',
            'due_date' => now()->addDays(7),
        ]);

        session()->forget('cart');

        return redirect()->away(WhatsApp::link($msg));
    }

    public function contactWhatsApp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $msg = "Enquiry from {$data['name']}\n{$data['email']}\n\n{$data['message']}";
        WhatsApp::logOutbound($msg, WhatsApp::number(), 'contact');

        return redirect()->away(WhatsApp::link($msg));
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

    private function settings(): array
    {
        return [
            'phone' => Setting::getValue('phone', '+27 10 500 2140'),
            'email' => Setting::getValue('email', 'info@ekelectronics.co.za'),
            'address' => Setting::getValue('address', 'Unit 15, Ground floor, Lone Creek Office Building D, 21 Mac-Mac Road and Howick Close, Waterfall Business Park, Midrand, 1685'),
            'tagline' => Setting::getValue('tagline', 'Innovation. Integrity. Impact.'),
            'whatsapp' => WhatsApp::number(),
        ];
    }
}
