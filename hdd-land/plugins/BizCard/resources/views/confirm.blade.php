<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>تأیید عضویت باشگاه مشتری</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/estedad-font@v7.0.0/dist/Estedad-Variable.css">
  <link rel="stylesheet" href="{{ asset('css/biz-card.css') }}?v=3">
  <style>:root{--bc-brand:{{ $s['accent'] ?? '#e23d12' }}}</style>
</head>
<body class="bc-body">
<div class="bc-shell">
  <div class="bc-confirm">
    <img class="bc-logo" src="{{ \Plugins\BizCard\src\CardConfig::assetUrl((string) ($s['logo'] ?? '/images/card/icon.png')) }}" alt="">
    @if(!empty($ok))
      <h1>عضویت تأیید شد</h1>
      <p>{{ $s['club_confirm_ok'] ?? 'عضویت شما در باشگاه مشتری سرزمین هارد تأیید شد.' }}</p>
    @else
      <h1>لینک معتبر نیست</h1>
      <p>این لینک تأیید پیدا نشد یا قبلاً استفاده شده است. دوباره از کارت ویزیت درخواست بدهید.</p>
    @endif
    <div class="bc-actions" style="padding:1rem 0 0;grid-template-columns:1fr">
      <a class="bc-btn bc-btn-p" href="{{ url('/card') }}">بازگشت به کارت ویزیت</a>
      <a class="bc-btn bc-btn-g" href="{{ url('/card/vcard') }}">ذخیره در گوشی</a>
    </div>
  </div>
</div>
</body>
</html>
