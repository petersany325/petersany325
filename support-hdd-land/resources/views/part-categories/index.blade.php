@extends('layouts.app')
@section('title', 'گروه‌بندی کالا | '.shop_name())
@section('page_title', 'گروه‌بندی درختی اجناس و قطعات')
@section('content')
<div class="panel">
    <h2>گروه‌بندی درختی (نامحدود)</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('part-categories.store') }}" class="accept-row accept-row-4" style="align-items:end;margin-bottom:12px;">
        @csrf
        <div><label>نام گروه</label><input name="name" required></div>
        <div>
            <label>والد</label>
            <select name="parent_id"><option value="">— ریشه —</option>
                @foreach($options as $id => $lab)<option value="{{ $id }}">{{ $lab }}</option>@endforeach
            </select>
        </div>
        <div>
            <label>نوع</label>
            <select name="item_type">
                @foreach($itemTypes as $k => $lab)<option value="{{ $k }}">{{ $lab }}</option>@endforeach
            </select>
        </div>
        <div><button class="btn btn-primary" type="submit">افزودن گروه</button></div>
    </form>

    @php
        $render = function ($nodes, $depth = 0) use (&$render, $itemTypes) {
            foreach ($nodes as $node) {
                echo '<div style="padding:6px 8px;margin:4px 0;margin-right:'.($depth*16).'px;background:#f8fafc;border-radius:8px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">';
                echo '<strong>'.e($node->name).'</strong> <span class="muted">'.e($node->itemTypeLabel()).'</span>';
                if (!$node->is_active) echo ' <span class="pill pill-off">غیرفعال</span>';
                echo '<form method="POST" action="'.route('part-categories.destroy', $node).'" style="display:inline;" data-confirm="حذف/غیرفعال؟">'.csrf_field().method_field('DELETE').'<button class="btn btn-danger" type="submit">حذف</button></form>';
                echo '</div>';
                if ($node->children && $node->children->count()) $render($node->children, $depth+1);
            }
        };
        $render($roots);
    @endphp
</div>
@endsection
