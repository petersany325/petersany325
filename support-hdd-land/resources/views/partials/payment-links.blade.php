@php
    $payLinks = $payLinks ?? \App\Support\PaymentGateways::active();
    $payTitle = $payTitle ?? 'پرداخت آنلاین';
@endphp
@if(count($payLinks))
<section class="pay-links {{ $compact ?? false ? 'is-compact' : '' }}">
    <header class="pay-links-head"><meta charset="utf-8">
        <strong>{{ $payTitle }}</strong>
        @if(!($compact ?? false))
            <span>انتخاب درگاه برای پرداخت مشتری</span>
        @endif
    </header>
    <div class="pay-links-grid">
        @foreach($payLinks as $link)
            <a class="pay-link-btn tone-{{ $link['tone'] }}"
               href="{{ $link['url'] }}"
               target="_blank"
               rel="noopener noreferrer"
               title="{{ $link['hint'] }}">
                <span class="pay-link-mark">{{ mb_substr($link['label'], 0, 1) }}</span>
                <span class="pay-link-text">
                    <strong>{{ $link['label'] }}</strong>
                    @if(!($compact ?? false))
                        <small>{{ $link['hint'] }}</small>
                    @endif
                </span>
                <span class="pay-link-go">↗</span>
            </a>
        @endforeach
    </div>
</section>
@endif
