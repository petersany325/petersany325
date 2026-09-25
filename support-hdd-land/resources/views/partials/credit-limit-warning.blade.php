{{--
  اخطار سقف اعتبار نسیه — چشمک‌زن قرمز وقتی پر/تجاوز شده.
  vars: $customer (required), optional $openDebt, $extraRemain (مانده قبض جاری اگر جداست — معمولاً داخل open است)
--}}
@php
    /** @var \App\Models\Customer $customer */
    $openDebt = isset($openDebt) ? (int) $openDebt : $customer->openDebtTotal();
    $limit = $customer->hasCreditLimit() ? (int) $customer->credit_limit : null;
    $over = $limit !== null && $openDebt > $limit;
    $headroom = $limit === null ? null : max(0, $limit - $openDebt);
    $showAlways = $showAlways ?? true;
    $compact = ! empty($compact);
@endphp
@if($limit !== null && ($showAlways || $over))
    <div class="credit-limit-banner {{ $over ? 'credit-limit-banner--danger credit-limit-blink' : 'credit-limit-banner--warn' }}"
         role="alert"
         data-credit-limit-banner
         data-over="{{ $over ? '1' : '0' }}"
         data-limit="{{ $limit }}"
         data-open="{{ $openDebt }}">
        @if($over)
            <strong>⚠ سقف اعتبار پر شده — نسیه ممنوع</strong>
            <span>
                سقف {{ number_format($limit) }} تومان · مانده بدهی {{ number_format($openDebt) }} تومان
                @if($openDebt > $limit)
                    · {{ number_format($openDebt - $limit) }} تومان بیش از سقف
                @endif
            </span>
        @else
            <strong>سقف اعتبار نسیه</strong>
            <span>
                سقف {{ number_format($limit) }} تومان · مانده {{ number_format($openDebt) }} ·
                ظرفیت باقی‌مانده {{ number_format($headroom) }} تومان
            </span>
        @endif
        @unless($compact)
            <a href="{{ route('customers.credit-limits', ['q' => $customer->name]) }}" style="margin-right:auto;color:inherit;text-decoration:underline;font-weight:700;">تعریف سقف</a>
        @endunless
    </div>
@endif
