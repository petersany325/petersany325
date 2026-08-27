@extends('layouts.app')
@section('title', 'سند دستی | سرزمین هارد')
@section('page_title', 'ثبت سند دستی')
@section('window_title', 'سند دستی')

@section('content')
@include('accounting._nav', [
    'accTitle' => 'سند دستی',
    'accSub' => 'ثبت دوطرفه آزاد — جمع بدهکار باید برابر بستانکار باشد',
])

<div class="acc-desk">
    <section class="acc-panel">
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('accounting.manual.store') }}" id="acc-manual-form">
            @csrf
            <div class="form-grid" style="grid-template-columns:2fr 1fr;">
                <div>
                    <label>شرح سند</label>
                    <input type="text" name="description" value="{{ old('description') }}" required>
                </div>
                <div>
                    <label>تاریخ</label>
                    @include('partials.jalali-date', ['name' => 'entry_date', 'value' => old('entry_date', now())])
                </div>
            </div>

            <div class="table-wrap" style="margin-top:12px;">
                <table class="compact-table acc-table" id="acc-manual-lines">
                    <thead>
                    <tr><th>حساب</th><th>بدهکار</th><th>بستانکار</th><th>یادداشت</th></tr>
                    </thead>
                    <tbody>
                    @for($i = 0; $i < 4; $i++)
                        <tr>
                            <td>
                                <select name="lines[{{ $i }}][account_id]" @if($i < 2) required @endif>
                                    <option value="">—</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}" @selected((string) old("lines.$i.account_id") === (string) $acc->id)>
                                            {{ $acc->code }} — {{ $acc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" min="0" name="lines[{{ $i }}][debit]" value="{{ old("lines.$i.debit", 0) }}"></td>
                            <td><input type="number" min="0" name="lines[{{ $i }}][credit]" value="{{ old("lines.$i.credit", 0) }}"></td>
                            <td><input type="text" name="lines[{{ $i }}][memo]" value="{{ old("lines.$i.memo") }}"></td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>
            <div class="actions" style="margin-top:10px;">
                <button class="btn btn-primary" type="submit">ثبت سند</button>
            </div>
        </form>
    </section>
</div>
@endsection
