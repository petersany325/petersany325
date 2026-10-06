@extends('layouts.app')
@section('title', 'مدیریت بانک | '.shop_name())
@section('page_title', 'تعریف و مدیریت حساب بانکی')
@section('window_title', 'حساب‌های بانکی / صندوق')

@section('content')
<div class="panel" style="margin-bottom:12px;">
    <h2 style="margin-top:0;">مدیریت بانک</h2>
    <p class="lead" style="margin:0;">تعریف حساب‌های صندوق، کارتخوان و کارت‌به‌کارت برای اتصال به خزانه و هزینه‌های ثابت.</p>
</div>

<div class="split-2">
    <div class="panel">
        <h3 style="margin-top:0;">فهرست حساب‌ها</h3>
        <div class="table-wrap">
            <table class="compact-table">
                <thead>
                <tr>
                    <th>نام</th>
                    <th>بانک</th>
                    <th>نوع</th>
                    <th>شبا / کارت</th>
                    <th>حساب کل</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($banks as $bank)
                    <tr>
                        <td>
                            {{ $bank->name }}
                            @if($bank->is_default)<span class="badge">پیش‌فرض</span>@endif
                        </td>
                        <td>{{ $bank->bank_name ?: '—' }}</td>
                        <td>{{ $bank->typeLabel() }}</td>
                        <td dir="ltr" style="text-align:left;">
                            @if($bank->iban)<div>{{ $bank->iban }}</div>@endif
                            @if($bank->card_number)<div>{{ $bank->card_number }}</div>@endif
                            @if($bank->account_number)<div>{{ $bank->account_number }}</div>@endif
                            @unless($bank->iban || $bank->card_number || $bank->account_number)—@endunless
                        </td>
                        <td dir="ltr">{{ $bank->resolvedGlCode() }}</td>
                        <td>{{ $bank->is_active ? 'فعال' : 'غیرفعال' }}</td>
                        <td>
                            <details>
                                <summary class="btn btn-ghost">ویرایش</summary>
                                <form method="POST" action="{{ route('accounting.banks.update', $bank) }}" style="margin-top:8px;">
                                    @csrf @method('PUT')
                                    @include('accounting._bank-form-fields', ['bank' => $bank])
                                    <div class="actions">
                                        <button class="btn btn-secondary" type="submit">ذخیره</button>
                                    </div>
                                </form>
                                <form method="POST" action="{{ route('accounting.banks.destroy', $bank) }}" data-confirm="این حساب حذف شود؟" style="margin-top:6px;">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger" type="submit">حذف</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">هنوز حساب بانکی تعریف نشده.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">حساب جدید</h3>
        <form method="POST" action="{{ route('accounting.banks.store') }}">
            @csrf
            @include('accounting._bank-form-fields', ['bank' => null])
            <div class="actions">
                <button class="btn btn-primary" type="submit">ثبت حساب</button>
            </div>
        </form>
    </div>
</div>
@endsection
