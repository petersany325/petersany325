<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'نصب')</title>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <style>
    :root { --ink:#0a1628; --brass:#b8956c; --paper:#f3f5f8; --danger:#8a2a2a; --ok:#1f6b4a; }
    *{box-sizing:border-box} body{margin:0;font-family:Vazirmatn,Tahoma,sans-serif;background:linear-gradient(180deg,#f3f5f8,#e6ebf1);color:var(--ink);min-height:100vh}
    .wrap{max-width:720px;margin:0 auto;padding:2.5rem 1.25rem}
    .card{background:#fff;padding:1.75rem;border:1px solid rgba(10,22,40,.08)}
    h1{margin:0 0 0.5rem;font-size:1.6rem} p.lead{color:#3d4f66;margin-top:0}
    label{display:grid;gap:.35rem;font-size:.9rem;font-weight:600;margin-bottom:1rem}
    input{font:inherit;padding:.8rem .9rem;border:1px solid rgba(10,22,40,.12);background:#f3f5f8}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:.9rem 1.4rem;background:var(--brass);color:var(--ink);border:0;font:inherit;font-weight:700;cursor:pointer}
    .alert{padding:.85rem 1rem;margin-bottom:1rem;font-weight:600}
    .alert-error{background:rgba(160,50,50,.1);color:var(--danger)}
    .alert-ok{background:rgba(46,125,90,.12);color:var(--ok)}
    .req{list-style:none;padding:0;margin:0 0 1.5rem}
    .req li{display:flex;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid rgba(10,22,40,.08);font-size:.95rem}
    .ok{color:var(--ok);font-weight:700} .bad{color:var(--danger);font-weight:700}
    .creds{background:#0a1628;color:#fff;padding:1.25rem;margin:1rem 0}
    .creds code{color:#d4b896}
    @media(max-width:640px){.row{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <div class="wrap">
    @yield('content')
  </div>
</body>
</html>
