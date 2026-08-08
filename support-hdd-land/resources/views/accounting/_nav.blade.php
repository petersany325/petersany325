@php
    $accTitle = $accTitle ?? 'حسابداری';
    $accSub = $accSub ?? 'سیستم مالی دوطرفه تعمیرگاه';
    $accShowPeriod = $accShowPeriod ?? false;
    $accNav = [
        ['route' => 'accounting.index', 'label' => 'میز کار', 'icon' => '⌂', 'tone' => 'teal'],
        ['route' => 'accounting.journals', 'label' => 'اسناد', 'icon' => '☰', 'tone' => 'blue'],
        ['route' => 'accounting.accounts', 'label' => 'سرفصل‌ها', 'icon' => '▤', 'tone' => 'slate'],
        ['route' => 'accounting.ledger', 'label' => 'دفتر معین', 'icon' => '▦', 'tone' => 'amber'],
        ['route' => 'accounting.trial', 'label' => 'تراز', 'icon' => '⚖', 'tone' => 'green'],
        ['route' => 'accounting.receivables', 'label' => 'بدهکاران', 'icon' => '◎', 'tone' => 'rose'],
        ['route' => 'accounting.manual', 'label' => 'سند دستی', 'icon' => '+', 'tone' => 'violet'],
    ];
@endphp
<div class="acc-shell">
    <div class="acc-hero">
        <div>
            <div class="acc-hero-eyebrow">{{ shop_name() }} · حسابداری دوطرفه</div>
            <h2 class="acc-hero-title">{{ $accTitle }}</h2>
            <p class="acc-hero-sub">{{ $accSub }}</p>
        </div>
        <div class="acc-hero-actions">
            @if($accShowPeriod)
                <form method="GET" action="{{ route('accounting.index') }}" class="acc-period">
                    @include('partials.jalali-date', ['name' => 'from', 'value' => $from ?? jalali_period_range('this_month')[0]])
                    <span class="acc-period-sep">تا</span>
                    @include('partials.jalali-date', ['name' => 'to', 'value' => $to ?? now()])
                    <button class="btn btn-sm btn-primary" type="submit">اعمال</button>
                </form>
                @can('reports.accounting')
                <form method="POST" action="{{ route('accounting.rebuild') }}" onsubmit="return confirm('اسناد از قبض/پرداخت/قطعه بازسازی شوند؟')">
                    @csrf
                    <button class="btn btn-sm btn-ghost acc-rebuild" type="submit">بازسازی اسناد</button>
                </form>
                @endcan
            @elseif(isset($accActions))
                {!! $accActions !!}
            @endif
        </div>
    </div>
    <nav class="acc-nav">
        @foreach($accNav as $item)
            <a href="{{ route($item['route']) }}"
               class="acc-nav-item tone-{{ $item['tone'] }} {{ request()->routeIs($item['route']) ? 'is-on' : '' }}">
                <span class="acc-nav-ico">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
