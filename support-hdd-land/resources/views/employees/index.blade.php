@extends('layouts.app')
@section('title', 'کارتابل کارمندان | '.shop_name())
@section('page_title', 'کارتابل کارمندان')
@section('window_title', 'تنظیمات کارتابل کارمند — ورود موبایل و دسترسی')

@section('content')
<div class="emp-cartable">
    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="emp-cartable-hero">
        <div>
            <h2>کارتابل کارمندان</h2>
            <p class="lead">ورود با موبایل + تأیید SMS، دسترسی، تخصص و درصد سود / حقوق تعمیرکار</p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a class="btn btn-primary" href="{{ route('employees.create') }}">کارمند جدید</a>
            <a class="btn btn-secondary" href="{{ route('interns.create') }}">کارآموز جدید</a>
            <a class="btn btn-ghost" href="{{ route('interns.index') }}">کارتابل کارآموز</a>
            <a class="btn btn-ghost" href="{{ route('staff-sms.templates') }}">متن SMS</a>
        </div>
    </div>

    <div class="emp-stat-row">
        <div class="emp-stat"><span>کل</span><strong>{{ $stats['total'] }}</strong></div>
        <div class="emp-stat tone-ok"><span>فعال</span><strong>{{ $stats['active'] }}</strong></div>
        <div class="emp-stat tone-sms"><span>ورود SMS</span><strong>{{ $stats['otp'] }}</strong></div>
        <div class="emp-stat tone-pass"><span>ورود رمز</span><strong>{{ $stats['password'] }}</strong></div>
    </div>

    <div class="emp-card-grid">
        @forelse($employees as $employee)
            @php
                $meta = \App\Support\Permissions::roleMeta($employee->role);
                $tech = $employee->technician;
            @endphp
            <article class="emp-card tone-{{ $meta['tone'] }} {{ $employee->is_active ? '' : 'is-off' }}">
                <header class="emp-card-head">
                    <div class="emp-avatar">{{ $meta['mark'] }}</div>
                    <div>
                        <strong>{{ $employee->name }}</strong>
                        <div class="emp-duty">{{ $meta['label'] }}</div>
                    </div>
                    <span class="emp-status {{ $employee->is_active ? 'on' : 'off' }}">{{ $employee->is_active ? 'فعال' : 'غیرفعال' }}</span>
                </header>
                <div class="emp-card-body">
                    <div class="emp-phone" dir="ltr">{{ $employee->phone ?: '—' }}</div>
                    <div class="emp-login-chips">
                        @if($employee->can_login_otp)
                            <span class="chip chip-sms">موبایل / SMS</span>
                        @endif
                        @if($employee->can_login_password)
                            <span class="chip chip-pass">رمز</span>
                        @endif
                        @if(! $employee->can_login_otp && ! $employee->can_login_password)
                            <span class="chip">بدون ورود</span>
                        @endif
                    </div>
                    @if($tech)
                        <div class="emp-pay-box">
                            <div><span class="muted">تخصص</span><strong>{{ $tech->specialty ?: '—' }}</strong></div>
                            <div><span class="muted">درصد سود</span><strong>{{ (int) $tech->commission_percent }}٪</strong></div>
                            <div><span class="muted">حقوق / دستمزد</span><strong dir="ltr">{{ number_format((int) ($tech->monthly_salary ?? 0)) }}</strong></div>
                        </div>
                    @endif
                    <div class="emp-perm-chips">
                        @foreach(array_slice($employee->permissionList(), 0, 5) as $perm)
                            <span class="chip chip-soft">{{ \App\Support\Permissions::ALL[$perm] ?? $perm }}</span>
                        @endforeach
                        @if(count($employee->permissionList()) > 5)
                            <span class="chip chip-soft">+{{ count($employee->permissionList()) - 5 }}</span>
                        @endif
                    </div>
                </div>
                <footer class="emp-card-foot">
                    <a class="btn btn-secondary" href="{{ route('employees.edit', $employee) }}">ویرایش دسترسی</a>
                    <form method="POST" action="{{ route('employees.welcome-sms', $employee) }}">
                        @csrf
                        <button class="btn btn-ghost" type="submit" title="ارسال مجدد پیامک خوش‌آمدگویی">SMS خوش‌آمد</button>
                    </form>
                    @if((int) $employee->id !== (int) auth()->id())
                        <form method="POST"
                              action="{{ route('employees.destroy', $employee) }}"
                              data-confirm="کاربر «{{ $employee->name }}» حذف شود؟ این عمل برگشت‌ناپذیر است.">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">حذف کاربر</button>
                        </form>
                    @endif
                </footer>
            </article>
        @empty
            <div class="panel" style="grid-column:1/-1;">
                <p class="lead">هنوز کارمندی ثبت نشده.</p>
                <a class="btn btn-primary" href="{{ route('employees.create') }}">اولین کارمند را بسازید</a>
            </div>
        @endforelse
    </div>

    @if(($orphanTechnicians ?? collect())->isNotEmpty())
        <div class="panel" style="margin-top:14px;">
            <h3 style="margin-top:0;">تعمیرکاران بدون کارتابل ورود</h3>
            <p class="muted" style="margin-top:0;">این‌ها فقط در لیست تعمیرکار هستند. برای ادغام کامل، از کارتابل کارمند ثبت/اتصال کنید.</p>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>نام</th><th>تخصص</th><th>درصد سود</th><th>حقوق</th><th></th></tr></thead>
                    <tbody>
                    @foreach($orphanTechnicians as $tech)
                        <tr>
                            <td>{{ $tech->name }}</td>
                            <td>{{ $tech->specialty ?: '—' }}</td>
                            <td>{{ (int) $tech->commission_percent }}٪</td>
                            <td dir="ltr">{{ number_format((int) ($tech->monthly_salary ?? 0)) }}</td>
                            <td><a class="btn btn-ghost" href="{{ route('employees.create') }}">ثبت در کارتابل</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{ $employees->links('partials.pagination') }}
</div>
@endsection
