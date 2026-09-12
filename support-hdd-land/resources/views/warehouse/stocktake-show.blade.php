@extends('layouts.app')
@section('title', 'شمارش '.$stocktake->doc_no.' | '.shop_name())
@section('page_title', 'سند انبارگردانی '.$stocktake->doc_no)
@section('content')
<div class="panel">
    <div class="actions" style="margin:0 0 10px;">
        <a class="btn btn-ghost" href="{{ route('stocktakes.index') }}">بازگشت</a>
        @if($stocktake->status === 'draft')
            <form method="POST" action="{{ route('stocktakes.post', $stocktake) }}" style="display:inline;" data-confirm="موجودی‌ها بر اساس شمارش تعدیل شود؟">
                @csrf
                <button class="btn btn-primary" type="submit">ثبت قطعی انبارگردانی</button>
            </form>
        @endif
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($stocktake->status === 'draft')
    <form method="POST" action="{{ route('stocktakes.update', $stocktake) }}">
        @csrf
        @method('PUT')
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>کالا</th><th>سیستم</th><th>شمارش</th><th>اختلاف</th><th>یادداشت</th></tr></thead>
                <tbody>
                @foreach($stocktake->lines as $i => $line)
                    <tr>
                        <td>{{ $line->part?->name }}</td>
                        <td>{{ $line->system_qty }}</td>
                        <td>
                            <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $line->id }}">
                            <input type="number" name="lines[{{ $i }}][counted_qty]" min="0" value="{{ $line->counted_qty }}" style="width:90px">
                        </td>
                        <td>{{ $line->diff_qty }}</td>
                        <td><input type="text" name="lines[{{ $i }}][note]" value="{{ $line->note }}"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="actions"><button class="btn btn-secondary" type="submit">ذخیره شمارش</button></div>
    </form>
    @else
        <p class="lead">این سند ثبت قطعی شده است.</p>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>کالا</th><th>سیستم</th><th>شمارش</th><th>اختلاف</th></tr></thead>
                <tbody>
                @foreach($stocktake->lines as $line)
                    <tr>
                        <td>{{ $line->part?->name }}</td>
                        <td>{{ $line->system_qty }}</td>
                        <td>{{ $line->counted_qty }}</td>
                        <td>{{ $line->diff_qty }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
