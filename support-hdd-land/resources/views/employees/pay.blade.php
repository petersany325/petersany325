@extends('layouts.app')
@section('title', 'تخصص، سود و حقوق | '.shop_name())
@section('page_title', 'تخصص، سود و حقوق تعمیرکار')
@section('window_title', 'درصد سود، حقوق و دستمزد — کارتابل کارمند')

@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
        <div>
            <h2 style="margin:0;">تخصص، درصد سود و حقوق</h2>
            <p class="lead" style="margin:6px 0 0;">همین‌جا درصد سود / کمیسیون و حقوق ماهانه تعمیرکاران (مثل تعمیرگاه شرکت) را تنظیم کنید.</p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a class="btn btn-secondary" href="{{ route('employees.index') }}">کارتابل کارمند</a>
            <a class="btn btn-ghost" href="{{ route('employees.create') }}">کارمند/تعمیرکار با ورود</a>
        </div>
    </div>
</div>

<div class="panel" style="margin-top:10px;">
    <h3 style="margin-top:0;">ثبت ردیف جدید (تعمیرگاه / تعمیرکار)</h3>
    <form method="POST" action="{{ route('employees.pay.store') }}" class="accept-form">
        @csrf
        <div class="accept-row accept-row-3">
            <div>
                <label>نام</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="مثال: تعمیرگاه شرکت">
            </div>
            <div>
                <label>موبایل (اختیاری)</label>
                <input type="text" name="phone" value="{{ old('phone') }}" dir="ltr" style="text-align:left;" placeholder="09…">
            </div>
            <div>
                <label>تخصص</label>
                <input type="text" name="specialty" value="{{ old('specialty') }}" placeholder="هارد، بازیابی…">
            </div>
        </div>
        <div class="accept-row accept-row-3" style="margin-top:8px;">
            <div>
                <label>درصد سود / کمیسیون %</label>
                <input type="number" name="commission_percent" min="0" max="100" value="{{ old('commission_percent', 0) }}">
            </div>
            <div>
                <label>حقوق / دستمزد ماهانه (تومان)</label>
                <input type="number" name="monthly_salary" min="0" step="1000" value="{{ old('monthly_salary', 0) }}" dir="ltr" style="text-align:left;">
            </div>
            <div style="display:flex;align-items:flex-end;">
                <button class="btn btn-primary" type="submit">افزودن</button>
            </div>
        </div>
    </form>
</div>

<div class="panel" style="margin-top:10px;">
    <h3 style="margin-top:0;">لیست تعمیرکاران</h3>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>نام</th>
                <th>کارتابل ورود</th>
                <th>تخصص</th>
                <th>درصد سود</th>
                <th>حقوق / دستمزد</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($technicians as $tech)
                <tr>
                    <td colspan="7" style="padding:0;border:0;">
                        <form method="POST" action="{{ route('employees.pay.update', $tech) }}" class="pay-row-form">
                            @csrf
                            @method('PUT')
                            <div class="pay-row">
                                <div class="pay-name">
                                    <strong>{{ $tech->name }}</strong>
                                    @if($tech->phone)
                                        <div class="muted" dir="ltr">{{ $tech->phone }}</div>
                                    @endif
                                </div>
                                <div>
                                    @if($tech->user)
                                        <span class="chip chip-sms">{{ $tech->user->name }}</span>
                                        <a class="btn btn-ghost" href="{{ route('employees.edit', $tech->user) }}">ویرایش ورود</a>
                                    @else
                                        <span class="chip">بدون ورود</span>
                                    @endif
                                </div>
                                <div>
                                    <input type="text" name="specialty" value="{{ old('specialty', $tech->specialty) }}" placeholder="تخصص">
                                </div>
                                <div>
                                    <input type="number" name="commission_percent" min="0" max="100" value="{{ old('commission_percent', (int) $tech->commission_percent) }}">
                                </div>
                                <div>
                                    <input type="number" name="monthly_salary" min="0" step="1000" value="{{ old('monthly_salary', (int) ($tech->monthly_salary ?? 0)) }}" dir="ltr" style="text-align:left;">
                                </div>
                                <div>
                                    @include('partials.toggle', [
                                        'name' => 'is_active',
                                        'label' => 'فعال',
                                        'checked' => (bool) old('is_active', $tech->is_active),
                                        'on' => 'فعال',
                                        'off' => 'خاموش',
                                    ])
                                </div>
                                <div>
                                    <button class="btn btn-primary" type="submit">ذخیره</button>
                                </div>
                            </div>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">هنوز تعمیرکاری ثبت نشده. از فرم بالا اضافه کنید.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
