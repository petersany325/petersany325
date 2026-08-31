<?php

namespace Plugins\Catalog\src\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Plugins\AdminCore\src\Support\SerialSupport;
use Plugins\Catalog\src\Models\Product;

class ProductSerialController extends Controller
{
    public function index(Product $product): View
    {
        SerialSupport::ensureSchema();
        \Plugins\Catalog\Plugin::ensureProductCardColumns();

        $serialQuery = Schema::hasTable('product_serials')
            ? DB::table('product_serials')->where('product_id', $product->id)
            : null;
        $serials = $serialQuery
            ? (clone $serialQuery)->orderByDesc('id')->limit(500)->get()
            : collect();
        $companies = Schema::hasTable('warranty_companies')
            ? DB::table('warranty_companies')->where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get()
            : collect();
        $s = SerialSupport::settings();

        return view('catalog::admin.product-serials', [
            'product' => $product,
            'serials' => $serials,
            'companies' => $companies,
            's' => $s,
            'stats' => [
                'available' => $serialQuery ? (clone $serialQuery)->where('status', 'available')->count() : 0,
                'sold' => $serialQuery ? (clone $serialQuery)->where('status', 'sold')->count() : 0,
                'total' => $serialQuery ? (clone $serialQuery)->count() : 0,
            ],
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse|JsonResponse
    {
        SerialSupport::ensureSchema();
        $s = SerialSupport::settings();
        $data = $request->validate([
            'serial' => ['nullable', 'string', 'max:120'],
            'bulk' => ['nullable', 'string', 'max:100000'],
            'source' => ['nullable', 'in:manual,barcode,excel'],
            'warranty_company' => ['required', 'string', 'max:160'],
            'company_warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'has_company_warranty' => ['nullable'],
        ], [
            'warranty_company.required' => 'ابتدا شرکت گارانتی را انتخاب کنید.',
        ]);
        if (blank($data['serial'] ?? null) && blank($data['bulk'] ?? null)) {
            return back()->with('error', 'سریال را وارد کنید.')->withInput();
        }

        $rows = [];
        if (! empty($data['bulk'])) {
            foreach (preg_split('/[\r\n,;]+/', $data['bulk']) ?: [] as $line) {
                $serial = trim($line);
                if ($serial !== '') {
                    $rows[] = $this->withPrefix($serial, (string) ($s['serial_prefix'] ?? ''));
                }
            }
        } else {
            $rows[] = $this->withPrefix((string) $data['serial'], (string) ($s['serial_prefix'] ?? ''));
        }

        $company = trim((string) $data['warranty_company']);
        $months = (int) ($data['company_warranty_months'] ?? $s['default_company_warranty_months'] ?? 12);
        $result = $this->insert($rows, [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'status' => 'available',
            'source' => $data['source'] ?? 'manual',
            'warranty_company' => $company,
            'company_warranty_months' => $months,
            'has_company_warranty' => $request->boolean('has_company_warranty', true),
            'show_in_sales' => true,
            'allow_public_lookup' => true,
        ]);

        // sync stock with available serials if managing stock
        $avail = DB::table('product_serials')->where('product_id', $product->id)->where('status', 'available')->count();
        if ($product->manage_stock ?? true) {
            $product->update(['stock' => $avail, 'stock_status' => $avail > 0 ? 'instock' : 'outofstock']);
        }

        $message = $this->resultMessage($result, "برای «{$product->name}» ثبت شد");
        if ($request->expectsJson()) {
            return response()->json(['ok'=>true, 'message'=>$message, 'added'=>$result['added'], 'skipped'=>$result['skipped'], 'available'=>$avail]);
        }

        return back()->with('success', $message);
    }

    public function import(Request $request, Product $product): RedirectResponse
    {
        SerialSupport::ensureSchema();
        $s = SerialSupport::settings();
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'warranty_company' => ['required', 'string', 'max:160'],
            'company_warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
        ], [
            'warranty_company.required' => 'ابتدا شرکت گارانتی را انتخاب کنید.',
        ]);
        $content = (string) file_get_contents($request->file('file')->getRealPath());
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $serials = [];
        $first = true;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cols = str_getcsv($line, str_contains($line, "\t") ? "\t" : ',');
            $cell = trim((string) ($cols[0] ?? ''));
            if ($first && preg_match('/^(serial|سریال|sn)$/iu', $cell)) {
                $first = false;
                continue;
            }
            $first = false;
            if ($cell !== '') {
                $serials[] = $this->withPrefix($cell, (string) ($s['serial_prefix'] ?? ''));
            }
        }
        $result = $this->insert($serials, [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'status' => 'available',
            'source' => 'excel',
            'warranty_company' => trim((string) $request->input('warranty_company')),
            'company_warranty_months' => (int) $request->input('company_warranty_months', $s['default_company_warranty_months'] ?? 12),
            'has_company_warranty' => $request->boolean('has_company_warranty', true),
            'show_in_sales' => true,
            'allow_public_lookup' => true,
        ]);
        if ($product->manage_stock ?? true) {
            $avail = DB::table('product_serials')->where('product_id', $product->id)->where('status', 'available')->count();
            $product->update(['stock' => $avail, 'stock_status' => $avail > 0 ? 'instock' : 'outofstock']);
        }

        return back()->with('success', $this->resultMessage($result, 'از فایل CSV/TXT وارد شد'));
    }

