@extends('layouts.app')
@section('title', 'ویرایش کارت کالا | '.shop_name())
@section('page_title', 'ویرایش کارت کالا')
@section('window_title', 'ویرایش شناسنامه انبار')

@section('content')
@include('parts._nav', [
    'whTitle' => 'ویرایش: '.$part->name,
    'whSub' => 'برای تغییر موجودی از کارتکس یا رسید/حواله استفاده کنید',
    'whActions' => '<a class="btn btn-ghost" href="'.route('parts.show', $part).'">کارتکس</a>',
])
<div class="panel" style="max-width:860px;">
    <form method="POST" action="{{ route('parts.update', $part) }}">
        @csrf @method('PUT')
        @include('parts._form', ['withStock' => false, 'categories' => $categories ?? [], 'itemTypes' => $itemTypes ?? \App\Models\Part::ITEM_TYPES])
        <div class="actions">
            <button class="btn btn-primary" type="submit">ذخیره کارت</button>
            <a class="btn btn-ghost" href="{{ route('parts.show', $part) }}">کارتکس</a>
        </div>
    </form>
</div>

@if(!empty($tiers) && $tiers->count())
<div class="panel" style="max-width:860px;margin-top:12px;">
    <h3 style="margin-top:0;">تیپ قیمتی</h3>
    <form method="POST" action="{{ route('parts.tier-prices', $part) }}" class="accept-row accept-row-3">
        @csrf
        @foreach($tiers as $tier)
            @php $p = $part->tierPrices->firstWhere('price_tier_id', $tier->id); @endphp
            <div>
                <label>{{ $tier->name }}</label>
                <input type="number" min="0" name="prices[{{ $tier->id }}]" value="{{ $p?->price ?? '' }}" placeholder="{{ $part->sale_price }}">
            </div>
        @endforeach
        <div class="actions" style="margin:0;"><button class="btn btn-secondary" type="submit">ذخیره تیپ قیمت</button></div>
    </form>
</div>
@endif
@endsection
