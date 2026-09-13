<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Services\PartnerNetworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function __construct(private PartnerNetworkService $network)
    {
    }

    public function index(Request $request): View
    {
        $sync = $this->network->syncPeers();
        $q = trim((string) $request->input('q', ''));
        $query = Partner::query()->with('customer')->orderByDesc('is_active')->orderBy('name');
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('org_name', 'like', '%'.$q.'%')
                    ->orWhere('shop_name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('domain', 'like', '%'.$q.'%')
                    ->orWhere('code', 'like', '%'.$q.'%');
            });
        }

        return view('partners.index', [
            'partners' => $query->paginate(40)->withQueryString(),
            'q' => $q,
            'sync' => $sync,
        ]);
    }

    public function sync(): RedirectResponse
    {
        $sync = $this->network->syncPeers();

        return back()->with($sync['ok'] ? 'success' : 'error', $sync['message']);
    }

    public function create(): RedirectResponse
    {
        return redirect()
            ->route('partners.index')
            ->with('error', 'همکاران به‌صورت خودکار از لایسنس‌های فعال شبکه اضافه می‌شوند؛ ثبت دستی لازم نیست.');
    }

    public function store(): RedirectResponse
    {
        return $this->create();
    }

    public function edit(Partner $partner): View
    {
        return view('partners.edit', ['partner' => $partner]);
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        // Network identity fields stay synced from licenses; only local notes/active override.
        $partner->update([
            'notes' => $data['notes'] ?? $partner->notes,
            'is_active' => $request->boolean('is_active', $partner->is_active),
        ]);

        return redirect()->route('partners.index')->with('success', 'یادداشت همکار ذخیره شد.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $partner->update(['is_active' => false]);

        return back()->with('success', 'همکار در فهرست محلی غیرفعال شد (از شبکه لایسنس حذف نمی‌شود).');
    }
}
