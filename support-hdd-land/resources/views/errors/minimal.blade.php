<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'خطا') | سرزمین هارد</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=hd1" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            font-family: Vazirmatn, Tahoma, sans-serif;
            background: #e8eaee;
            color: #1f2933;
            padding: 24px;
            text-align: center;
        }
        .box {
            background: #fff;
            border: 1px solid #c9d0da;
            border-radius: 10px;
            padding: 28px 24px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 8px 24px rgba(31, 41, 51, .08);
        }
        .code {
            font-size: 42px;
            font-weight: 800;
            color: #3a4454;
            margin: 0 0 8px;
        }
        p { margin: 0 0 18px; color: #5f6b7a; line-height: 1.7; }
        a {
            display: inline-block;
            background: #3a4454;
            color: #fff;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="code">@yield('code')</div>
        <p>@yield('message')</p>
        @yield('action')
    </div>
</body>
</html>
