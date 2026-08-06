<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'پنل مدیریت')</title>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet" />
  <style>
    :root{--ink:#0a1628;--brass:#b8956c;--paper:#f3f5f8;--line:rgba(10,22,40,.1)}
    *{box-sizing:border-box} body{margin:0;font-family:Vazirmatn,Tahoma,sans-serif;background:var(--paper);color:var(--ink)}
    a{color:inherit;text-decoration:none}
    .shell{display:grid;grid-template-columns:240px 1fr;min-height:100vh}
    .side{background:var(--ink);color:#fff;padding:1.5rem 1rem}
    .side h1{font-size:1.05rem;margin:0 0 1.5rem;color:#d4b896}
    .side a{display:block;padding:.7rem .8rem;margin-bottom:.25rem;color:rgba(255,255,255,.8)}
    .side a.active,.side a:hover{background:rgba(255,255,255,.08);color:#fff}
    .main{padding:1.5rem}
    .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;gap:1rem;flex-wrap:wrap}
    .card{background:#fff;border:1px solid var(--line);padding:1.25rem;margin-bottom:1rem}
    .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}
    .stat{background:#fff;border:1px solid var(--line);padding:1rem}
    .stat b{display:block;font-size:1.6rem;margin-top:.35rem}
    table{width:100%;border-collapse:collapse;font-size:.95rem}
    th,td{padding:.7rem .4rem;border-bottom:1px solid var(--line);text-align:right;vertical-align:top}
    label{display:grid;gap:.35rem;font-size:.88rem;font-weight:600;margin-bottom:.9rem}
    input,textarea,select{font:inherit;padding:.7rem .8rem;border:1px solid var(--line);background:#f7f8fa;width:100%}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:.65rem 1rem;background:var(--brass);border:0;font:inherit;font-weight:700;cursor:pointer;color:var(--ink)}
    .btn-dark{background:var(--ink);color:#fff}
    .btn-danger{background:#8a2a2a;color:#fff}
    .alert{padding:.8rem 1rem;margin-bottom:1rem;background:rgba(46,125,90,.12);color:#1f6b4a;font-weight:600}
    .login-wrap{max-width:420px;margin:4rem auto;padding:0 1rem}
    @media(max-width:900px){.shell{grid-template-columns:1fr}.side{display:flex;gap:.5rem;overflow:auto;padding:1rem}.side h1{display:none}.stats{grid-template-columns:1fr}}
  </style>
</head>
<body>
@yield('body')
</body>
</html>
