@extends('layouts.admin')
@section('title', 'منوی سایت')
@section('content')
<style>
.sm{--bg:#070b18;--card:#12182a;--line:rgba(255,255,255,.08);--ink:#eef2ff;--muted:#9aa3b8;--accent:#7c5cff}
.sm h1{margin:0 0 .35rem;font-size:1.35rem}
.sm .lead{color:#64748b;margin:0 0 1rem;line-height:1.7}
.sm-preview{background:var(--bg);color:var(--ink);border-radius:16px;padding:1rem 1rem 1.2rem;margin-bottom:1rem}
.sm-bar{display:flex;gap:.35rem;align-items:center;flex-wrap:wrap}
.sm-brand{margin-left:auto;text-align:left}
.sm-brand b{display:block;font-size:.92rem}
.sm-brand span{display:block;color:var(--muted);font-size:.75rem}
.sm-item{position:relative}
.sm-item>a,.sm-link{color:#fff;text-decoration:none;font-weight:700;padding:.45rem .7rem;border-radius:10px;display:inline-block}
.sm-item:hover>a,.sm-item.is-open>a{background:rgba(124,92,255,.22)}
.sm-panel{display:none;position:absolute;top:100%;right:0;z-index:5;background:#101628;border:1px solid var(--line);border-radius:14px;padding:.75rem;min-width:220px;box-shadow:0 16px 40px rgba(0,0,0,.35)}
.sm-item:hover .sm-panel,.sm-item:focus-within .sm-panel{display:grid;gap:.7rem}
.sm-panel.cols{grid-template-columns:repeat(auto-fit,minmax(160px,1fr));min-width:min(640px,80vw)}
.sm-col b,.sm-col>a{display:block;color:#fff;font-weight:800;margin-bottom:.25rem;text-decoration:none}
.sm-col a{display:block;color:#c9d2ea;text-decoration:none;font-size:.86rem;padding:.18rem 0}
.sm-form{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem}
.sm-form h2{margin:0 0 .4rem;font-size:1rem}
.sm-row{display:grid;grid-template-columns:1.2fr 1fr auto;gap:.45rem;margin-bottom:.45rem}
.sm-row input{border:1px solid #dbe3ef;border-radius:10px;padding:.55rem .7rem;font:inherit}
.sm-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.8rem}
@media(max-width:700px){.sm-row{grid-template-columns:1fr}.sm-brand{margin-left:0}}
</style>
<div class="sm">
  <h1>منوی سایت</h1>
  <p class="lead">همان چیدمان منوی بالای سایت: صفحه اصلی، طراحی سایت، نرم‌افزارها، هوش مصنوعی و بقیه. زیرمنوها از صفحات طراحی، نرم‌افزار و فروش سایت ساخته می‌شوند.</p>
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  <div class="sm-preview" aria-label="پیش‌نمایش منوی سایت">
    <div class="sm-bar">
      @foreach($items as $item)
        @php $kids = $item['children'] ?? []; @endphp
        @if(count($kids))
          <div class="sm-item">
            <a href="{{ url($item['url'] ?? '/') }}">{{ $item['label'] }} ▾</a>
            @php
              $nested = false;
              foreach ($kids as $col) { if (!empty($col['children'])) { $nested = true; break; } }
            @endphp
            <div class="sm-panel {{ $nested ? 'cols' : '' }}">
              @if($nested)
                @foreach($kids as $col)
                  <div class="sm-col">
                    <a href="{{ url($col['url'] ?? '/') }}"><b>{{ $col['label'] }}</b></a>
                    @foreach($col['children'] ?? [] as $leaf)
                      <a href="{{ url($leaf['url'] ?? '/') }}">{{ $leaf['label'] }}</a>
                    @endforeach
                  </div>
                @endforeach
              @else
                <div class="sm-col">
                  @foreach($kids as $leaf)
                    <a href="{{ url($leaf['url'] ?? '/') }}">{{ $leaf['label'] }}</a>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        @else
          <a class="sm-link" href="{{ url($item['url'] ?? '/') }}">{{ $item['label'] }}</a>
        @endif
      @endforeach
      <div class="sm-brand">
        <b>{{ $brand }}</b>
        <span>{{ $tagline }}</span>
      </div>
    </div>
  </div>
  <form class="sm-form" method="post" action="{{ url('/admin/mega-menu/settings') }}">
    @csrf
    <input type="hidden" name="site_menu" value="1">
    <h2>عنوان‌های اصلی منو</h2>
    <p class="lead">هر ردیف یک دکمه در نوار بالاست. اگر عنوان «طراحی سایت» باشد، زیرمنوی خدمات طراحی به آن وصل می‌شود.</p>
    <div id="sm-rows">
      @foreach($raw as $row)
        <div class="sm-row">
          <input name="nav_label[]" value="{{ $row['label'] }}" placeholder="عنوان" maxlength="80">
          <input name="nav_url[]" value="{{ $row['url'] }}" placeholder="/design" dir="ltr" maxlength="300">
          <button class="btn" type="button" onclick="this.parentElement.remove()">حذف</button>
        </div>
      @endforeach
    </div>
    <div class="sm-actions">
      <button class="btn" type="button" id="sm-add">ردیف جدید</button>
      <button class="btn btn-primary" type="submit">ذخیره منوی سایت</button>
      <a class="btn" href="{{ url('/') }}" target="_blank" rel="noopener">دیدن سایت</a>
    </div>
  </form>
</div>
<script>
document.getElementById('sm-add')?.addEventListener('click', function(){
  var row=document.createElement('div');
  row.className='sm-row';
  row.innerHTML='<input name="nav_label[]" placeholder="عنوان" maxlength="80"><input name="nav_url[]" placeholder="/" dir="ltr" maxlength="300"><button class="btn" type="button">حذف</button>';
  row.querySelector('button').addEventListener('click', function(){ row.remove(); });
  document.getElementById('sm-rows').appendChild(row);
});
</script>
@endsection
