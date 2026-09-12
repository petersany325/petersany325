@extends('layouts.app')
@section('title', 'تیپ قیمتی | '.shop_name())
@section('page_title', 'تیپ قیمتی مشتریان')
@section('content')
<div class="panel">
    <h2>تیپ قیمتی (عمومی / همکار / خاص …)</h2>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('price-tiers.store') }}" class="accept-row accept-row-3" style="align-items:end;">
        @csrf
        <div><label>نام</label><input name="name" required placeholder="مثلاً مشتری سازمانی"></div>
        <div><label>کد</label><input name="code" placeholder="org"></div>
        <div><button class="btn btn-primary" type="submit">افزودن</button></div>
    </form>
    <div class="table-wrap" style="margin-top:12px;">
        <table class="data">
            <thead><tr><th>نام</th><th>کد</th><th>پیش‌فرض</th><th>وضعیت</th></tr></thead>
            <tbody>
            @foreach($tiers as $t)
                <tr>
                    <td>{{ $t->name }}</td>
                    <td dir="ltr">{{ $t->code ?: '—' }}</td>
                    <td>{{ $t->is_default ? 'بله' : '—' }}</td>
                    <td>{{ $t->is_active ? 'فعال' : 'غیرفعال' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="muted">قیمت هر تیپ را از ویرایش کارت کالا تنظیم کنید.</p>
</div>
@endsection
