<form method="GET" action="{{ $action }}" class="acc-period">
    @include('partials.jalali-date', ['name' => 'from', 'value' => $from ?? null])
    <span class="acc-period-sep">تا</span>
    @include('partials.jalali-date', ['name' => 'to', 'value' => $to ?? null])
    {!! $extra ?? '' !!}
    <button class="btn btn-sm btn-primary" type="submit">اعمال</button>
</form>
