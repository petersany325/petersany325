<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $query = Partner::query()->with('customer')->orderBy('name');
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('shop_name', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('code', 'like', '%'.$q.'%');
            });
        }

        return view('partners.index', [
            'partners' => $query->paginate(40)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('partners.create', ['partner' => new Partner(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $partner = Partner::query()->create($data);
        $partner->ensureCustomer();

        return redirect()
            ->route('partners.index')
            ->with('success', 'نماینده ثبت شد. قبض‌های ارجاعی به نام همین نماینده صادر می‌شوند.');
    }

    public function edit(Partner $partner): View
    {
        return view('partners.edit', ['partner' => $partner]);
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $partner->update($this->validated($request));
        $partner->ensureCustomer();

        return redirect()
            ->route('partners.index')
            ->with('success', 'نماینده به‌روز شد.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        if ($partner->receptions()->exists()) {
            $partner->update(['is_active' => false]);

            return back()->with('success', 'نماینده قبض دارد؛ غیرفعال شد (حذف نشد).');
        }
        $partner->delete();

        return back()->with('success', 'نماینده حذف شد.');
    }

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'shop_name' => ['nullable', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
