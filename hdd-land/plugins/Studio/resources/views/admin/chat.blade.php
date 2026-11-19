@extends('layouts.admin')
@section('title', 'تنظیمات چت آنلاین')
@section('content')
@php
  $c = $chat ?? [];
  $f = fn ($k, $d = '') => old($k, $c[$k] ?? $d);
  $on = fn ($k) => (bool) old($k, $c[$k] ?? false);
  $topics = old('topics', $c['topics'] ?? []);
  if (! is_array($topics)) { $topics = []; }
  $linkText = function ($links): string {
      $lines = [];
      if (is_string($links)) { return $links; }
      foreach ((array) $links as $link) {
          if (is_array($link)) {
              $lines[] = trim((string) ($link['label'] ?? '')).'|'.trim((string) ($link['url'] ?? ''));
          }
      }
      return implode("\n", array_filter($lines, fn ($l) => $l !== '|'));
  };
  $keysText = function ($keys): string {
      if (is_string($keys)) { return $keys; }
      return implode('، ', array_map('strval', (array) $keys));
  };
  $suggestions = old('suggestions_text');
  if ($suggestions === null) {
      $suggestions = implode("\n", (array) ($c['suggestions'] ?? []));
  }
  $avatar = \App\Support\StudioChat::safeAvatar((string) ($c['avatar'] ?? 'images/chat/guide.jpg'));
  $corner = old('corner', $c['corner'] ?? 'right') === 'left' ? 'left' : 'right';
