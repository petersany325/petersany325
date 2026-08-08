<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $filter = (string) $request->get('filter', 'all');
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $parts = Part::query()
            ->with('warehouse')
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('brand', 'like', "%{$q}%")
                        ->orWhere('model', 'like', "%{$q}%");
                });
            })
            ->when($filter === 'low', fn ($query) => $query->whereColumn('stock', '<=', 'min_stock'))
            ->when($filter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $all = Part::query()->where('is_active', true)
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId));
        $stats = [
            'sku' => (clone $all)->count(),
            'qty' => (int) (clone $all)->sum('stock'),
            'value_cost' => (int) (clone $all)->selectRaw('COALESCE(SUM(stock * purchase_price),0) as v')->value('v'),
            'value_sale' => (int) (clone $all)->selectRaw('COALESCE(SUM(stock * sale_price),0) as v')->value('v'),
            'low' => (clone $all)->whereColumn('stock', '<=', 'min_stock')->count(),
        ];

        $recent = StockMovement::query()
            ->with(['part', 'user', 'reception', 'warehouse'])
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->latest('id')
            ->limit(12)
            ->get();

        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();

        return view('parts.index', compact('parts', 'q', 'filter', 'stats', 'recent', 'warehouses', 'warehouseId'));
    }

    public function create()
    {
        return view('parts.create', [
            'warehouses' => Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data) {
            $stock = (int) ($data['stock'] ?? 0);
            $data['stock'] = 0;
            if (empty($data['warehouse_id'])) {
                $data['warehouse_id'] = Warehouse::defaultId();
            }
            $part = Part::create($data);

            if ($stock > 0) {
                $this->applyMovement($part, $stock, 'in', 'opening', (int) $part->purchase_price, 'موجودی اول دوره هنگام تعریف قطعه');
            }
        });

        return redirect()->route('parts.index')->with('success', 'قطعه در انبار ثبت شد.');
    }

    public function edit(Part $part)
    {
        return view('parts.edit', [
            'part' => $part->load('warehouse'),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Part $part)
    {
        $part->update($this->validated($request, false));

        return redirect()->route('parts.show', $part)->with('success', 'کارت کالا به‌روزرسانی شد.');
    }

    public function show(Part $part)
    {
        $part->load('warehouse');
        $movements = $part->stockMovements()
            ->with(['user', 'reception', 'warehouse'])
            ->latest('id')
            ->paginate(40);

        $valueCost = (int) $part->stock * (int) $part->purchase_price;
        $valueSale = (int) $part->stock * (int) $part->sale_price;

        return view('parts.show', compact('part', 'movements', 'valueCost', 'valueSale'));
    }

    public function movements(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $type = (string) $request->get('type', '');
        $docType = (string) $request->get('doc_type', '');
        [$defaultFrom] = jalali_period_range('this_month');
        $from = resolve_request_date($request->get('from'), $defaultFrom);
        $to = resolve_request_date($request->get('to'), now()->toDateString());

        $movements = StockMovement::query()
            ->with(['part', 'user', 'reception'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($docType !== '', fn ($query) => $query->where('doc_type', $docType))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('doc_no', 'like', "%{$q}%")
                        ->orWhere('note', 'like', "%{$q}%")
                        ->orWhereHas('part', function ($p) use ($q) {
                            $p->where('name', 'like', "%{$q}%")
                                ->orWhere('code', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(40)
            ->withQueryString();

        $summary = [
            'in_qty' => (int) StockMovement::query()->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->where('type', 'in')->sum('quantity'),
            'out_qty' => (int) abs((int) StockMovement::query()->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->where('type', 'out')->sum('quantity')),
            'in_cost' => (int) StockMovement::query()->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->where('type', 'in')->sum('total_cost'),
            'out_cost' => (int) StockMovement::query()->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->where('type', 'out')->sum('total_cost'),
        ];

        return view('parts.movements', [
            'movements' => $movements,
            'q' => $q,
            'type' => $type,
            'docType' => $docType,
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'docTypes' => StockMovement::DOC_TYPES,
        ]);
    }

    public function receiptForm()
    {
        return view('parts.receipt', [
            'parts' => Part::with('warehouse')->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function receiptStore(Request $request)
    {
        $data = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'update_purchase_price' => ['nullable', 'boolean'],
        ]);

        $part = Part::findOrFail($data['part_id']);
        $unitCost = array_key_exists('unit_cost', $data) && $data['unit_cost'] !== null
            ? (int) $data['unit_cost']
            : (int) $part->purchase_price;

        $updatePrice = $request->boolean('update_purchase_price');

        DB::transaction(function () use ($data, $part, $unitCost, $updatePrice) {
            if ($updatePrice && $unitCost > 0) {
                $part->purchase_price = $unitCost;
                $part->save();
            }
            $this->applyMovement(
                $part->fresh(),
                (int) $data['quantity'],
                'in',
                'purchase',
                $unitCost,
                $data['note'] ?? 'رسید خرید / ورود انبار'
            );
        });

        return redirect()->route('parts.movements')->with('success', 'رسید ورود انبار ثبت شد.');
    }

    public function issueForm()
    {
        return view('parts.issue', [
            'parts' => Part::with('warehouse')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function issueStore(Request $request)
    {
        $data = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $part = Part::findOrFail($data['part_id']);
        if ((int) $part->stock < (int) $data['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => 'موجودی کافی نیست (موجودی فعلی: '.$part->stock.').',
            ]);
        }

        DB::transaction(function () use ($data, $part) {
            $this->applyMovement(
                $part,
                -1 * abs((int) $data['quantity']),
                'out',
                'issue',
                (int) $part->purchase_price,
                $data['note'] ?? 'حواله خروج انبار'
            );
        });

        return redirect()->route('parts.movements')->with('success', 'حواله خروج ثبت شد.');
    }

    public function valuation()
    {
        $parts = Part::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Part $part) {
                $part->value_cost = (int) $part->stock * (int) $part->purchase_price;
                $part->value_sale = (int) $part->stock * (int) $part->sale_price;
                $part->margin = $part->value_sale - $part->value_cost;

                return $part;
            });

        $totals = [
            'qty' => (int) $parts->sum('stock'),
            'cost' => (int) $parts->sum('value_cost'),
            'sale' => (int) $parts->sum('value_sale'),
            'margin' => (int) $parts->sum('margin'),
        ];

        return view('parts.valuation', compact('parts', 'totals'));
    }

    public function adjustStock(Request $request, Part $part)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
        ]);

        $qty = (int) $data['quantity'];
        $unitCost = array_key_exists('unit_cost', $data) && $data['unit_cost'] !== null
            ? (int) $data['unit_cost']
            : (int) $part->purchase_price;

        DB::transaction(function () use ($data, $part, $qty, $unitCost) {
            $this->applyMovement(
                $part,
                $qty,
                $qty >= 0 ? 'in' : 'out',
                'adjust',
                $unitCost,
                $data['note'] ?? 'تعدیل دستی موجودی'
            );
        });

        return back()->with('success', 'موجودی تعدیل شد و سند انبار ثبت گردید.');
    }

    /**
     * Apply stock change, movement row, and accounting entry.
     */
    private function applyMovement(
        Part $part,
        int $qty,
        string $type,
        string $docType,
        int $unitCost,
        ?string $note = null,
        ?int $receptionId = null
    ): StockMovement {
        $part = Part::lockForUpdate()->findOrFail($part->id);
        $newStock = (int) $part->stock + $qty;
        if ($newStock < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'موجودی نمی‌تواند منفی شود.',
            ]);
        }

        $part->stock = $newStock;
        $part->save();

        $prefix = match ($docType) {
            'purchase', 'receipt', 'opening' => 'IN',
            'issue', 'consumption' => 'OUT',
            'return' => 'RT',
            default => 'ADJ',
        };

        $movement = StockMovement::create([
            'doc_no' => StockMovement::nextDocNo($prefix),
            'part_id' => $part->id,
            'warehouse_id' => $part->warehouse_id ?: Warehouse::defaultId(),
            'reception_id' => $receptionId,
            'user_id' => Auth::id(),
            'type' => $type,
            'doc_type' => $docType,
            'quantity' => $qty,
            'unit_cost' => max(0, $unitCost),
            'total_cost' => abs($qty) * max(0, $unitCost),
            'stock_after' => $part->stock,
            'note' => $note,
        ]);

        try {
            app(AccountingService::class)->postStockPurchase(
                $movement->id,
                $part,
                $qty,
                max(0, $unitCost),
                $note ?? ''
            );
        } catch (\Throwable $e) {
        }

        return $movement;
    }

    private function validated(Request $request, bool $withStock = true): array
    {
        $rules = [
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
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
        $data['warehouse_id'] = ! empty($data['warehouse_id'])
            ? (int) $data['warehouse_id']
            : Warehouse::defaultId();

        if ($withStock) {
            $data['stock'] = (int) ($data['stock'] ?? 0);
        }

        return $data;
    }
}
