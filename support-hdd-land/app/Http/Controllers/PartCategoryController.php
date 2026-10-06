<?php

namespace App\Http\Controllers;

use App\Models\PartCategory;
use Illuminate\Http\Request;

class PartCategoryController extends Controller
{
    public function index()
    {
        $roots = PartCategory::query()
            ->with(['children.children'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('part-categories.index', [
            'roots' => $roots,
            'options' => PartCategory::optionsTree(),
            'itemTypes' => PartCategory::ITEM_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'exists:part_categories,id'],
            'item_type' => ['required', 'in:shop,repair,labor'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        PartCategory::create([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'item_type' => $data['item_type'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => true,
        ]);

        return back()->with('success', 'گروه ثبت شد.');
    }

    public function update(Request $request, PartCategory $partCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'exists:part_categories,id'],
            'item_type' => ['required', 'in:shop,repair,labor'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['parent_id']) && (int) $data['parent_id'] === (int) $partCategory->id) {
            return back()->withErrors(['parent_id' => 'گروه نمی‌تواند والد خودش باشد.']);
        }

        $partCategory->update([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
            'item_type' => $data['item_type'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'گروه ویرایش شد.');
    }

    public function destroy(PartCategory $partCategory)
    {
        if ($partCategory->children()->exists() || $partCategory->parts()->exists()) {
            $partCategory->update(['is_active' => false]);

            return back()->with('success', 'گروه دارای زیرمجموعه/کالاست؛ غیرفعال شد.');
        }
        $partCategory->delete();

        return back()->with('success', 'گروه حذف شد.');
    }
}
