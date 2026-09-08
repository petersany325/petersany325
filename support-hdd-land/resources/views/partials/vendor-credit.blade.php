@php
    $hideContact = $hideContact ?? false;
    $compact = $compact ?? false;
@endphp
<div class="vendor-credit {{ $compact ? 'is-compact' : '' }}">
    <span class="vendor-credit-tagline">{{ vendor_tagline() }}</span>
    <span class="vendor-credit-brand">
        محصول
        <a href="{{ vendor_url() }}" target="_blank" rel="noopener">{{ vendor_name() }}</a>
        <span dir="ltr">(<a href="{{ vendor_url() }}" target="_blank" rel="noopener">{{ vendor_host() }}</a>)</span>
    </span>
    @unless($hideContact)
        <a class="vendor-credit-contact" href="{{ route('contact') }}">تماس با ما</a>
    @endunless
</div>
