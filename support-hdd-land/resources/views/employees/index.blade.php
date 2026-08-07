@extends('layouts.app')
@section('title', 'کارتابل کارمندان | سرزمین هارد')
@section('page_title', 'کارتابل کارمندان')
@section('window_title', 'تنظیمات کارتابل کارمند — ورود موبایل و دسترسی')

@section('content')
<div class="emp-cartable">
    <div class="emp-cartable-hero">
        <div>
            <h2>کارتابل کارمندان</h2>
            <p class="lead">ورود با موبایل + تأیید SMS و دسترسی طبق وظیفه هر نفر</p>
        </div>
        <a class="btn btn-primary" href="{{ route('employees.create') }}">کارمند جدید</a>
    </div>

    <div class="emp-stat-row">
        <div class="emp-stat"><span>کل</span><strong>{{ $stats['total'] }}</strong></div>
        <div class="emp-stat tone-ok"><span>فعال</span><strong>{{ $stats['active'] }}</strong></div>
        <div class="emp-stat tone-sms"><span>ورود SMS</span><strong>{{ $stats['otp'] }}</strong></div>
        <div class="emp-stat tone-pass"><span>ورود رمز</span><strong>{{ $stats['password'] }}</strong></div>
    </div>

    <div class="emp-card-grid">
        @forelse($employees as $employee)
            @php $meta = \App\Support\Permissions::roleMeta($employee->role); @endphp
            <article class="emp-card tone-{{ $meta['tone'] }} {{ $employee->is_active ? '' : 'is-off' }}">
                <header class="emp-card-head"><meta charset="utf-8">
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
                </footer>
            </article>
        @empty
            <div class="panel" style="grid-column:1/-1;">
                <p class="lead">هنوز کارمندی ثبت نشده.</p>
                <a class="btn btn-primary" href="{{ route('employees.create') }}">اولین کارمند را بسازید</a>
            </div>
        @endforelse
    </div>

    {{ $employees->links('partials.pagination') }}
</div>
@endsection
