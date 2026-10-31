<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\PartCategory;
use App\Models\PartPrice;
use App\Models\PriceTier;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PartImportController extends Controller
{
    public function form()
    {
        PriceTier::ensureDefaults();

        return view('parts.import', [
            'warehouses' => Warehouse::orderBy('name')->get(),
            'categories' => PartCategory::optionsTree(),
            'tiers' => PriceTier::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'update_existing' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('file')->getRealPath();
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $rows = $this->parseFile($path, $ext);
        if ($rows === []) {
            return back()->withErrors(['file' => 'فایل خالی است یا خوانده نشد. از CSV با جداکننده ویرگول استفاده کنید.']);
        }

        $header = array_map(fn ($h) => $this->normHeader((string) $h), array_shift($rows));
        $map = $this->mapColumns($header);
        if (! isset($map['name']) && ! isset($map['code'])) {
            return back()->withErrors(['file' => 'ستون name یا code لازم است.']);
        }

        $wh = $data['warehouse_id'] ?? Warehouse::defaultId();
        $update = $request->boolean('update_existing', true);
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, $map, $wh, $update, &$created, &$updated) {
            foreach ($rows as $row) {
                if (! is_array($row) || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }
                $get = function (string $key) use ($row, $map) {
                    if (! isset($map[$key])) {
                        return null;
                    }

                    return trim((string) ($row[$map[$key]] ?? ''));
                };

                $code = $get('code');
                $name = $get('name');
                if (($name === null || $name === '') && ($code === null || $code === '')) {
                    continue;
                }

                $part = null;
                if ($code) {
                    $part = Part::query()->where('code', $code)->orWhere('barcode', $code)->orWhere('tech_code', $code)->first();
                }
                if (! $part && $name) {
                    $part = Part::query()->where('name', $name)->first();
                }

                $payload = [
                    'warehouse_id' => $wh,
                    'name' => $name ?: ($part?->name ?: $code),
                    'code' => $code ?: ($part?->code),
                    'tech_code' => $get('tech_code') ?: ($part?->tech_code),
                    'barcode' => $get('barcode') ?: ($part?->barcode),
                    'brand' => $get('brand') ?: ($part?->brand),
                    'model' => $get('model') ?: ($part?->model),
                    'keywords' => $get('keywords') ?: ($part?->keywords),
                    'description' => $get('description') ?: ($part?->description),
                    'item_type' => in_array($get('item_type'), ['shop', 'repair', 'labor'], true) ? $get('item_type') : ($part?->item_type ?: 'repair'),
                    'purchase_price' => $this->intOr($get('purchase_price'), $part?->purchase_price ?? 0),
                    'sale_price' => $this->intOr($get('sale_price'), $part?->sale_price ?? 0),
                    'min_stock' => $this->intOr($get('min_stock'), $part?->min_stock ?? 0),
                    'discount_percent' => min(100, $this->intOr($get('discount_percent'), $part?->discount_percent ?? 0)),
                    'is_active' => true,
                ];

                $stock = $get('stock');
                if ($part && $update) {
                    $part->update($payload);
                    if ($stock !== null && $stock !== '') {
                        $newStock = (int) $stock;
                        $diff = $newStock - (int) $part->stock;
                        $part->stock = $newStock;
                        $part->save();
                        if ($diff !== 0) {
                            StockMovement::create([
                                'doc_no' => StockMovement::nextDocNo('IMP'),
                                'part_id' => $part->id,
                                'warehouse_id' => $part->warehouse_id,
                                'user_id' => Auth::id(),
                                'type' => 'adjust',
                                'doc_type' => 'adjust',
                                'quantity' => $diff,
                                'unit_cost' => (int) $part->purchase_price,
                                'total_cost' => abs($diff) * (int) $part->purchase_price,
                                'stock_after' => (int) $part->stock,
                                'note' => 'ورود/ویرایش از فایل',
                            ]);
                        }
                    }
                    $updated++;
                } elseif (! $part) {
                    $payload['stock'] = $stock !== null && $stock !== '' ? (int) $stock : 0;
                    if (empty($payload['barcode']) && ! empty($payload['code'])) {
                        $payload['barcode'] = $payload['code'];
                    }
                    $part = Part::create($payload);
                    if ((int) $part->stock > 0) {
                        StockMovement::create([
                            'doc_no' => StockMovement::nextDocNo('IMP'),
                            'part_id' => $part->id,
                            'warehouse_id' => $part->warehouse_id,
                            'user_id' => Auth::id(),
                            'type' => 'in',
                            'doc_type' => 'opening',
                            'quantity' => (int) $part->stock,
                            'unit_cost' => (int) $part->purchase_price,
                            'total_cost' => (int) $part->stock * (int) $part->purchase_price,
                            'stock_after' => (int) $part->stock,
                            'note' => 'موجودی اول از فایل',
                        ]);
                    }
                    $created++;
                }
            }
        });

        return back()->with('success', "ورود فایل انجام شد. جدید: {$created} — به‌روز: {$updated}");
    }

    public function bulkPrices(Request $request)
    {
        $data = $request->validate([
            'percent' => ['required', 'numeric', 'min:-90', 'max:500'],
            'field' => ['required', 'in:sale_price,purchase_price'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'item_type' => ['nullable', 'in:shop,repair,labor'],
        ]);

        $q = Part::query()->where('is_active', true);
        if (! empty($data['warehouse_id'])) {
            $q->where('warehouse_id', $data['warehouse_id']);
        }
        if (! empty($data['item_type'])) {
            $q->where('item_type', $data['item_type']);
        }

        $factor = 1 + ((float) $data['percent'] / 100);
        $field = $data['field'];
        $n = 0;
        $q->orderBy('id')->chunkById(200, function ($parts) use ($factor, $field, &$n) {
            foreach ($parts as $part) {
                $part->{$field} = max(0, (int) round((int) $part->{$field} * $factor));
                $part->save();
                $n++;
            }
        });

        return back()->with('success', "قیمت {$n} کالا با {$data['percent']}٪ تغییر کرد.");
    }

    public function tiers()
    {
        PriceTier::ensureDefaults();

        return view('parts.price-tiers', [
            'tiers' => PriceTier::orderBy('sort_order')->get(),
        ]);
    }

    public function storeTier(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'code' => ['nullable', 'string', 'max:40'],
        ]);
        PriceTier::ensureDefaults();
        PriceTier::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'sort_order' => (int) PriceTier::max('sort_order') + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'تیپ قیمتی اضافه شد.');
    }

    public function savePartTierPrices(Request $request, Part $part)
    {
        $data = $request->validate([
            'prices' => ['nullable', 'array'],
            'prices.*' => ['nullable', 'integer', 'min:0'],
        ]);
        foreach ($data['prices'] ?? [] as $tierId => $price) {
            if ($price === null || $price === '') {
                continue;
            }
            PartPrice::updateOrCreate(
                ['part_id' => $part->id, 'price_tier_id' => (int) $tierId],
                ['price' => (int) $price]
            );
        }

        return back()->with('success', 'قیمت تیپ‌ها ذخیره شد.');
    }

    private function parseFile(string $path, string $ext): array
    {
        // Prefer CSV (Excel → Save as CSV). XLSX without library: try best-effort as CSV.
        $rows = [];
        if (($fh = fopen($path, 'r')) === false) {
            return [];
        }
        // Detect delimiter
        $first = fgets($fh);
        if ($first === false) {
            fclose($fh);

            return [];
        }
        rewind($fh);
        $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        while (($data = fgetcsv($fh, 0, $delim)) !== false) {
            $rows[] = $data;
        }
        fclose($fh);

        return $rows;
    }

    private function normHeader(string $h): string
    {
        $h = mb_strtolower(trim($h));
        $h = str_replace([' ', '‌'], '', $h);
        $map = [
            'نام' => 'name', 'name' => 'name', 'کالا' => 'name',
            'کد' => 'code', 'code' => 'code', 'کدکالا' => 'code',
            'کدفنی' => 'tech_code', 'tech_code' => 'tech_code', 'techcode' => 'tech_code',
            'بارکد' => 'barcode', 'barcode' => 'barcode',
            'برند' => 'brand', 'brand' => 'brand',
            'مدل' => 'model', 'model' => 'model',
            'موجودی' => 'stock', 'stock' => 'stock',
            'خرید' => 'purchase_price', 'purchase_price' => 'purchase_price', 'بهایخرید' => 'purchase_price',
            'فروش' => 'sale_price', 'sale_price' => 'sale_price', 'فیفروش' => 'sale_price',
            'حداقل' => 'min_stock', 'min_stock' => 'min_stock', 'نقطهسفارش' => 'min_stock',
            'کلیدواژه' => 'keywords', 'keywords' => 'keywords',
            'توضیحات' => 'description', 'description' => 'description',
            'نوع' => 'item_type', 'item_type' => 'item_type',
            'تخفیف' => 'discount_percent', 'discount_percent' => 'discount_percent',
        ];

        return $map[$h] ?? $h;
    }

    private function mapColumns(array $header): array
    {
        $out = [];
        foreach ($header as $i => $h) {
            $key = $this->normHeader((string) $h);
            if ($key !== '') {
                $out[$key] = $i;
            }
        }

        return $out;
    }

    private function intOr(?string $v, int $default): int
    {
        if ($v === null || $v === '') {
            return $default;
        }
        $v = preg_replace('/[^\d\-]/', '', $v) ?? '';

        return (int) $v;
    }
}
