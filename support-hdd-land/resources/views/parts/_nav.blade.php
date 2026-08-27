@php
    $whTitle = $whTitle ?? 'انبار قطعات';
    $whSub = $whSub ?? 'سیستم انبارداری حسابداری تعمیرگاه';
    $whNav = [
        ['route' => 'parts.index', 'label' => 'میز انبار', 'mark' => 'م', 'tone' => 'teal'],
        ['route' => 'warehouses.index', 'label' => 'انبارها', 'mark' => 'چ', 'tone' => 'violet'],
        ['route' => 'parts.receipt', 'label' => 'رسید ورود', 'mark' => 'ر', 'tone' => 'blue'],
        ['route' => 'parts.issue', 'label' => 'حواله خروج', 'mark' => 'ح', 'tone' => 'rose'],
        ['route' => 'parts.movements', 'label' => 'کارتکس / گردش', 'mark' => 'ک', 'tone' => 'amber'],
        ['route' => 'parts.valuation', 'label' => 'ارزش موجودی', 'mark' => 'ا', 'tone' => 'green'],
        ['route' => 'parts.create', 'label' => 'کالای جدید', 'mark' => '+', 'tone' => 'slate'],
    ];
@endphp
<div class="wh-shell">
    <div class="wh-hero">
        <div>
            <div class="wh-eyebrow">{{ shop_name() }} · انبار حسابداری</div>
            <h2 class="wh-title">{{ $whTitle }}</h2>
            <p class="wh-sub">{{ $whSub }}</p>
        </div>
        @isset($whActions)
            <div class="wh-hero-actions">{!! $whActions !!}</div>
        @endisset
    </div>
    <nav class="wh-nav">
        @foreach($whNav as $item)
            <a href="{{ route($item['route']) }}"
               class="wh-nav-item tone-{{ $item['tone'] }} {{ request()->routeIs($item['route']) || (isset($item['match']) && request()->routeIs($item['match'])) ? 'is-on' : '' }}">
                <span class="wh-nav-mark">{{ $item['mark'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