@endphp
<link rel="stylesheet" href="{{ asset('css/admin-toggles.css') }}?v=1">
<style>
.sc-adm{display:grid;gap:.85rem}
.sc-adm h1{margin:0;font-size:1.02rem;font-weight:600}
.sc-adm .lead{margin:.25rem 0 0;color:#64748b;font-size:.74rem;line-height:1.8}
.sc-grid{display:grid;grid-template-columns:1.4fr .8fr;gap:.85rem}
.sc-fields{display:grid;gap:.65rem}
.sc-fields label,.sc-topic label{display:grid;gap:.28rem}
.sc-fields input[type=text],.sc-fields textarea,.sc-topic input,.sc-topic textarea,.sc-topic select{width:100%;border:1px solid #e6eaf2;background:#f8fafc;padding:.5rem .7rem}
.sc-row{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}
.sc-corners{display:flex;gap:.4rem;flex-wrap:wrap}
.sc-corners label{display:flex;align-items:center;gap:.35rem;border:1px solid #e6eaf2;border-radius:999px;padding:.35rem .7rem;font-size:.74rem;font-weight:500;background:#fff}
.sc-corners input{accent-color:#7c5cff}
.sc-preview{position:relative;height:132px;border-radius:14px;background:#070b18;overflow:hidden}
.sc-preview span{position:absolute;bottom:12px;display:flex;align-items:center;gap:.4rem;background:#111827;color:#fff;border-radius:999px;padding:.22rem .65rem .22rem .22rem;font-size:.74rem}
.sc-preview.is-right span{right:12px}
.sc-preview.is-left span{left:12px}
.sc-preview img{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #7c5cff}
.sc-topic{border:1px solid #e6eaf2;border-radius:14px;padding:.75rem;margin:.55rem 0;background:#fff}
.sc-topic h3{margin:0 0 .55rem;font-size:.78rem;font-weight:600;display:flex;justify-content:space-between;align-items:center}
.sc-actions{display:flex;gap:.45rem;flex-wrap:wrap;position:sticky;bottom:.6rem;background:rgba(248,250,252,.94);padding:.6rem;border:1px solid #e6eaf2;border-radius:14px}
@media(max-width:900px){.sc-grid,.sc-row{grid-template-columns:1fr}}
</style>
<div class="sc-adm">
  <div>
    <h1>تنظیمات چت آنلاین</h1>
    <p class="lead">ظاهر دکمه، متن خوشامد، سؤال‌های آماده، اتصال به فروش و پشتیبانی، و جواب هر موضوع از اینجا عوض می‌شود. آیکون روی سایت پایین راست می‌نشیند.</p>
  </div>
  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  <form method="post" action="{{ url('/admin/studio-chat') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="topics_present" value="1">
    <div class="sc-grid">
      <div class="panel" style="padding:.9rem 1rem">
        <h2 style="margin:0 0 .7rem">نمایش روی سایت</h2>
        <div class="hl-tog">
          <div class="hl-tog__txt"><strong>چت آنلاین</strong><small>خاموش باشد، دکمه از همهٔ صفحه‌های استودیو برداشته می‌شود</small></div>
          <label class="hl-sw"><input type="checkbox" name="enabled" value="1" @checked($on('enabled'))><span class="hl-sw__track" data-on="روشن" data-off="خاموش"><i></i></span></label>
        </div>
        <div class="sc-fields" style="margin-top:.75rem">
          <div>
            <span style="font-size:.74rem;font-weight:500">گوشهٔ آیکون</span>
            <div class="sc-corners">
              <label><input type="radio" name="corner" value="right" @checked($corner==='right') data-corner> پایین راست</label>
              <label><input type="radio" name="corner" value="left" @checked($corner==='left') data-corner> پایین چپ</label>
            </div>
          </div>
          <div class="sc-row">
            <label>متن روی دکمه<input type="text" name="button" id="scButton" maxlength="24" value="{{ $f('button','گفتگو') }}"></label>
            <label>عنوان پنجره<input type="text" name="title" maxlength="60" value="{{ $f('title') }}"></label>
          </div>
          <div class="sc-row">
            <label>زیرعنوان<input type="text" name="subtitle" maxlength="80" value="{{ $f('subtitle') }}"></label>
            <label>متن جعبهٔ نوشتن<input type="text" name="placeholder" maxlength="80" value="{{ $f('placeholder') }}"></label>
          </div>
          <label>لوگوی چت
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp">
          </label>
          <p class="hint" style="margin:0">لوگوی فعلی همان ربات راهنماست. فایل تازه jpg، png یا webp است.</p>
        </div>
      </div>
      <div class="panel" style="padding:.9rem 1rem">
        <h2 style="margin:0 0 .7rem">پیش‌نمایش دکمه</h2>
        <div class="sc-preview {{ $corner==='left' ? 'is-left' : 'is-right' }}" id="scPreview">
          <span><img src="{{ asset($avatar) }}" alt=""><b id="scPreviewLabel">{{ $f('button','گفتگو') }}</b></span>
        </div>
        <p class="hint">روی موبایل، دکمه بالای نوار وب‌اپ می‌ماند تا روی منو ننشیند.</p>
      </div>
    </div>

    <div class="panel" style="padding:.9rem 1rem">
      <h2 style="margin:0 0 .7rem">شروع گفتگو</h2>
      <div class="sc-fields">
        <label>جملهٔ اول<input type="text" name="greeting" maxlength="400" value="{{ $f('greeting') }}"></label>
        <label>سؤال‌های آماده — هر خط یک سؤال<textarea name="suggestions_text" rows="4">{{ $suggestions }}</textarea></label>
        <label>وقتی جواب مطمئن نیست<textarea name="fallback" rows="3">{{ $f('fallback') }}</textarea></label>
      </div>
    </div>

    <div class="panel" style="padding:.9rem 1rem">
      <h2 style="margin:0 0 .7rem">اتصال به انسان</h2>
      <div class="hl-tog">
        <div class="hl-tog__txt"><strong>دکمهٔ فروش</strong><small>مشتری را به واتس‌اپ یا صفحهٔ تماس می‌برد</small></div>
        <label class="hl-sw"><input type="checkbox" name="show_sales" value="1" @checked($on('show_sales'))><span class="hl-sw__track" data-on="روشن" data-off="خاموش"><i></i></span></label>
      </div>
      <div class="hl-tog">
        <div class="hl-tog__txt"><strong>دکمهٔ پشتیبانی</strong><small>مشتری را به صفحهٔ پشتیبانی سایت می‌برد</small></div>
        <label class="hl-sw"><input type="checkbox" name="show_support" value="1" @checked($on('show_support'))><span class="hl-sw__track" data-on="روشن" data-off="خاموش"><i></i></span></label>
      </div>
      <div class="sc-fields" style="margin-top:.75rem">
        <div class="sc-row">
          <label>برچسب فروش<input type="text" name="sales_label" maxlength="40" value="{{ $f('sales_label') }}"></label>
          <label>لینک فروش<input type="text" name="sales_url" maxlength="300" value="{{ $f('sales_url') }}" dir="ltr"></label>
        </div>
        <div class="sc-row">
          <label>برچسب پشتیبانی<input type="text" name="support_label" maxlength="40" value="{{ $f('support_label') }}"></label>
          <label>لینک پشتیبانی<input type="text" name="support_url" maxlength="300" value="{{ $f('support_url') }}" dir="ltr"></label>
        </div>
      </div>
    </div>

    <div class="panel" style="padding:.9rem 1rem">
      <h2 style="margin:0 0 .35rem">جواب‌ها</h2>
      <p class="lead">هر موضوع با کلیدواژه شناخته می‌شود. لینک‌ها هر خط به شکل «عنوان|آدرس». قیمت را اینجا خالی بگذارید تا وقتی در صفحهٔ همان محصول ثبت شود.</p>
      <div id="scTopics">
        @foreach($topics as $i => $topic)
          @php
            $handoff = (string) (is_array($topic) ? ($topic['handoff'] ?? '') : '');
          @endphp
          <article class="sc-topic">
            <h3>موضوع <button type="button" class="btn btn-outline sc-del">حذف</button></h3>
            <div class="sc-fields">
              <label>کلیدواژه‌ها، با ویرگول<input type="text" name="topics[{{ $i }}][keys]" value="{{ $keysText(is_array($topic) ? ($topic['keys'] ?? '') : '') }}"></label>
              <label>جواب<textarea name="topics[{{ $i }}][text]" rows="3">{{ is_array($topic) ? ($topic['text'] ?? '') : '' }}</textarea></label>
              <label>لینک‌ها<textarea name="topics[{{ $i }}][links]" rows="3" dir="ltr">{{ $linkText(is_array($topic) ? ($topic['links'] ?? []) : []) }}</textarea></label>
              <label>بعد از جواب
                <select name="topics[{{ $i }}][handoff]">
                  <option value="" @selected($handoff==='')>فقط جواب</option>
                  <option value="sales" @selected($handoff==='sales')>وصل به فروش</option>
                  <option value="support" @selected($handoff==='support')>وصل به پشتیبانی</option>
                </select>
              </label>
            </div>
          </article>
        @endforeach
      </div>
      <button type="button" class="btn btn-outline" id="scAdd">موضوع جدید</button>
    </div>

    <div class="sc-actions">
      <button class="btn btn-primary" type="submit">ذخیره تنظیمات چت</button>
      <button class="btn btn-outline" type="submit" name="reset_chat" value="1" onclick="return confirm('تنظیمات چت به پیش‌فرض سایت برگردد؟')">بازگشت به پیش‌فرض</button>
      <a class="btn btn-outline" href="{{ url('/') }}" target="_blank" rel="noopener">دیدن سایت</a>
    </div>
  </form>
</div>
<template id="scTopicTpl">
  <article class="sc-topic">
    <h3>موضوع <button type="button" class="btn btn-outline sc-del">حذف</button></h3>
    <div class="sc-fields">
      <label>کلیدواژه‌ها، با ویرگول<input type="text" data-name="keys" value=""></label>
      <label>جواب<textarea data-name="text" rows="3"></textarea></label>
      <label>لینک‌ها<textarea data-name="links" rows="3" dir="ltr"></textarea></label>
      <label>بعد از جواب
        <select data-name="handoff">
          <option value="">فقط جواب</option>
          <option value="sales">وصل به فروش</option>
          <option value="support">وصل به پشتیبانی</option>
        </select>
      </label>
    </div>
  </article>
</template>
<script>
(function(){
  var box=document.getElementById('scTopics');
  var tpl=document.getElementById('scTopicTpl');
  function index(){
    box.querySelectorAll('.sc-topic').forEach(function(card,i){
      card.querySelectorAll('[name],[data-name]').forEach(function(el){
        var key=el.getAttribute('data-name') || (el.name.match(/\[(\w+)\]$/)||[])[1];
        if(key) el.name='topics['+i+']['+key+']';
      });
    });
  }
  box.addEventListener('click', function(e){
    var btn=e.target.closest('.sc-del');
    if(!btn) return;
    var card=btn.closest('.sc-topic');
    if(card && box.children.length>1){ card.remove(); index(); }
  });
  document.getElementById('scAdd').addEventListener('click', function(){
    if(box.children.length>=20) return;
    box.appendChild(tpl.content.cloneNode(true));
    index();
  });
  var label=document.getElementById('scButton');
  var preview=document.getElementById('scPreview');
  var previewLabel=document.getElementById('scPreviewLabel');
  label.addEventListener('input', function(){ previewLabel.textContent=label.value || 'گفتگو'; });
  document.querySelectorAll('[data-corner]').forEach(function(el){
    el.addEventListener('change', function(){
      preview.classList.toggle('is-left', el.value==='left' && el.checked);
      preview.classList.toggle('is-right', el.value==='right' && el.checked);
    });
  });
})();
</script>
@endsection
