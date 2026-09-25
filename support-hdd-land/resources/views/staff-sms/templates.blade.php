@extends('layouts.app')
@section('title', 'متن SMS خوش‌آمد | '.shop_name())
@section('page_title', 'متن پیامک کارمند و کارآموز')
@section('window_title', 'قالب پیامک خوش‌آمدگویی')

@section('content')
<div class="panel">
    <div class="emp-cartable-hero" style="margin-bottom:10px;">
        <div>
            <h2 style="margin:0;font-size:15px;">متن SMS خوش‌آمدگویی</h2>
            <p class="lead" style="margin:2px 0 0;">متن پیامک کارمند جدید و کارآموز جدید را اینجا ویرایش کنید.</p>
        </div>
        <div style="display:flex;gap:6px;">
            <a class="btn btn-ghost" href="{{ route('employees.create') }}">کارمند جدید</a>
            <a class="btn btn-ghost" href="{{ route('interns.create') }}">کارآموز جدید</a>
        </div>
    </div>

    <form method="POST" action="{{ route('staff-sms.templates.save') }}" class="stack">
        @csrf
        <section class="emp-section">
            <div class="emp-section-head">
                <h3>پیامک کارمند جدید</h3>
                <p>جایگاه‌ها: {name} {shop} {role} {phone} {login_url}</p>
            </div>
            <textarea name="employee_template" rows="8" required style="font-family:ui-monospace,Menlo,Consolas,monospace;direction:rtl;">{{ old('employee_template', $employeeTemplate) }}</textarea>
            @error('employee_template')<div class="alert alert-error">{{ $message }}</div>@enderror
            <details style="margin-top:6px;">
                <summary class="muted" style="cursor:pointer;font-size:11px;">متن پیش‌فرض</summary>
                <pre style="white-space:pre-wrap;font-size:11px;background:#f7f8fa;padding:8px;border:1px solid #d9dee6;">{{ $employeeDefault }}</pre>
            </details>
        </section>

        <section class="emp-section">
            <div class="emp-section-head">
                <h3>پیامک کارآموز جدید</h3>
                <p>جایگاه‌ها: {name} {shop} {phone} {start_date} {end_date} {notes} — تاریخ‌ها شمسی ارسال می‌شوند</p>
            </div>
            <textarea name="intern_template" rows="8" required style="font-family:ui-monospace,Menlo,Consolas,monospace;direction:rtl;">{{ old('intern_template', $internTemplate) }}</textarea>
            @error('intern_template')<div class="alert alert-error">{{ $message }}</div>@enderror
            <details style="margin-top:6px;">
                <summary class="muted" style="cursor:pointer;font-size:11px;">متن پیش‌فرض</summary>
                <pre style="white-space:pre-wrap;font-size:11px;background:#f7f8fa;padding:8px;border:1px solid #d9dee6;">{{ $internDefault }}</pre>
            </details>
        </section>

        <div class="actions">
            <button class="btn btn-primary" type="submit">ذخیره متن‌ها</button>
        </div>
    </form>
</div>
@endsection
