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
<a class="p-ticket {{ !empty($highlightPay) ? 'is-pay' : '' }}" href="{{ route('portal.show', $ticket) }}">
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
    @if($ticket->status === 'ready')
        <div class="p-ticket-pay">
            <span>مانده: <b>{{ number_format($ticket->remainingAmount()) }}</b> تومان</span>
            <em>لینک پرداخت ←</em>
        </div>
    @endif
</a>
