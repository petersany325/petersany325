<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::query()
            ->withCount('parts')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('parts.warehouses', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $wh = Warehouse::create($data);
            if (! empty($data['is_default'])) {
                $wh->makeDefault();
            }
        });

        return back()->with('success', 'انبار ثبت شد.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $this->validated($request, $warehouse->id);
        DB::transaction(function () use ($data, $warehouse) {
            $warehouse->update($data);
            if (! empty($data['is_default'])) {
                $warehouse->makeDefault();
            }
        });

        return back()->with('success', 'انبار به‌روزرسانی شد.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->parts()->exists()) {
            return back()->withErrors(['warehouse' => 'این انبار کالا دارد؛ ابتدا کالاها را منتقل یا غیرفعال کنید.']);
        }
        if ($warehouse->is_default) {
            return back()->withErrors(['warehouse' => 'انبار پیش‌فرض را نمی‌توان حذف کرد.']);
        }
        $warehouse->delete();

        return back()->with('success', 'انبار حذف شد.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:30', Rule::unique('warehouses', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:180'],
            'note' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_default'] = $request->boolean('is_default', false);

        return $data;
    }
}
