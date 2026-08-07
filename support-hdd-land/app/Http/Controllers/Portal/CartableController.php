<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Reception;
use App\Support\PaymentGateways;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartableController extends Controller
{
    public function home(Request $request)
    {
        $customer = $this->customer($request);
        $stats = $this->stats($customer);

        $menus = [
            ['route' => 'portal.tickets', 'params' => [], 'label' => 'همه قبض‌ها', 'hint' => 'لیست کامل دستگاه‌ها', 'icon' => '▤', 'tone' => 'teal'],
            ['route' => 'portal.search', 'params' => [], 'label' => 'جستجوی قبض', 'hint' => 'شماره / سریال', 'icon' => '⌕', 'tone' => 'blue'],
            ['route' => 'portal.tickets', 'params' => ['status' => 'repairing'], 'label' => 'در حال تعمیر', 'hint' => $stats['repairing'].' دستگاه', 'icon' => '⚙', 'tone' => 'amber'],
            ['route' => 'portal.tickets', 'params' => ['status' => 'ready'], 'label' => 'آماده تحویل', 'hint' => 'هزینه و لینک پرداخت', 'icon' => '✓', 'tone' => 'green'],
            ['route' => 'portal.tickets', 'params' => ['status' => 'waiting_part'], 'label' => 'منتظر قطعه', 'hint' => $stats['waiting_part'].' مورد', 'icon' => '◈', 'tone' => 'rose'],
            ['route' => 'portal.report', 'params' => [], 'label' => 'گزارش وضعیت', 'hint' => 'خلاصه تعمیرات', 'icon' => '▦', 'tone' => 'violet'],
            ['route' => 'portal.tickets', 'params' => ['status' => 'delivered'], 'label' => 'تحویل‌شده‌ها', 'hint' => $stats['delivered'].' قبض', 'icon' => '↩', 'tone' => 'slate'],
            ['route' => 'portal.pay', 'params' => [], 'label' => 'پرداخت آنلاین', 'hint' => 'درگاه‌های بانکی', 'icon' => '₿', 'tone' => 'gold'],
        ];

        $ready = $customer->receptions()
            ->withCount('parts')
            ->where('status', 'ready')
            ->latest('id')
            ->limit(5)
            ->get();

        $recent = $customer->receptions()
            ->withCount('parts')
            ->whereNotIn('status', ['cancelled'])
            ->latest('id')
            ->limit(6)
            ->get();

        return view('portal.home', compact('customer', 'stats', 'menus', 'ready', 'recent'));
    }

    public function tickets(Request $request)
    {
        $customer = $this->customer($request);
        $status = (string) $request->query('status', '');
        $allowed = array_keys(Reception::STATUSES);

        $query = $customer->receptions()->withCount('parts')->latest('id');
        if ($status !== '' && in_array($status, $allowed, true)) {
            $query->where('status', $status);
        } else {
            $status = '';
            $query->where('status', '!=', 'cancelled');
        }

        $tickets = $query->paginate(12)->withQueryString();
        $title = $status !== '' ? (Reception::STATUSES[$status] ?? 'قبض‌ها') : 'همه قبض‌ها';
        $payLinks = PaymentGateways::active();

        return view('portal.tickets', compact('customer', 'tickets', 'status', 'title', 'payLinks'));
    }

    public function search(Request $request)
    {
        $customer = $this->customer($request);
        $q = trim((string) $request->query('q', ''));
        $tickets = collect();

        if ($q !== '') {
            $like = '%'.$q.'%';
            $tickets = $customer->receptions()
                ->withCount('parts')
                ->where(function ($w) use ($like, $q) {
                    $w->where('ticket_no', 'like', $like)
                        ->orWhere('receipt_no', 'like', $like)
                        ->orWhere('serial_number', 'like', $like)
                        ->orWhere('product_name', 'like', $like)
                        ->orWhere('brand', 'like', $like)
                        ->orWhere('model', 'like', $like);
                    if (ctype_digit($q)) {
                        $w->orWhere('id', (int) $q);
                    }
                })
                ->latest('id')
                ->limit(40)
                ->get();
        }

        return view('portal.search', compact('customer', 'q', 'tickets'));
    }

    public function show(Request $request, Reception $reception)
    {
        $customer = $this->customer($request);
        abort_unless((int) $reception->customer_id === (int) $customer->id, 404);

        $reception->load(['parts', 'payments', 'faultType', 'technician']);
        $payLinks = $reception->status === 'ready' ? PaymentGateways::active() : [];

        return view('portal.show', compact('customer', 'reception', 'payLinks'));
    }

    public function report(Request $request)
    {
        $customer = $this->customer($request);
        $stats = $this->stats($customer);

        $byStatus = $customer->receptions()
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status');

        $open = $customer->receptions()
            ->withCount('parts')
            ->whereIn('status', ['received', 'repairing', 'waiting_part', 'ready'])
            ->latest('id')
            ->get();

        return view('portal.report', compact('customer', 'stats', 'byStatus', 'open'));
    }

    public function pay(Request $request)
    {
        $customer = $this->customer($request);
        $payLinks = PaymentGateways::active();
        $ready = $customer->receptions()
            ->withCount('parts')
            ->where('status', 'ready')
            ->latest('id')
            ->get();

        return view('portal.pay', compact('customer', 'payLinks', 'ready'));
    }

    private function customer(Request $request): Customer
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('portalCustomer');

        return $customer;
    }

    /**
     * @return array{total:int,open:int,repairing:int,waiting_part:int,ready:int,delivered:int}
     */
    private function stats(Customer $customer): array
    {
        $base = $customer->receptions();

        return [
            'total' => (clone $base)->where('status', '!=', 'cancelled')->count(),
            'open' => (clone $base)->whereIn('status', ['received', 'repairing', 'waiting_part', 'ready'])->count(),
            'repairing' => (clone $base)->where('status', 'repairing')->count(),
            'waiting_part' => (clone $base)->where('status', 'waiting_part')->count(),
            'ready' => (clone $base)->where('status', 'ready')->count(),
            'delivered' => (clone $base)->where('status', 'delivered')->count(),
        ];
    }
}
