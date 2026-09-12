@php
    $ft = \App\Support\FooterConfig::get();
    $ftLinks1 = \App\Support\FooterConfig::links($ft['column1_links']);
    $ftLinks2 = \App\Support\FooterConfig::links($ft['column2_links']);
    $ftSocial = \App\Support\FooterConfig::links($ft['social_links']);
    $ftTrustItems = preg_split('/\R/u', $ft['trust_items']) ?: [];
    $phoneDigits = preg_replace('/\D+/', '', (string) ($ft['phone'] ?? ''));
    $phoneDisplay = $phoneDigits;
    if (preg_match('/^(\d{3})(\d{4})(\d{4})$/', $phoneDigits, $m)) {
        $phoneDisplay = $m[1].' '.$m[2].' '.$m[3];
    } elseif (preg_match('/^(\d{4})(\d{3})(\d{4})$/', $phoneDigits, $m)) {
        $phoneDisplay = $m[1].' '.$m[2].' '.$m[3];
    }
@endphp

@if (!empty($ft['enabled']))
<footer id="siteFooter" class="modern-footer" role="contentinfo" style="--ft-bg:{{ $ft['bg'] }};--ft-accent:{{ $ft['accent'] }};--ft-text:{{ $ft['text'] }};--ft-muted:{{ $ft['muted'] }}">
    <div class="container">
        @if (!empty($ft['show_newsletter']))
            <section class="ft-cta" aria-labelledby="footerNewsletterTitle">
                <div class="ft-cta-copy">
                    <span class="ft-eyebrow">خبرنامه</span>
                    <div class="ft-cta-text">
                        <h2 id="footerNewsletterTitle">{{ $ft['newsletter_title'] }}</h2>
                        <p>{{ $ft['newsletter_text'] }}</p>
                    </div>
                </div>
                <form class="ft-cta-form" action="{{ url('/contact') }}" method="get">
                    <label class="sr-only" for="ftEmail">ایمیل</label>
                    <input id="ftEmail" type="email" name="email" autocomplete="email" placeholder="ایمیل شما" required>
                    <button type="submit">عضویت</button>
                </form>
            </section>
        @endif

        <div class="ft-grid">
            <section class="ft-brand">
                <a href="{{ url('/') }}" class="ft-logo" aria-label="صفحه اصلی {{ $ft['brand'] }}"><b aria-hidden="true">H</b><strong>{{ $ft['brand'] }}</strong></a>
                <p>{{ $ft['description'] }}</p>
                <div class="ft-trust">
                    @foreach ($ftTrustItems as $trust)
                        @if (trim($trust) !== '')
                            <span>{{ trim($trust) }}</span>
                        @endif
                    @endforeach
                </div>
            </section>

            <nav aria-label="{{ $ft['column1_title'] }}">
                <h2>{{ $ft['column1_title'] }}</h2>
                @foreach ($ftLinks1 as $link)
                    <a href="{{ url($link['url']) }}"><span>{{ $link['label'] }}</span><i aria-hidden="true">←</i></a>
                @endforeach
            </nav>

            <nav aria-label="{{ $ft['column2_title'] }}">
                <h2>{{ $ft['column2_title'] }}</h2>
                @foreach ($ftLinks2 as $link)
                    <a href="{{ url($link['url']) }}"><span>{{ $link['label'] }}</span><i aria-hidden="true">←</i></a>
                @endforeach
                @if (!empty($ft['show_webapp']))
                    <a href="{{ url('/app') }}"><span>وب‌اپ فروشگاه</span><i aria-hidden="true">←</i></a>
                @endif
            </nav>

            <section class="ft-contact" aria-labelledby="footerContactTitle">
                <h2 id="footerContactTitle">ارتباط با ما</h2>
                <div class="ft-contact-list">
                    @if (!empty($ft['phone']))
                        <a class="ft-contact-row" href="tel:{{ $phoneDigits }}">
                            <span>تلفن</span>
                            <strong class="ft-phone" dir="ltr">{{ $phoneDisplay }}</strong>
                        </a>
                    @endif
                    @if (!empty($ft['email']))
                        <a class="ft-contact-row" href="mailto:{{ $ft['email'] }}">
                            <span>ایمیل</span>
                            <strong dir="ltr">{{ $ft['email'] }}</strong>
                        </a>
                    @endif
                    @if (!empty($ft['address']))
                        <p class="ft-contact-addr">{{ $ft['address'] }}</p>
                    @endif
                </div>
                <div class="ft-social">
                    @foreach ($ftSocial as $social)
                        <a href="{{ $social['url'] }}" aria-label="{{ $social['label'] }}" @if (str_starts_with($social['url'], 'http')) target="_blank" rel="noopener noreferrer" @endif>{{ mb_substr($social['label'], 0, 1) }}</a>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="ft-bottom">
            <span>© {{ now()->year }} {{ $ft['copyright'] }}</span>
            <div>
                <a href="{{ url('/contact') }}">تماس</a>
                <a href="{{ url('/about') }}">درباره ما</a>
                @if (!empty($ft['show_back_top']))
                    <a href="#top" aria-label="بازگشت به بالای صفحه">↑ بالا</a>
                @endif
            </div>
        </div>
    </div>
</footer>