    public function destroy(Product $product, int $serialId): RedirectResponse
    {
        $serial = DB::table('product_serials')->where('id', $serialId)->where('product_id', $product->id)->first();
        if (! $serial) {
            return back()->with('error', 'سریال پیدا نشد.');
        }
        if (($serial->status ?? '') === 'sold') {
            return back()->with('error', 'سریال فروخته‌شده قابل حذف نیست؛ سابقه فروش باید حفظ شود.');
        }
        DB::table('product_serials')->where('id', $serialId)->where('product_id', $product->id)->delete();
        if ($product->manage_stock ?? true) {
            $avail = DB::table('product_serials')->where('product_id', $product->id)->where('status', 'available')->count();
            $product->update(['stock' => $avail, 'stock_status' => $avail > 0 ? 'instock' : 'outofstock']);
        }

        return back()->with('success', 'سریال حذف شد.');
    }

    /** @param  list<string>  $serials @param  array<string,mixed>  $base */
    protected function insert(array $serials, array $base): array
    {
        $added = 0;
        $skipped = 0;
        $now = now();
        $normalized = [];
        foreach ($serials as $serial) {
            $serial = preg_replace('/[\x00-\x1F\x7F]/u', '', trim((string) $serial)) ?? '';
            if ($serial === '' || strlen($serial) > 120) {
                $skipped++;
                continue;
            }
            $normalized[$serial] = $serial;
        }
        $skipped += max(0, count($serials) - $skipped - count($normalized));
        foreach ($normalized as $serial) {
            try {
                $row = array_merge($base, ['serial' => $serial, 'created_at' => $now, 'updated_at' => $now]);
                $filtered = [];
                foreach ($row as $k => $v) {
                    if ($k === 'serial' || Schema::hasColumn('product_serials', $k)) {
                        $filtered[$k] = $v;
                    }
                }
                DB::table('product_serials')->insert($filtered);
                $added++;
            } catch (\Throwable) {
                $skipped++;
            }
        }

        return ['added' => $added, 'skipped' => $skipped];
    }

    protected function resultMessage(array $result, string $suffix): string
    {
        $message = ((int) $result['added']).' سریال '.$suffix.'.';
        if ((int) $result['skipped'] > 0) {
            $message .= ' '.((int) $result['skipped']).' مورد تکراری یا نامعتبر رد شد.';
        }

        return $message;
    }

    protected function withPrefix(string $serial, string $prefix): string
    {
        $serial = trim($serial);
        $prefix = trim($prefix);

        return $prefix !== '' && ! str_starts_with($serial, $prefix) ? $prefix.$serial : $serial;
    }
}
