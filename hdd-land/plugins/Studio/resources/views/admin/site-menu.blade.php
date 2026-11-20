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
.sm-node{margin-bottom:.45rem}
.sm-row{display:grid;grid-template-columns:1.1fr .9fr auto;gap:.4rem;align-items:center}
.sm-row input{border:1px solid #dbe3ef;border-radius:10px;padding:.5rem .65rem;font:inherit;width:100%}
.sm-ops{display:flex;gap:.3rem;flex-wrap:wrap}
.sm-ops button,.sm-actions .btn{border:1px solid #dbe3ef;background:#f8fafc;border-radius:8px;padding:.4rem .55rem;font:inherit;font-weight:700;cursor:pointer}
.sm-kids{margin-right:1.15rem;margin-top:.35rem;border-right:2px solid #e2e8f0;padding-right:.55rem}
.sm-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.8rem}
@media(max-width:700px){.sm-row{grid-template-columns:1fr}.sm-brand{margin-left:0}}
</style>
<div class="sm">
  <h1>منوی سایت</h1>
  <p class="lead">نام هر منو را عوض کنید، منوی جدید یا زیرمنو بسازید، حذف کنید و ترتیب را بالا و پایین ببرید. بعد از ذخیره، همان فهرست روی سایت دیده می‌شود.</p>
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
    <input type="hidden" name="menu_json" id="menu_json" value="[]">
    <h2>ویرایش منو</h2>
    <div id="sm-tree">
      @include('studio::admin.site-menu-node', ['nodes' => $items, 'depth' => 0])
    </div>
    <div class="sm-actions">
      <button class="btn" type="button" id="sm-add">منوی جدید</button>
      <button class="btn btn-primary" type="submit">ذخیره منوی سایت</button>
      @if(!empty($custom))
        <button class="btn" type="submit" name="menu_reset" value="1">بازگشت به منوی پیش‌فرض</button>
      @endif
      <a class="btn" href="{{ url('/') }}" target="_blank" rel="noopener">دیدن سایت</a>
    </div>
  </form>
</div>
<template id="sm-tpl">
  <div class="sm-node" data-depth="0">
    <div class="sm-row">
      <input class="js-label" value="" maxlength="80" placeholder="نام منو">
      <input class="js-url" value="/" maxlength="300" dir="ltr" placeholder="/path">
      <div class="sm-ops">
        <button type="button" data-act="up">بالا</button>
        <button type="button" data-act="down">پایین</button>
        <button type="button" data-act="child">زیرمنو</button>
        <button type="button" data-act="del">حذف</button>
      </div>
    </div>
    <div class="sm-kids"></div>
  </div>
</template>
<script>
(function(){
  var tree=document.getElementById('sm-tree');
  var form=tree?.closest('form');
  function node(depth){
    var el=document.getElementById('sm-tpl').content.firstElementChild.cloneNode(true);
    el.dataset.depth=String(depth);
    if(depth>=2){
      var child=el.querySelector('[data-act="child"]');
      if(child) child.remove();
    }
    return el;
  }
  tree?.addEventListener('click', function(ev){
    var btn=ev.target.closest('[data-act]');
    if(!btn) return;
    var item=btn.closest('.sm-node');
    var parent=item.parentElement;
    if(btn.dataset.act==='del'){ item.remove(); return; }
    if(btn.dataset.act==='up' && item.previousElementSibling){ parent.insertBefore(item, item.previousElementSibling); return; }
    if(btn.dataset.act==='down' && item.nextElementSibling){ parent.insertBefore(item.nextElementSibling, item); return; }
    if(btn.dataset.act==='child'){
      var depth=parseInt(item.dataset.depth||'0',10);
      if(depth>=2) return;
      item.querySelector(':scope > .sm-kids').appendChild(node(depth+1));
    }
  });
  document.getElementById('sm-add')?.addEventListener('click', function(){ tree.appendChild(node(0)); });
  function read(box){
    var out=[];
    box.querySelectorAll(':scope > .sm-node').forEach(function(item){
      var label=(item.querySelector(':scope > .sm-row .js-label')?.value||'').trim();
      if(!label) return;
      var url=(item.querySelector(':scope > .sm-row .js-url')?.value||'/').trim()||'/';
      var row={label:label, url:url};
      var kids=read(item.querySelector(':scope > .sm-kids'));
      if(kids.length) row.children=kids;
      out.push(row);
    });
    return out;
  }
  form?.addEventListener('submit', function(ev){
    if(ev.submitter && ev.submitter.name==='menu_reset') return;
    document.getElementById('menu_json').value=JSON.stringify(read(tree));
  });
})();
</script>
@endsection
