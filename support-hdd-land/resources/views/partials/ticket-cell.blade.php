@php
    /** @var mixed $reception */
    $label = ticket_label($reception ?? null);
@endphp
@if($label !== '' && ! empty($reception?->id))
    <a href="{{ route('receptions.show', $reception) }}" dir="ltr">{{ $label }}</a>
@elseif($label !== '')
    <span dir="ltr">{{ $label }}</span>
@else
    —
@endif
