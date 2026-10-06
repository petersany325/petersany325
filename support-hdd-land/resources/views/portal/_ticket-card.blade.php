@php
    $statusTone = match($ticket->status) {
        'ready' => 'green',
        'repairing' => 'amber',
        'waiting_part' => 'rose',
        'delivered' => 'slate',
        'received' => 'blue',
        'unrepairable' => 'rose',
        default => 'teal',
    };
    $partsCount = (int) ($ticket->parts_count ?? $ticket->parts->count());
    $partsQty = $ticket->relationLoaded('parts')
        ? (int) $ticket->parts->sum('quantity')
        : $partsCount;
@endphp
@php
    $remain = $ticket->remainingAmount();
    $showDebt = !empty($highlightDebt) || ($remain > 0 && $ticket->status === 'delivered');
    $showPay = !empty($highlightPay) || $ticket->status === 'ready' || $showDebt;
@endphp
<a class="p-ticket {{ $showPay ? 'is-pay' : '' }} {{ $showDebt ? 'is-debt' : '' }}" href="{{ route('portal.show', $ticket) }}">
    <div class="p-ticket-top">
        <strong>{{ $ticket->ticket_no }}</strong>
        <span class="p-badge tone-{{ $statusTone }}">{{ $ticket->statusLabel() }}</span>
    </div>
    <div class="p-ticket-title">
        {{ $ticket->product_name ?: 'دستگاه' }}
        @if($ticket->brand) · {{ $ticket->brand }} @endif
        @if($ticket->model) {{ $ticket->model }} @endif
    </div>
    <div class="p-ticket-meta">
        <span>قطعه: {{ $partsCount }} ردیف@if($partsQty !== $partsCount) ({{ $partsQty }} عدد)@endif</span>
        <span>جمع: {{ number_format((int) $ticket->total_amount) }}</span>
    </div>
    @if($ticket->capacity_changed && $ticket->hdd_capacity_after)
        <div class="p-ticket-meta" style="color:#9a3412;">
            <span>تغییر فضا: {{ $ticket->hdd_capacity ?: '—' }} → {{ $ticket->hdd_capacity_after }}</span>
        </div>
    @endif
    @if($remain > 0 && ($ticket->status === 'ready' || $showDebt || !empty($highlightPay)))
        <div class="p-ticket-pay {{ $showDebt ? 'is-debt' : '' }}">
            <span>{{ $showDebt ? 'بدهی' : 'مانده' }}: <b>{{ number_format($remain) }}</b> تومان</span>
            <em>{{ $showDebt ? 'تسویه نسیه ←' : 'لینک پرداخت ←' }}</em>
        </div>
    @endif
</a>
