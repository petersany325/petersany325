@extends('layouts.app')

@section('title', 'نمایندگان همکار | '.shop_name())
@section('page_title', 'نمایندگان / همکاران تعمیرگاهی')
@section('window_title', 'فهرست نمایندگانی که قبض ارجاع می‌دهند یا می‌گیرند')

@section('content')
<section class="panel">
    <form method="GET" class="accept-row accept-row-3" style="align-items:end;">
        <div>
            <label>جستجو</label>
            <input type="text" name="q" value="{{ $q }}" placeholder="نام / فروشگاه / موبایل / کد">
        </div>
        <div class="actions" style="margin:0;">
            <button class="btn btn-primary" type="submit">جستجو</button>
            <a class="btn btn-ghost" href="{{ route('partners.index') }}">پاک</a>
            <a class="btn btn-secondary" href="{{ route('partners.create') }}">نماینده جدید</a>
            <a class="btn btn-primary" href="{{ route('partners.intake') }}">پذیرش از نماینده</a>
        </div>
    </form>
</section>

<section class="panel" style="margin-top:12px;">
    <div class="table-wrap">
        <table class="compact-table">
            <thead>
            <tr>
                <th>نام</th>
                <th>فروشگاه</th>
                <th>موبایل</th>
                <th>کد</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($partners as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->shop_name ?: '—' }}</td>
                    <td dir="ltr">{{ $p->phone ?: '—' }}</td>
                    <td dir="ltr">{{ $p->code ?: '—' }}</td>
                    <td>{{ $p->is_active ? 'فعال' : 'غیرفعال' }}</td>
                    <td class="actions">
                        <a class="btn btn-ghost" href="{{ route('partners.edit', $p) }}">ویرایش</a>
                        <form method="POST" action="{{ route('partners.destroy', $p) }}" onsubmit="return confirm('حذف/غیرفعال شود؟');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">نماینده‌ای ثبت نشده. اول همکاران را اضافه کنید.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $partners->links('partials.pagination') }}
</section>
@endsection