<style>
.modern-footer{position:relative;overflow:hidden;margin-top:2.5rem;padding:1.6rem 0 .75rem;background:var(--ft-bg);color:var(--ft-text)}
.modern-footer::before{content:"";position:absolute;inset:auto auto -120px -140px;width:320px;height:320px;border-radius:50%;background:var(--ft-accent);filter:blur(120px);opacity:.1;pointer-events:none}
.ft-cta{position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;gap:.85rem 1.25rem;padding:.7rem .85rem;border-radius:14px;background:linear-gradient(120deg,var(--ft-accent),#ea580c);box-shadow:0 8px 22px rgba(0,0,0,.16);box-sizing:border-box}
.ft-cta-copy{display:flex;align-items:center;gap:.65rem;min-width:0;flex:1}
.ft-eyebrow{flex-shrink:0;display:inline-flex;align-items:center;padding:.18rem .5rem;border-radius:999px;background:rgba(255,255,255,.2);color:#fff;font-size:.62rem;font-weight:800;line-height:1.2}
.ft-cta-text{min-width:0}
.ft-cta h2{margin:0;color:#fff;font-size:.92rem;font-weight:800;line-height:1.35}
.ft-cta p{margin:.12rem 0 0;color:rgba(255,255,255,.9);font-size:.72rem;line-height:1.45}
.ft-cta-form{display:flex;align-items:center;flex:0 1 280px;min-width:min(260px,100%);padding:3px;border-radius:10px;background:#fff}
.ft-cta-form input{flex:1;min-width:0;padding:.45rem .6rem;border:0;outline:0;background:transparent;font-size:.8rem}
.ft-cta-form button{padding:.45rem .8rem;border:0;border-radius:8px;background:#0f172a;color:#fff;font-size:.76rem;font-weight:800;cursor:pointer;white-space:nowrap}
.ft-grid{position:relative;display:grid;grid-template-columns:1.45fr 1fr 1fr 1.1fr;gap:clamp(1.1rem,2.5vw,2.2rem);padding:1.35rem 0 1.6rem}
.ft-grid h2{margin:0 0 .75rem;font-size:.82rem;font-weight:800}
.ft-logo{display:inline-flex;align-items:center;gap:.55rem;color:inherit;text-decoration:none;font-size:1rem}
.ft-logo b{display:grid;width:34px;height:34px;place-items:center;border-radius:10px;background:linear-gradient(135deg,var(--ft-accent),#ea580c);font-size:.85rem}
.ft-brand p{margin:.65rem 0 .8rem;color:var(--ft-muted);font-size:.78rem;line-height:1.75}
.ft-trust{display:flex;flex-wrap:wrap;gap:.35rem}
.ft-trust span{padding:.22rem .5rem;border:1px solid rgba(255,255,255,.12);border-radius:999px;color:#cbd5e1;font-size:.66rem}
.ft-grid nav{display:flex;flex-direction:column;gap:.55rem}
.ft-grid nav a{display:flex;justify-content:space-between;gap:.45rem;color:var(--ft-muted);font-size:.8rem;text-decoration:none;transition:.18s}
.ft-grid nav a i{font-style:normal;opacity:.7}
.ft-grid nav a:hover,.ft-grid nav a:focus-visible{color:#fff}
.modern-footer a:focus-visible,.modern-footer button:focus-visible,.modern-footer input:focus-visible{outline:2px solid #fff;outline-offset:2px}
.ft-contact-list{display:grid;gap:.55rem}
.ft-contact-row{display:flex;flex-direction:column;align-items:flex-start;gap:.1rem;color:inherit;text-decoration:none}
.ft-contact-row span{color:var(--ft-muted);font-size:.66rem;line-height:1.2}
.ft-contact-row strong{display:block;width:100%;font-size:.84rem;font-weight:800;line-height:1.4;letter-spacing:.01em;text-align:start}
.ft-phone,.ft-contact-row strong[dir="ltr"]{direction:ltr;unicode-bidi:isolate;font-variant-numeric:tabular-nums;letter-spacing:.04em;text-align:left}
.ft-contact-addr{margin:.15rem 0 0;color:var(--ft-muted);font-size:.76rem;line-height:1.7}
.ft-social{display:flex;gap:.4rem;margin-top:.85rem}
.ft-social a{display:grid;width:32px;height:32px;place-items:center;border:1px solid rgba(255,255,255,.1);border-radius:9px;background:rgba(255,255,255,.06);color:#fff;font-size:.75rem;font-weight:800;text-decoration:none}
.ft-social a:hover{background:var(--ft-accent)}
.ft-bottom{display:flex;justify-content:space-between;align-items:center;gap:.85rem;padding:.85rem 0;border-top:1px solid rgba(255,255,255,.1);color:var(--ft-muted);font-size:.72rem}
.ft-bottom div{display:flex;gap:.9rem}
.ft-bottom a{color:inherit;text-decoration:none}
.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
@media (max-width:900px){
  .ft-grid{grid-template-columns:1fr 1fr}
  .ft-cta{align-items:stretch;flex-direction:column}
  .ft-cta-form{flex:1 1 auto;min-width:0;width:100%}
}
@media (max-width:560px){
  .modern-footer{margin-top:1.75rem;padding:1.25rem 0 .65rem}
  .ft-cta{padding:.75rem;border-radius:12px}
  .ft-cta-copy{align-items:flex-start}
  .ft-cta h2{font-size:.88rem}
  .ft-grid{grid-template-columns:1fr;padding-top:1.1rem}
  .ft-bottom{flex-direction:column;align-items:flex-start}
  .ft-cta-form{flex-direction:column;background:transparent;padding:0;gap:.4rem}
  .ft-cta-form input,.ft-cta-form button{width:100%;border-radius:9px}
  .ft-cta-form input{background:#fff;padding:.55rem .7rem}
  .ft-cta-form button{background:#0f172a}
}
</style>
@endif
