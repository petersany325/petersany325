@php
    $label = '';
    if (! empty($reception)) {
        $label = method_exists($reception, 'ticketLabel')
            ? (string) $reception->ticketLabel()
            : trim((string) (($reception->ticket_no ?? '') ?: ($reception->receipt_no ?? '')));
    }
@endphp
@if($label !== '' && ! empty($reception->id))
    <a href="{{ route('receptions.show', $reception) }}" dir="ltr">{{ $label }}</a>
@elseif($label !== '')
    <span dir="ltr">{{ $label }}</span>
@else
    —
@endif
