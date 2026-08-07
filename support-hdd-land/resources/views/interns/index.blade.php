@extends('layouts.app')
@section('title', 'کارتابل کارآموزان | سرزمین هارد')
@section('page_title', 'کارتابل کارآموزان')
@section('window_title', 'ثبت‌نام و دوره کارآموزی')

@section('content')
<div class="emp-cartable">
    <div class="emp-cartable-hero">
        <div>
            <h2>کارتابل کارآموزان</h2>
            <p class="lead">ثبت‌نام کارآموز و تأیید دوره با پیامک خوش‌آمدگویی</p>
        </div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a class="btn btn-primary" href="{{ route('interns.create') }}">کارآموز جدید</a>
            <a class="btn btn-ghost" href="{{ route('employees.create') }}">کارمند جدید</a>
            <a class="btn btn-ghost" href="{{ route('staff-sms.templates') }}">متن SMS</a>
        </div>
    </div>

    <div class="emp-stat-row">
        <div class="emp-stat"><span>کل</span><strong>{{ $stats['total'] }}</strong></div>
        <div class="emp-stat tone-ok"><span>در حال کارآموزی</span><strong>{{ $stats['active'] }}</strong></div>
        <div class="emp-stat tone-sms"><span>آینده</span><strong>{{ $stats['upcoming'] }}</strong></div>
        <div class="emp-stat"><span>پایان/غیرفعال</span><strong>{{ $stats['finished'] }}</strong></div>
    </div>

    <div class="emp-card-grid">
        @forelse($interns as $intern)
            <article class="emp-card {{ $intern->is_active ? '' : 'is-off' }}">
                <header class="emp-card-head">
                    <div class="emp-avatar">آ</div>
                    <div>
                        <strong>{{ $intern->name }}</strong>
                        <div class="emp-duty">{{ $intern->department ?: 'کارآموز' }}</div>
                    </div>
                    <span class="emp-status {{ $intern->isCurrent() ? 'on' : 'off' }}">{{ $intern->statusLabel() }}</span>
                </header>
                <div class="emp-card-body">
                    <div class="emp-phone" dir="ltr">{{ $intern->phone }}</div>
                    <div class="muted" style="font-size:11px;margin-top:4px;">
                        از {{ jalali_date($intern->start_date) }} تا {{ jalali_date($intern->end_date) }}
                    </div>
                    @if($intern->notes)
                        <div style="font-size:11px;margin-top:4px;">{{ \Illuminate\Support\Str::limit($intern->notes, 80) }}</div>
                    @endif
                </div>
                <footer class="emp-card-foot">
                    <a class="btn btn-secondary" href="{{ route('interns.edit', $intern) }}">ویرایش</a>
                    <form method="POST" action="{{ route('interns.welcome-sms', $intern) }}">
                        @csrf
                        <button class="btn btn-ghost" type="submit">SMS خوش‌آمد</button>
                    </form>
                    <form method="POST" action="{{ route('interns.destroy', $intern) }}" data-confirm="کارآموز «{{ $intern->name }}» حذف شود؟">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">حذف</button>
                    </form>
                </footer>
            </article>
        @empty
            <div class="panel" style="grid-column:1/-1;">
                <p class="lead">هنوز کارآموزی ثبت نشده.</p>
                <a class="btn btn-primary" href="{{ route('interns.create') }}">اولین کارآموز را ثبت کنید</a>
            </div>
        @endforelse
    </div>
    {{ $interns->links('partials.pagination') }}
</div>
@endsection
