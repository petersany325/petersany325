<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\StockMovement;
use App\Models\Stocktake;
use App\Models\StocktakeLine;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseOpsController extends Controller
{
    public function transfers(Request $request)
    {
        $items = WarehouseTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'part', 'user'])
            ->latest('id')
            ->paginate(30);

        return view('warehouse.transfers', [
            'items' => $items,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'parts' => Part::where('is_active', true)->orderByDesc('usage_count')->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function storeTransfer(Request $request)
    {
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'part_id' => ['required', 'exists:parts,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $part = Part::findOrFail($data['part_id']);
        if ((int) $part->warehouse_id !== (int) $data['from_warehouse_id']) {
            throw ValidationException::withMessages([
                'from_warehouse_id' => 'این کالا در انبار مبدأ نیست (انبار فعلی کالا: '.($part->warehouse?->name ?: '—').').',
            ]);
        }
        if ((int) $part->stock < (int) $data['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => 'موجودی کافی نیست (موجودی: '.$part->stock.').',
            ]);
        }

        DB::transaction(function () use ($data, $part) {
            $qty = (int) $data['quantity'];
            $doc = StockMovement::nextDocNo('TR');

            $part->stock = (int) $part->stock - $qty;
            $part->warehouse_id = (int) $data['to_warehouse_id'];
            $part->save();

            StockMovement::create([
                'doc_no' => $doc,
                'part_id' => $part->id,
                'warehouse_id' => $data['from_warehouse_id'],
                'user_id' => Auth::id(),
                'type' => 'out',
                'doc_type' => 'transfer_out',
                'quantity' => -$qty,
                'unit_cost' => (int) $part->purchase_price,
                'total_cost' => (int) $part->purchase_price * $qty,
                'stock_after' => (int) $part->stock,
                'note' => 'انتقال به انبار #'.$data['to_warehouse_id'].($data['note'] ? ' — '.$data['note'] : ''),
            ]);

            StockMovement::create([
                'doc_no' => $doc,
                'part_id' => $part->id,
                'warehouse_id' => $data['to_warehouse_id'],
                'user_id' => Auth::id(),
                'type' => 'in',
                'doc_type' => 'transfer_in',
                'quantity' => $qty,
                'unit_cost' => (int) $part->purchase_price,
                'total_cost' => (int) $part->purchase_price * $qty,
                'stock_after' => (int) $part->stock,
                'note' => 'انتقال از انبار #'.$data['from_warehouse_id'].($data['note'] ? ' — '.$data['note'] : ''),
            ]);

            WarehouseTransfer::create([
                'doc_no' => $doc,
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'part_id' => $part->id,
                'quantity' => $qty,
                'user_id' => Auth::id(),
                'note' => $data['note'] ?? null,
            ]);
        });

        return back()->with('success', 'انتقال بین انبار ثبت شد.');
    }

    public function stocktakes()
    {
        $items = Stocktake::query()->with(['warehouse', 'creator'])->latest('id')->paginate(20);

        return view('warehouse.stocktakes', [
            'items' => $items,
            'warehouses' => Warehouse::orderBy('name')->get(),
        ]);
    }

    public function createStocktake(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $stocktake = Stocktake::create([
            'doc_no' => StockMovement::nextDocNo('ST'),
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'status' => 'draft',
            'created_by' => Auth::id(),
            'note' => $data['note'] ?? null,
        ]);

        $parts = Part::query()
            ->when(! empty($data['warehouse_id']), fn ($q) => $q->where('warehouse_id', $data['warehouse_id']))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($parts as $part) {
            StocktakeLine::create([
                'stocktake_id' => $stocktake->id,
                'part_id' => $part->id,
                'system_qty' => (int) $part->stock,
                'counted_qty' => (int) $part->stock,
                'diff_qty' => 0,
            ]);
        }

        return redirect()->route('stocktakes.show', $stocktake)->with('success', 'سند انبارگردانی ساخته شد. شمارش را وارد کنید.');
    }

    public function showStocktake(Stocktake $stocktake)
    {
        $stocktake->load(['lines.part', 'warehouse', 'creator']);

        return view('warehouse.stocktake-show', compact('stocktake'));
    }

    public function updateStocktake(Request $request, Stocktake $stocktake)
    {
        abort_unless($stocktake->status === 'draft', 422);
        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.counted_qty' => ['required', 'integer', 'min:0'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data['lines'] as $row) {
            $line = StocktakeLine::where('stocktake_id', $stocktake->id)->where('id', $row['id'])->first();
            if (! $line) {
                continue;
            }
            $counted = (int) $row['counted_qty'];
            $line->update([
                'counted_qty' => $counted,
                'diff_qty' => $counted - (int) $line->system_qty,
                'note' => $row['note'] ?? null,
            ]);
        }

        return back()->with('success', 'شمارش ذخیره شد.');
    }

    public function postStocktake(Stocktake $stocktake)
    {
        abort_unless($stocktake->status === 'draft', 422);

        DB::transaction(function () use ($stocktake) {
            $stocktake->load('lines.part');
            foreach ($stocktake->lines as $line) {
                if ((int) $line->diff_qty === 0) {
                    continue;
                }
                $part = $line->part;
                if (! $part) {
                    continue;
                }
                $part->stock = (int) $line->counted_qty;
                $part->save();
                StockMovement::create([
                    'doc_no' => $stocktake->doc_no,
                    'part_id' => $part->id,
                    'warehouse_id' => $part->warehouse_id,
                    'user_id' => Auth::id(),
                    'type' => 'adjust',
                    'doc_type' => 'stocktake',
                    'quantity' => (int) $line->diff_qty,
                    'unit_cost' => (int) $part->purchase_price,
                    'total_cost' => abs((int) $line->diff_qty) * (int) $part->purchase_price,
                    'stock_after' => (int) $part->stock,
                    'note' => 'انبارگردانی '.$stocktake->doc_no,
                ]);
            }
            $stocktake->update(['status' => 'posted', 'posted_at' => now()]);
        });

        return redirect()->route('stocktakes.index')->with('success', 'انبارگردانی ثبت قطعی شد و موجودی‌ها تعدیل شدند.');
    }
}
