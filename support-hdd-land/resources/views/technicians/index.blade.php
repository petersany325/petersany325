@extends('layouts.app')
@section('title', 'تعمیرکاران | سرزمین هارد')
@section('page_title', 'ثبت کارمندان تعمیرگاه')
@section('content')
<div class="panel">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center;">
        <div><h2>تعمیرکاران</h2><p class="lead">ثبت و مدیریت کارکنان فنی</p></div>
        <a class="btn btn-primary" href="{{ route('technicians.create') }}">تعمیرکار جدید</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>نام</th><th>تلفن</th><th>تخصص</th><th>کمیسیون</th><th>وضعیت</th><th>عملیات</th></tr></thead>
            <tbody>
            @forelse($technicians as $tech)
                <tr>
                    <td>{{ $tech->name }}</td>
                    <td>{{ $tech->phone ?: '—' }}</td>
                    <td>{{ $tech->specialty ?: '—' }}</td>
                    <td>{{ $tech->commission_percent }}٪</td>
                    <td>{{ $tech->is_active ? 'فعال' : 'غیرفعال' }}</td>
                    <td style="white-space:nowrap;">
                        <a class="btn btn-ghost" href="{{ route('technicians.edit', $tech) }}">ویرایش</a>
                        <form method="POST"
                              action="{{ route('technicians.destroy', $tech) }}"
                              style="display:inline;"
                              data-confirm="تعمیرکار «{{ $tech->name }}» حذف شود؟ این عمل برگشت‌ناپذیر است.">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">تعمیرکاری ثبت نشده.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $technicians->links('partials.pagination') }}
</div>
@endsection
