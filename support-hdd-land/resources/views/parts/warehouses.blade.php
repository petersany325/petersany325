@extends('layouts.app')
@section('title', 'تعریف انبارها | '.shop_name())
@section('page_title', 'انبارهای چندگانه')
@section('window_title', 'تعریف و مدیریت انبار طبق استاندارد حسابداری')

@section('content')
@include('parts._nav', [
    'whTitle' => 'انبارهای چندگانه',
    'whSub' => 'هر کالا به یک انبار وصل است؛ رسید و حواله روی همان انبار سند می‌خورند',
])

<div class="split-2">
    <div class="panel">
        <h3 style="margin-top:0;">فهرست انبارها</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead><tr><th>کد</th><th>نام</th><th>محل</th><th>کالا</th><th>وضعیت</th><th></th></tr></thead>
                <tbody>
                @forelse($warehouses as $wh)
                    <tr>
                        <td dir="ltr">{{ $wh->code ?: '—' }}</td>
                        <td>
                            {{ $wh->name }}
                            @if($wh->is_default)<span class="badge">پیش‌فرض</span>@endif
                        </td>
                        <td>{{ $wh->location ?: '—' }}</td>
                        <td>{{ number_format($wh->parts_count) }}</td>
                        <td>{{ $wh->is_active ? 'فعال' : 'غیرفعال' }}</td>
                        <td>
                            <details>
                                <summary class="btn btn-ghost">ویرایش</summary>
                                <form method="POST" action="{{ route('warehouses.update', $wh) }}" style="margin-top:8px;">
                                    @csrf @method('PUT')
                                    <div class="form-grid">
                                        <div><label>کد</label><input type="text" name="code" value="{{ $wh->code }}"></div>
                                        <div><label>نام</label><input type="text" name="name" value="{{ $wh->name }}" required></div>
                                        <div class="full"><label>محل</label><input type="text" name="location" value="{{ $wh->location }}"></div>
                                        <div class="full"><label>یادداشت</label><input type="text" name="note" value="{{ $wh->note }}"></div>
                                        <div>
                                            @include('partials.toggle', ['name'=>'is_active','label'=>'فعال','checked'=>$wh->is_active])
                                        </div>
                                        <div>
                                            @include('partials.toggle', ['name'=>'is_default','label'=>'پیش‌فرض','checked'=>$wh->is_default])
                                        </div>
                                    </div>
                                    <div class="actions">
                                        <button class="btn btn-secondary" type="submit">ذخیره</button>
                                    </div>
                                </form>
                                @unless($wh->is_default)
                                <form method="POST" action="{{ route('warehouses.destroy', $wh) }}" data-confirm="این انبار حذف شود؟" style="margin-top:6px;">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger" type="submit">حذف</button>
                                </form>
                                @endunless
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">انباری نیست.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">انبار جدید</h3>
        <form method="POST" action="{{ route('warehouses.store') }}">
            @csrf
            <div class="form-grid" style="grid-template-columns:1fr;">
                <div><label>کد</label><input type="text" name="code" placeholder="WH-02"></div>
                <div><label>نام انبار</label><input type="text" name="name" required placeholder="مثلاً انبار برد / انبار قطعات مصرفی"></div>
                <div><label>محل فیزیکی</label><input type="text" name="location" placeholder="طبقه / قفسه"></div>
                <div><label>یادداشت</label><input type="text" name="note"></div>
                <div>@include('partials.toggle', ['name'=>'is_active','label'=>'فعال','checked'=>true])</div>
                <div>@include('partials.toggle', ['name'=>'is_default','label'=>'انبار پیش‌فرض','checked'=>false])</div>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">ثبت انبار</button>
            </div>
        </form>
    </div>
</div>
@endsection
