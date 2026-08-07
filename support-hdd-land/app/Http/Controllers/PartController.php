<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\StockMovement;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $parts = Part::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('brand', 'like', "%{$q}%")
                        ->orWhere('model', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('parts.index', compact('parts', 'q'));
    }

    public function create()
    {
        return view('parts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Part::create($data);

        return redirect()->route('parts.index')->with('success', 'قطعه ثبت شد.');
    }

    public function edit(Part $part)
    {
        return view('parts.edit', compact('part'));
    }

    public function update(Request $request, Part $part)
    {
        $part->update($this->validated($request, false));

        return redirect()->route('parts.index')->with('success', 'قطعه به‌روزرسانی شد.');
    }

    public function adjustStock(Request $request, Part $part)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $part) {
            $part = Part::lockForUpdate()->findOrFail($part->id);
            $qty = (int) $data['quantity'];
            $part->stock = max(0, $part->stock + $qty);
            $part->save();

            $movement = StockMovement::create([
                'part_id' => $part->id,
                'user_id' => Auth::id(),
                'type' => $qty >= 0 ? 'in' : 'out',
                'quantity' => $qty,
                'stock_after' => $part->stock,
                'note' => $data['note'] ?? 'تنظیم دستی موجودی',
            ]);

            try {
                app(AccountingService::class)->postStockPurchase(
                    $movement->id,
                    $part,
                    $qty,
                    (int) $part->purchase_price,
                    $data['note'] ?? ''
                );
            } catch (\Throwable $e) {
            }
        });

        return back()->with('success', 'موجودی به‌روزرسانی شد.');
    }

    private function validated(Request $request, bool $withStock = true): array
    {
        $rules = [
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'purchase_price' => ['nullable', 'integer', 'min:0'],
            'sale_price' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($withStock) {
            $rules['stock'] = ['nullable', 'integer', 'min:0'];
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['purchase_price'] = (int) ($data['purchase_price'] ?? 0);
        $data['sale_price'] = (int) ($data['sale_price'] ?? 0);
        $data['min_stock'] = (int) ($data['min_stock'] ?? 0);

        if ($withStock) {
            $data['stock'] = (int) ($data['stock'] ?? 0);
        }

        return $data;
    }
}
