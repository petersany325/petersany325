<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Install EK Electronics')</title>
  <style>
    body{font-family:Manrope,system-ui,sans-serif;background:#f5f7fb;margin:0;color:#0b1220}
    .wrap{max-width:640px;margin:40px auto;padding:0 16px}
    .card{background:#fff;border:1px solid #e4e8f0;border-radius:18px;padding:28px;box-shadow:0 18px 50px rgba(15,23,42,.08)}
    h1{margin:0 0 8px} .lead{color:#5b6475}
    label{display:grid;gap:6px;font-weight:700;margin:12px 0;font-size:.9rem}
    input{padding:11px 12px;border:1px solid #e4e8f0;border-radius:12px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .btn{background:#1d4ed8;color:#fff;border:0;border-radius:999px;padding:12px 18px;font-weight:750;cursor:pointer;margin-top:12px}
    .req{list-style:none;padding:0}.req li{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eef2f7}
    .ok{color:#059669;font-weight:700}.bad{color:#dc2626;font-weight:700}
    .alert{padding:12px;border-radius:12px;margin:12px 0}.alert-error{background:#fef2f2;color:#991b1b}
    .alert-ok{background:#ecfdf5;color:#065f46}
  </style>
</head>
<body>
  <div class="wrap">@yield('content')</div>
</body>
</html>
