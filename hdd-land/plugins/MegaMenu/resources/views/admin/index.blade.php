@extends('layouts.admin')
@section('title','مگامنو ساز حرفه‌ای')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<style>
.mm-studio{display:grid;grid-template-columns:minmax(320px,1.05fr) minmax(340px,1fr);gap:1rem;align-items:start}
@media(max-width:1100px){.mm-studio{grid-template-columns:1fr}}
.mm-head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:1rem;flex-wrap:wrap}
.mm-head h1{margin:0;font-size:1.45rem}
.mm-head p{margin:.35rem 0 0;color:var(--muted,#8b93a7)}
.mm-panel{background:var(--panel,#151922);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:1rem}
.mm-toolbar{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.85rem}
.mm-hint{font-size:.82rem;color:var(--muted,#8b93a7);line-height:1.6;margin:0 0 .85rem}
.mm-nest{list-style:none;margin:0;padding:0;min-height:12px}
.mm-nest .mm-nest{margin:.35rem 0 .35rem 1.1rem;padding-right:.55rem;border-right:2px dashed rgba(255,255,255,.08)}
.mm-item{margin:0 0 .4rem}
.mm-row{
  display:flex;align-items:center;gap:.55rem;padding:.55rem .7rem;
  background:#0d1017;border:1px solid rgba(255,255,255,.08);border-radius:10px;
  cursor:pointer;transition:border-color .15s,background .15s,box-shadow .15s
}
.mm-row:hover{border-color:rgba(226,61,18,.35)}
.mm-row.is-active{border-color:#e23d12;box-shadow:0 0 0 1px rgba(226,61,18,.25);background:#141018}
.mm-row.is-off{opacity:.55}
.mm-handle{cursor:grab;color:#6b7280;font-size:1rem;user-select:none;padding:.1rem .2rem;line-height:1}
.mm-handle:active{cursor:grabbing}
.mm-icon{width:1.4rem;text-align:center;flex-shrink:0}
.mm-title{font-weight:650;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mm-meta{display:flex;flex-wrap:wrap;gap:.3rem;align-items:center}
.mm-chip{
  font-size:.68rem;padding:.12rem .4rem;border-radius:999px;
  background:rgba(255,255,255,.06);color:#b7bfd0;border:1px solid rgba(255,255,255,.08)
}
.mm-chip.mega{background:rgba(226,61,18,.15);color:#ff9b82;border-color:rgba(226,61,18,.3)}
.mm-chip.badge{background:rgba(34,197,94,.12);color:#86efac}
.mm-actions{display:flex;gap:.25rem;flex-shrink:0}
.mm-actions button{
  border:1px solid rgba(255,255,255,.1);background:transparent;color:#c5cad6;
  border-radius:7px;padding:.2rem .45rem;font-size:.72rem;cursor:pointer
}
.mm-actions button:hover{border-color:#e23d12;color:#fff}
.mm-empty{
  border:1px dashed rgba(255,255,255,.14);border-radius:12px;padding:1.4rem;text-align:center;
  color:var(--muted,#8b93a7);font-size:.9rem
}
.mm-toast{
  position:fixed;left:50%;bottom:1.4rem;transform:translateX(-50%);
  background:#111827;border:1px solid rgba(34,197,94,.4);color:#e5e7eb;
  padding:.65rem 1rem;border-radius:10px;z-index:99;font-size:.88rem;
  box-shadow:0 10px 30px rgba(0,0,0,.35);display:none
}
.mm-toast.err{border-color:rgba(239,68,68,.5)}
.mm-tabs{display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:.85rem;border-bottom:1px solid rgba(255,255,255,.08);padding-bottom:.55rem}
.mm-tab{
  border:0;background:transparent;color:#9aa3b5;padding:.4rem .7rem;border-radius:8px 8px 0 0;
  cursor:pointer;font-size:.86rem
}
.mm-tab.on{background:rgba(226,61,18,.12);color:#fff;font-weight:650}
.mm-pane{display:none}
.mm-pane.on{display:block}
.mm-form{display:grid;gap:.75rem}
.mm-form .grid2{display:grid;grid-template-columns:1fr 1fr;gap:.65rem}
@media(max-width:700px){.mm-form .grid2{grid-template-columns:1fr}}
.mm-form label{display:grid;gap:.3rem;font-size:.84rem;font-weight:550;color:#e8eaef}
.mm-form input,.mm-form select,.mm-form textarea{
  width:100%;border:1px solid rgba(255,255,255,.1);background:#0d1017;color:#e8eaef;
  border-radius:8px;padding:.55rem .65rem;font:inherit
}
.mm-checks{display:grid;gap:.55rem;margin:.35rem 0}
.mm-checks label,
.mm-form .mm-checks label{
  display:flex!important;flex-direction:row!important;align-items:center;gap:.55rem;
  font-weight:600;color:#e8eaef;font-size:.88rem;line-height:1.4
}
.mm-checks input[type=checkbox]{width:auto!important;min-width:1.05rem;height:1.05rem;accent-color:#e23d12;flex:0 0 auto}
.mm-lab{display:block;margin-bottom:.28rem;font-size:.78rem;font-weight:700;color:#c5cad6}
.mm-panel-title{margin:0 0 .65rem;font-size:1.05rem}
.mm-panel-sub{margin:0 0 .85rem;color:var(--muted,#8b93a7);font-size:.82rem}
.mm-ghost{opacity:.45}
.mm-chosen{box-shadow:0 8px 24px rgba(0,0,0,.35)}
.mm-drag{opacity:.95}
.btn-sm{padding:.35rem .65rem;font-size:.82rem}
.mm-footer{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.85rem;padding-top:.85rem;border-top:1px solid rgba(255,255,255,.08)}
.mm-icons{display:flex;flex-wrap:wrap;gap:.35rem;margin:.35rem 0 .6rem}
.mm-icons button{width:2rem;height:2rem;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:#0d1017;cursor:pointer;font-size:1rem}
.mm-icons button:hover,.mm-icons button.on{border-color:#e23d12;background:rgba(226,61,18,.12)}
.mm-media{display:grid;grid-template-columns:repeat(auto-fill,minmax(64px,1fr));gap:.4rem;max-height:160px;overflow:auto;margin:.4rem 0;padding:.35rem;background:#0d1017;border-radius:10px;border:1px solid rgba(255,255,255,.08)}
.mm-media button{border:1px solid transparent;background:transparent;padding:0;border-radius:8px;cursor:pointer;overflow:hidden;aspect-ratio:1}
.mm-media button img{width:100%;height:100%;object-fit:cover;display:block}
.mm-media button:hover,.mm-media button.on{border-color:#e23d12;outline:1px solid #e23d12}
.mm-upload-row{display:flex;flex-wrap:wrap;gap:.45rem;align-items:center;margin:.35rem 0}
.mm-upload-row input[type=file]{font-size:.75rem;max-width:100%}
.mm-preview{width:48px;height:48px;border-radius:8px;object-fit:cover;border:1px solid rgba(255,255,255,.12);background:#0d1017}
.mm-sec-title{margin:.2rem 0 .45rem;font-size:.8rem;color:#9aa3b5;font-weight:700}
</style>

<div class="mm-head">
  <div>
    <h1>مگامنو ساز حرفه‌ای</h1>
    <p>ساخت منوی اصلی و زیرمنو با درگ‌اند‌دراپ · تنظیمات منظم مثل Uber / Quad / Groovy</p>
  </div>
  <div class="row" style="gap:.5rem">
    <a class="btn btn-outline" href="{{ url('/') }}" target="_blank">پیش‌نمایش سایت</a>
    <a class="btn btn-outline" href="{{ route('admin.theme-builder') }}">قالب صفحه اول</a>
  </div>
</div>

<div id="mm-flash" class="mm-toast"></div>

{{-- تنظیمات اصلی مگامنو (فشرده + برچسب واضح) --}}
@php $s = $settings ?? []; @endphp
<style>
.mm-set{margin-bottom:.75rem;padding:.7rem .8rem!important;color:#e8eaef}
.mm-set summary{cursor:pointer;font-weight:750;font-size:.92rem;list-style:none;display:flex;justify-content:space-between;align-items:center;gap:.5rem;color:#fff}
.mm-set summary::-webkit-details-marker{display:none}
.mm-set summary small{font-weight:500;color:#9aa3b5;font-size:.75rem}
.mm-set[open] summary{margin-bottom:.55rem;padding-bottom:.45rem;border-bottom:1px solid rgba(255,255,255,.08)}
.mm-set .mm-grid4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.55rem .6rem}
@media(max-width:1100px){.mm-set .mm-grid4{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.mm-set .mm-grid4{grid-template-columns:1fr}}
.mm-set .mm-field{display:flex;flex-direction:column;gap:.28rem;min-width:0}
.mm-set .mm-field > span{
  display:block;font-size:.74rem;font-weight:750;color:#d7dbe6;letter-spacing:.01em
}
.mm-set .mm-field input,.mm-set .mm-field select{
  width:100%;padding:.4rem .5rem!important;font-size:.8rem!important;border-radius:8px!important;
  border:1px solid rgba(255,255,255,.12);background:#0d1017;color:#e8eaef
}
.mm-set .mm-inline{display:flex;flex-wrap:wrap;gap:.65rem 1rem;align-items:center;margin-top:.55rem}
.mm-set .mm-inline label{
  display:flex!important;flex-direction:row!important;align-items:center;gap:.4rem;
  font-size:.82rem;font-weight:650;color:#e8eaef;margin:0
}
.mm-set .mm-inline input[type=checkbox]{width:auto!important;min-width:1rem;height:1rem;accent-color:#e23d12}
.mm-set .btn{padding:.4rem .85rem;font-size:.8rem}
.mm-panel,.mm-panel *{box-sizing:border-box}
.mm-panel{color:#e8eaef}
.mm-panel h1,.mm-panel h3,.mm-panel-title{color:#fff}
</style>
<details class="mm-panel mm-set" open>
  <summary>
    <span>تنظیمات اصلی مگامنو</span>
    <small>هر گزینه نام دارد</small>
  </summary>
  <form method="post" action="{{ route('admin.mega-menu.settings') }}">
    @csrf
    <div class="mm-grid4">
      <div class="mm-field">
        <span>تراز منو در سایت</span>
        <select name="nav_align">
          <option value="right" @selected(($s['nav_align']??'')==='right')>راست (کنار لوگو)</option>
          <option value="center" @selected(($s['nav_align']??'')==='center')>وسط</option>
          <option value="left" @selected(($s['nav_align']??'')==='left')>چپ</option>
        </select>
      </div>
      <div class="mm-field">
        <span>ظاهر گرافیکی نوار منو</span>
        <select name="nav_style">
          <option value="pills" @selected(($s['nav_style']??'')==='pills')>قرصی</option>
          <option value="underline" @selected(($s['nav_style']??'')==='underline')>زیرخط</option>
          <option value="boxed" @selected(($s['nav_style']??'')==='boxed')>باکس</option>
          <option value="minimal" @selected(($s['nav_style']??'')==='minimal')>مینیمال</option>
        </select>
      </div>
      <div class="mm-field">
        <span>پس‌زمینه هدر</span>
        <select name="header_bg">
          <option value="white" @selected(($s['header_bg']??'')==='white')>سفید</option>
          <option value="soft" @selected(($s['header_bg']??'')==='soft')>کم‌رنگ</option>
          <option value="glass" @selected(($s['header_bg']??'')==='glass')>شیشه‌ای</option>
          <option value="transparent" @selected(($s['header_bg']??'')==='transparent')>بی‌رنگ / شفاف</option>
          <option value="custom" @selected(($s['header_bg']??'')==='custom')>رنگ سفارشی</option>
        </select>
      </div>
      <div class="mm-field">
        <span>رنگ سفارشی هدر</span>
        <input type="color" name="header_bg_color" value="{{ $s['header_bg_color'] ?? '#ffffff' }}">
      </div>
      <div class="mm-field">
        <span id="mm-op-lab">شفافیت هدر: {{ $s['header_opacity'] ?? 100 }}٪</span>
        <input type="range" name="header_opacity" min="0" max="100" value="{{ $s['header_opacity'] ?? 100 }}"
               oninput="document.getElementById('mm-op-lab').textContent='شفافیت هدر: '+this.value+'٪'">
      </div>
      <div class="mm-field">
        <span>پس‌زمینه زیرمنو / پنل</span>
        <select name="panel_bg">
          <option value="white" @selected(($s['panel_bg']??'')==='white')>سفید</option>
          <option value="soft" @selected(($s['panel_bg']??'')==='soft')>سفید کم‌رنگ</option>
          <option value="glass" @selected(($s['panel_bg']??'')==='glass')>شیشه‌ای</option>
          <option value="transparent" @selected(($s['panel_bg']??'')==='transparent')>شفاف / بی‌رنگ</option>
        </select>
      </div>
      <div class="mm-field">
        <span>افکت زیرمنو</span>
        <select name="panel_fx">
          <option value="soft" @selected(($s['panel_fx']??'')==='soft')>سایه نرم</option>
          <option value="glass" @selected(($s['panel_fx']??'')==='glass')>شیشه + بلور</option>
          <option value="shadow" @selected(($s['panel_fx']??'')==='shadow')>سایه عمیق</option>
          <option value="glow" @selected(($s['panel_fx']??'')==='glow')>درخشش</option>
          <option value="lift" @selected(($s['panel_fx']??'')==='lift')>لیفت</option>
          <option value="none" @selected(($s['panel_fx']??'')==='none')>بدون افکت</option>
        </select>
      </div>
      <div class="mm-field">
        <span>اندازه زیرمنوی استاندارد</span>
        <select name="dropdown_size">
          <option value="auto" @selected(($s['dropdown_size']??'')==='auto')>استاندارد</option>
          <option value="compact" @selected(($s['dropdown_size']??'')==='compact')>فشرده</option>
          <option value="medium" @selected(($s['dropdown_size']??'')==='medium')>متوسط</option>
        </select>
      </div>
      <div class="mm-field">
        <span>نحوه باز شدن منو</span>
        <select name="open_mode">
          <option value="hover" @selected(($s['open_mode']??'')==='hover')>با هاور موس</option>
          <option value="click" @selected(($s['open_mode']??'')==='click')>با کلیک</option>
        </select>
      </div>
      <div class="mm-field">
        <span>رنگ اکسنت</span>
        <input type="color" name="accent" value="{{ $s['accent'] ?? '#e23d12' }}">
      </div>
      <div class="mm-field">
        <span>فاصله لوگو تا منو (px)</span>
        <input type="number" name="gap_brand" min="8" max="48" value="{{ $s['gap_brand'] ?? 18 }}">
      </div>
    </div>
    <div class="mm-inline">
      <label><input type="checkbox" name="show_icons" value="1" @checked(!empty($s['show_icons']))><span>نمایش آیکون‌ها در سایت</span></label>
      <label><input type="checkbox" name="header_blur" value="1" @checked(!array_key_exists('header_blur',$s) || !empty($s['header_blur']))><span>بلور پس‌زمینه هدر</span></label>
      <button class="btn btn-primary" type="submit">ذخیره تنظیمات</button>
    </div>
  </form>
</details>

<details class="mm-panel mm-set" open>
  <summary>
    <span>پیشنهاد سازمانی (بنر مگامنو محصولات)</span>
    <small>تصویر، عنوان، لینک و دکمه بنر سمت چپ پنل</small>
  </summary>
  <form method="post" action="{{ route('admin.mega-menu.settings') }}" id="mm-org-promo-form">
    @csrf
    {{-- نگه داشتن سایر تنظیمات هنگام ذخیره جداگانه این بخش --}}
    <input type="hidden" name="nav_align" value="{{ $s['nav_align'] ?? 'right' }}">
    <input type="hidden" name="nav_style" value="{{ $s['nav_style'] ?? 'pills' }}">
    <input type="hidden" name="header_bg" value="{{ $s['header_bg'] ?? 'white' }}">
    <input type="hidden" name="header_bg_color" value="{{ $s['header_bg_color'] ?? '#ffffff' }}">
    <input type="hidden" name="header_opacity" value="{{ $s['header_opacity'] ?? 100 }}">
    <input type="hidden" name="panel_bg" value="{{ $s['panel_bg'] ?? 'white' }}">
    <input type="hidden" name="panel_fx" value="{{ $s['panel_fx'] ?? 'soft' }}">
    <input type="hidden" name="dropdown_size" value="{{ $s['dropdown_size'] ?? 'auto' }}">
    <input type="hidden" name="open_mode" value="{{ $s['open_mode'] ?? 'hover' }}">
    <input type="hidden" name="accent" value="{{ $s['accent'] ?? '#e23d12' }}">
    <input type="hidden" name="gap_brand" value="{{ $s['gap_brand'] ?? 18 }}">
    @if(!empty($s['show_icons']))<input type="hidden" name="show_icons" value="1">@endif
    @if(!array_key_exists('header_blur',$s) || !empty($s['header_blur']))<input type="hidden" name="header_blur" value="1">@endif

    <div class="mm-inline" style="margin-top:0;margin-bottom:.55rem">
      <label>
        <input type="checkbox" name="org_promo_enabled" value="1" @checked(!array_key_exists('org_promo_enabled',$s) || !empty($s['org_promo_enabled']))>
        <span>نمایش بنر پیشنهاد سازمانی در مگامنو محصولات</span>
      </label>
    </div>
    <div class="mm-grid4">
      <div class="mm-field">
        <span>عنوان بنر</span>
        <input type="text" name="org_promo_title" value="{{ $s['org_promo_title'] ?? 'پیشنهاد سازمانی' }}" maxlength="120" placeholder="پیشنهاد سازمانی">
      </div>
      <div class="mm-field">
        <span>متن دکمه</span>
        <input type="text" name="org_promo_button" value="{{ $s['org_promo_button'] ?? 'مشاهده' }}" maxlength="60" placeholder="مشاهده">
      </div>
      <div class="mm-field">
        <span>لینک بنر / دکمه</span>
        <input type="text" name="org_promo_url" value="{{ $s['org_promo_url'] ?? '/products' }}" maxlength="500" placeholder="/products یا /services">
      </div>
      <div class="mm-field" style="grid-column:1/-1">
        <span>توضیح کوتاه</span>
        <input type="text" name="org_promo_desc" value="{{ $s['org_promo_desc'] ?? 'تأمین هارد و SSD برای کسب‌وکارها با گارانتی شفاف' }}" maxlength="255" placeholder="متن زیر عنوان">
      </div>
      <div class="mm-field" style="grid-column:1/-1">
        <span>آدرس تصویر بنر</span>
        <input type="text" name="org_promo_image" id="org_promo_image" value="{{ $s['org_promo_image'] ?? '/images/home/mega-promo.jpg' }}" maxlength="500" placeholder="/images/home/mega-promo.jpg یا /uploads/menu/...">
      </div>
    </div>
    <div class="mm-upload-row" style="margin-top:.35rem">
      <img class="mm-preview" id="org_promo_preview" src="{{ $s['org_promo_image'] ?? asset('images/home/mega-promo.jpg') }}" alt="" onerror="this.style.opacity=.3">
      <input type="file" id="up_org_promo" accept="image/*">
      <button type="button" class="btn btn-outline btn-sm" id="btn-up-org-promo">آپلود تصویر</button>
      <button type="button" class="btn btn-outline btn-sm" data-media-pick data-field="org_promo_image" data-as="url" data-title="تصویر پیشنهاد سازمانی">فایل‌منیجر</button>
    </div>
    <p class="mm-hint" style="margin:.45rem 0 .55rem">این بنر در پنل مگای آیتم‌هایی مثل «محصولات» نمایش داده می‌شود (اگر زیرمنوی نوع پرومو جداگانه نداشته باشند).</p>
    <div class="mm-inline">
      <button class="btn btn-primary" type="submit">ذخیره پیشنهاد سازمانی</button>
    </div>
  </form>
</details>

<div class="mm-studio">
  {{-- درخت منو --}}
  <div class="mm-panel">
    <div class="mm-toolbar">
      <button type="button" class="btn btn-primary btn-sm" id="mm-add-root">+ منوی اصلی</button>
      <button type="button" class="btn btn-outline btn-sm" id="mm-add-child" disabled>+ زیرمنو</button>
      <button type="button" class="btn btn-outline btn-sm" id="mm-expand-all">باز کردن همه</button>
      <button type="button" class="btn btn-outline btn-sm" id="mm-collapse-all">جمع کردن</button>
    </div>
    <p class="mm-hint">آیتم‌ها را بکشید تا ترتیب یا سطح (منوی اصلی ↔ زیرمنو) عوض شود. روی هر ردیف کلیک کنید تا تنظیماتش در پنل کناری باز شود.</p>
    <div id="mm-empty" class="mm-empty" style="display:none">هنوز آیتمی نیست. «منوی اصلی» را بزنید.</div>
    <ul id="mm-tree" class="mm-nest" data-parent=""></ul>
  </div>

  {{-- پنل تنظیمات --}}
  <div class="mm-panel" id="mm-editor">
    <h3 class="mm-panel-title" id="mm-editor-title">تنظیمات آیتم</h3>
    <p class="mm-panel-sub" id="mm-editor-sub">یک آیتم از درخت انتخاب کنید یا منوی اصلی بسازید.</p>

    <div class="mm-tabs" id="mm-tabs">
      <button type="button" class="mm-tab on" data-tab="general">عمومی</button>
      <button type="button" class="mm-tab" data-tab="look">ظاهر</button>
      <button type="button" class="mm-tab" data-tab="mega">پنل مگا</button>
      <button type="button" class="mm-tab" data-tab="extra">پیشرفته</button>
    </div>

    <form id="mm-form" class="mm-form" autocomplete="off">
      <input type="hidden" name="id" id="f_id" value="">
      <input type="hidden" name="parent_id" id="f_parent_id" value="">
      <input type="hidden" name="sort_order" id="f_sort_order" value="0">

      <div class="mm-pane on" data-pane="general">
        <div class="grid2">
          <label>عنوان<input name="title" id="f_title" required placeholder="مثلاً دسته‌بندی‌ها"></label>
          <label>نوع
            <select name="type" id="f_type">
              @foreach($types as $k=>$lab)
                <option value="{{ $k }}">{{ $lab }}</option>
              @endforeach
            </select>
          </label>
          <label>آدرس / URL<input name="url" id="f_url" placeholder="/products"></label>
          <label>دسته محصول
            <select name="category_id" id="f_category_id">
              <option value="">—</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
              @endforeach
            </select>
          </label>
          <label>نشان (Badge)<input name="badge" id="f_badge" placeholder="جدید / فروش"></label>
          <label>فونت منو
            <select name="font_family" id="f_font_family">
              @foreach($fonts as $k=>$lab)
                <option value="{{ $k }}">{{ $lab }}</option>
              @endforeach
            </select>
          </label>
          <label>رنگ عنوان<input type="color" name="title_color" id="f_title_color" value="#1f2937"></label>
          <label>رنگ لینک<input type="color" name="link_color" id="f_link_color" value="#6b7280"></label>
          <label>رنگ هاور<input type="color" name="hover_color" id="f_hover_color" value="#e23d12"></label>
          <label>رنگ متن پنل<input type="color" name="text_color" id="f_text_color" value="#334155"></label>
          <label style="grid-column:1/-1">توضیح کوتاه<input name="description" id="f_description" placeholder="زیرعنوان لینک"></label>
        </div>
        <div class="mm-sec-title">آیکون (ایموجی یا تصویر)</div>
        <label>آیکون متنی<input name="icon" id="f_icon" placeholder="🔥 یا ★"></label>
        <div class="mm-icons" id="mm-icon-presets">
          @foreach($iconPresets as $ic)
            <button type="button" data-icon="{{ $ic }}" title="{{ $ic }}">{{ $ic }}</button>
          @endforeach
        </div>
        <label>تصویر آیکون (URL)<input name="icon_image_url" id="f_icon_image_url" placeholder="/uploads/menu/..."></label>
        <div class="mm-upload-row">
          <input type="file" id="up_icon" accept="image/*">
          <button type="button" class="btn btn-outline btn-sm" data-upload="icon_image_url" data-file="up_icon">آپلود آیکون</button>
          <button type="button" class="btn btn-outline btn-sm" data-media-pick data-field="f_icon_image_url" data-as="url" data-title="آیکون منو">فایل‌منیجر</button>
          <img class="mm-preview" id="prev_icon" alt="" style="display:none">
        </div>
        <div class="mm-sec-title">از کتابخانه هاست</div>
        <div class="mm-media" data-target="icon_image_url">
          @forelse($media as $m)
            <button type="button" data-url="{{ $m['url'] }}" title="{{ $m['name'] }}"><img src="{{ $m['url'] }}" alt=""></button>
          @empty
            <span class="muted" style="grid-column:1/-1;font-size:.78rem;padding:.4rem">کتابخانه خالی است — از فایل‌منیجر انتخاب کنید</span>
          @endforelse
        </div>
        <label>اندازه آیکون (px)<input type="number" name="icon_size" id="f_icon_size" min="12" max="48" value="18"></label>
        <div class="mm-checks" style="margin-top:.5rem">
          <label for="f_is_active"><input type="checkbox" name="is_active" id="f_is_active" value="1" checked><span>فعال در سایت</span></label>
          <label for="f_open_in_new"><input type="checkbox" name="open_in_new" id="f_open_in_new" value="1"><span>باز شدن در تب جدید</span></label>
        </div>
      </div>

      <div class="mm-pane" data-pane="look">
        <div class="mm-sec-title">تصویر آیتم / پرومو</div>
        <label>آدرس تصویر<input name="image_url" id="f_image_url" placeholder="/uploads/..."></label>
        <div class="mm-upload-row">
          <input type="file" id="up_image" accept="image/*">
          <button type="button" class="btn btn-outline btn-sm" data-upload="image_url" data-file="up_image">آپلود از کامپیوتر</button>
          <button type="button" class="btn btn-outline btn-sm" data-media-pick data-field="f_image_url" data-as="url" data-title="تصویر آیتم منو">فایل‌منیجر</button>
          <img class="mm-preview" id="prev_image" alt="" style="display:none">
        </div>
        <div class="mm-sec-title">کتابخانه / فایل‌منیجر هاست</div>
        <div class="mm-media" data-target="image_url">
          @foreach($media as $m)
            <button type="button" data-url="{{ $m['url'] }}" title="{{ $m['name'] }}"><img src="{{ $m['url'] }}" alt=""></button>
          @endforeach
        </div>
        <div class="grid2" style="margin-top:.6rem">
          <label>رنگ اکسنت<input type="color" name="accent_color" id="f_accent_color" value="#e23d12"></label>
          <label>رنگ پس‌زمینه پنل<input type="color" name="panel_bg_color" id="f_panel_bg_color" value="#ffffff"></label>
          <label>کلاس CSS<input name="css_class" id="f_css_class" placeholder="my-mega"></label>
          <label>برچسب تب<input name="tab_label" id="f_tab_label" placeholder="اگر نوع=تب"></label>
        </div>
        <label>HTML / محتوای سفارشی<textarea name="html" id="f_html" rows="3" placeholder="برای HTML یا پرومو"></textarea></label>
      </div>

      <div class="mm-pane" data-pane="mega">
        <p class="mm-hint" style="margin-top:0">اگر فقط زیرمنوی معمولی می‌خواهید، تیک مگامنو را خاموش بگذارید. پس‌زمینه سفید/شفاف زیرمنو را از «تنظیمات اصلی» بالا تنظیم کنید.</p>
        <div class="mm-checks" style="margin-bottom:.65rem">
          <label for="f_is_mega"><input type="checkbox" name="is_mega" id="f_is_mega" value="1"><span>مگامنو (پنل بازشو بزرگ چندستونه)</span></label>
          <label for="f_show_search"><input type="checkbox" name="show_search" id="f_show_search" value="1"><span>کادر جستجو داخل پنل مگا</span></label>
          <label for="f_is_tabbed"><input type="checkbox" name="is_tabbed" id="f_is_tabbed" value="1"><span>منوی تب‌دار (فقط مگا)</span></label>
        </div>
        <div class="grid2">
          <label>تعداد ستون<input type="number" name="columns" id="f_columns" min="1" max="6" value="3"></label>
          <label>عرض پنل
            <select name="panel_width" id="f_panel_width">
              <option value="normal">معمولی</option>
              <option value="wide" selected>عریض</option>
              <option value="full">تمام‌عرض</option>
            </select>
          </label>
          <label>تراز پنل (RTL)
            <select name="panel_align" id="f_panel_align">
              @foreach($panelAligns as $k=>$lab)
                <option value="{{ $k }}">{{ $lab }}</option>
              @endforeach
            </select>
          </label>
          <label>نحوه باز شدن
            <select name="open_mode" id="f_open_mode">
              @foreach($openModes as $k=>$lab)
                <option value="{{ $k }}">{{ $lab }}</option>
              @endforeach
            </select>
          </label>
          <label>انیمیشن
            <select name="animation" id="f_animation">
              @foreach($animations as $k=>$lab)
                <option value="{{ $k }}">{{ $lab }}</option>
              @endforeach
            </select>
          </label>
          <label>افکت
            <select name="effect" id="f_effect">
              @foreach($effects as $k=>$lab)
                <option value="{{ $k }}">{{ $lab }}</option>
              @endforeach
            </select>
          </label>
          <label>گردی گوشه پنل<input type="number" name="panel_radius" id="f_panel_radius" min="0" max="40" value="18"></label>
          <label>متن جای‌نگهدار جستجو<input name="search_placeholder" id="f_search_placeholder" value="جستجو..."></label>
        </div>
        <div class="mm-sec-title">تصویر پس‌زمینه پنل</div>
        <label>URL پس‌زمینه<input name="bg_image_url" id="f_bg_image_url" placeholder="URL پس‌زمینه"></label>
        <div class="mm-upload-row">
          <input type="file" id="up_bg" accept="image/*">
          <button type="button" class="btn btn-outline btn-sm" data-upload="bg_image_url" data-file="up_bg">آپلود پس‌زمینه</button>
          <button type="button" class="btn btn-outline btn-sm" data-media-pick data-field="f_bg_image_url" data-as="url" data-title="پس‌زمینه منو">فایل‌منیجر</button>
          <img class="mm-preview" id="prev_bg" alt="" style="display:none">
        </div>
        <div class="mm-media" data-target="bg_image_url">
          @foreach($media as $m)
            <button type="button" data-url="{{ $m['url'] }}" title="{{ $m['name'] }}"><img src="{{ $m['url'] }}" alt=""></button>
          @endforeach
        </div>
      </div>

      <div class="mm-pane" data-pane="extra">
        <div class="grid2">
          <label>نوع فرم بازشو
            <select name="form_type" id="f_form_type">
              <option value="none">ندارد</option>
              <option value="search">جستجو</option>
              <option value="newsletter">خبرنامه</option>
              <option value="login">ورود سریع</option>
              <option value="custom">HTML سفارشی</option>
            </select>
          </label>
        </div>
        <label>HTML فرم سفارشی<textarea name="form_html" id="f_form_html" rows="3" placeholder="اگر نوع فرم = custom"></textarea></label>
        <p class="mm-hint">افزونه‌های مگامنو: جستجو داخل پنل · تب‌دار · پرومو تصویری · فرم بازشو · آیکون تصویری · فونت و رنگ · پس‌زمینه · انیمیشن/افکت · درگ‌اند‌دراپ درخت</p>
      </div>

      <div class="mm-footer">
        <button type="submit" class="btn btn-primary" id="mm-save">ذخیره تنظیمات</button>
        <button type="button" class="btn btn-outline" id="mm-dup" disabled>کپی آیتم</button>
        <button type="button" class="btn btn-outline" id="mm-del" disabled style="color:#f87171">حذف</button>
      </div>
    </form>
  </div>
</div>

@php
  $routes = [
    'store' => route('admin.mega-menu.store'),
    'reorder' => route('admin.mega-menu.reorder'),
    'update' => url('/admin/mega-menu'),
    'toggle' => url('/admin/mega-menu'),
    'destroy' => url('/admin/mega-menu'),
    'upload' => route('admin.mega-menu.upload'),
    'settings' => route('admin.mega-menu.settings'),
  ];
@endphp
<script>
(function(){
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const ROUTES = @json($routes);
  const TYPES = @json($types);
  let tree = @json($treeJson);
  let selectedId = {{ $edit?->id ?? 'null' }};
  let sortables = [];

  const els = {
    tree: document.getElementById('mm-tree'),
    empty: document.getElementById('mm-empty'),
    form: document.getElementById('mm-form'),
    title: document.getElementById('mm-editor-title'),
    sub: document.getElementById('mm-editor-sub'),
    addRoot: document.getElementById('mm-add-root'),
    addChild: document.getElementById('mm-add-child'),
    del: document.getElementById('mm-del'),
    dup: document.getElementById('mm-dup'),
    toast: document.getElementById('mm-flash'),
  };

  function toast(msg, err){
    els.toast.textContent = msg;
    els.toast.className = 'mm-toast' + (err ? ' err' : '');
    els.toast.style.display = 'block';
    clearTimeout(toast._t);
    toast._t = setTimeout(()=>{ els.toast.style.display='none'; }, 3200);
  }

  async function api(url, method, body){
    const opt = {
      method,
      headers: {
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    };
    if (body !== undefined) {
      opt.headers['Content-Type'] = 'application/json';
      opt.body = JSON.stringify(body);
    }
    let res = await fetch(url, opt);
    // برخی هاست‌ها PUT/DELETE را مسدود می‌کنند — با POST + _method دوباره تلاش کن
    if ((method === 'PUT' || method === 'DELETE') && (res.status === 405 || res.status === 501)) {
      const fallback = Object.assign({}, body || {}, { _method: method });
      res = await fetch(url + (method === 'DELETE' ? '/delete' : ''), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': CSRF,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(fallback),
      });
    }
    let data = {};
    const raw = await res.text();
    try { data = raw ? JSON.parse(raw) : {}; } catch(e){
      if (!res.ok) throw new Error('پاسخ نامعتبر سرور (' + res.status + '). صفحه را تازه کنید.');
    }
    if (!res.ok || data.ok === false) {
      const msg = data.message
        || (data.errors ? Object.values(data.errors).flat().join(' · ') : '')
        || ('خطا ' + res.status);
      throw new Error(msg);
    }
    return data;
  }

  async function uploadFile(fileInputId, fieldId){
    const inp = document.getElementById(fileInputId);
    if (!inp || !inp.files || !inp.files[0]) { toast('فایلی انتخاب نشده', true); return; }
    const fd = new FormData();
    fd.append('file', inp.files[0]);
    const res = await fetch(ROUTES.upload, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: fd,
    });
    const data = await res.json().catch(()=>({}));
    if (!res.ok || !data.ok) throw new Error(data.message || 'آپلود ناموفق');
    const real = document.getElementById('f_' + fieldId);
    if (real) real.value = data.file.url;
    updatePreviews();
    toast('آپلود شد');
  }

  function updatePreviews(){
    [['f_icon_image_url','prev_icon'],['f_image_url','prev_image'],['f_bg_image_url','prev_bg']].forEach(([fid,pid])=>{
      const v = document.getElementById(fid)?.value;
      const img = document.getElementById(pid);
      if (!img) return;
      if (v) { img.src = v; img.style.display = 'block'; }
      else { img.style.display = 'none'; }
    });
  }

  function findNode(nodes, id){
    for (const n of nodes || []) {
      if (+n.id === +id) return n;
      const c = findNode(n.children || [], id);
      if (c) return c;
    }
    return null;
  }

  function flattenMap(nodes, map){
    map = map || {};
    (nodes||[]).forEach(n=>{ map[n.id]=n; flattenMap(n.children||[], map); });
    return map;
  }

  function defaults(){
    return {
      id: null, parent_id: null, title: 'آیتم جدید', type: 'link', url: '/',
      category_id: null, badge: '', icon: '', columns: 3, html: '',
      is_mega: false, open_in_new: false, is_active: true, sort_order: 0,
      image_url: '', bg_image_url: '', description: '', animation: 'fade',
      effect: 'shadow', panel_width: 'wide', show_search: false,
      search_placeholder: 'جستجو...', is_tabbed: false, tab_label: '',
      form_type: 'none', form_html: '', accent_color: '#e23d12', css_class: '',
      icon_image_url: '', font_family: '', title_color: '#1f2937', link_color: '#6b7280',
      hover_color: '#e23d12', text_color: '#334155', panel_bg_color: '#ffffff',
      panel_radius: 18, icon_size: 18, open_mode: 'hover', panel_align: 'right',
      children: []
    };
  }

  function fillForm(item){
    const d = Object.assign(defaults(), item || {});
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
    const chk = (id, on) => { const el = document.getElementById(id); if (el) el.checked = !!on; };
    set('f_id', d.id || '');
    set('f_parent_id', d.parent_id || '');
    set('f_sort_order', d.sort_order ?? 0);
    set('f_title', d.title);
    set('f_type', d.type || 'link');
    set('f_url', d.url || '');
    set('f_category_id', d.category_id || '');
    set('f_badge', d.badge || '');
    set('f_icon', d.icon || '');
    set('f_description', d.description || '');
    set('f_image_url', d.image_url || '');
    set('f_bg_image_url', d.bg_image_url || '');
    set('f_accent_color', d.accent_color || '#e23d12');
    set('f_css_class', d.css_class || '');
    set('f_columns', d.columns ?? 3);
    set('f_animation', d.animation || 'fade');
    set('f_effect', d.effect || 'shadow');
    set('f_panel_width', d.panel_width || 'wide');
    set('f_search_placeholder', d.search_placeholder || 'جستجو...');
    set('f_tab_label', d.tab_label || '');
    set('f_form_type', d.form_type || 'none');
    set('f_html', d.html || '');
    set('f_form_html', d.form_html || '');
    set('f_icon_image_url', d.icon_image_url || '');
    set('f_font_family', d.font_family || '');
    set('f_title_color', d.title_color || '#1f2937');
    set('f_link_color', d.link_color || '#6b7280');
    set('f_hover_color', d.hover_color || '#e23d12');
    set('f_text_color', d.text_color || '#334155');
    set('f_panel_bg_color', d.panel_bg_color || '#ffffff');
    set('f_panel_radius', d.panel_radius ?? 18);
    set('f_icon_size', d.icon_size ?? 18);
    set('f_open_mode', d.open_mode || 'hover');
    set('f_panel_align', d.panel_align || 'right');
    chk('f_is_active', d.is_active !== false);
    chk('f_open_in_new', d.open_in_new);
    chk('f_is_mega', d.is_mega);
    chk('f_show_search', d.show_search);
    chk('f_is_tabbed', d.is_tabbed);
    document.querySelectorAll('#mm-icon-presets button').forEach(b=>{
      b.classList.toggle('on', b.dataset.icon === (d.icon||''));
    });
    updatePreviews();

    const editing = !!d.id;
    els.title.textContent = editing ? ('ویرایش: ' + d.title) : 'آیتم جدید';
    els.sub.textContent = editing
      ? (d.parent_id ? 'زیرمنو · تنظیمات را ذخیره کنید' : 'منوی اصلی · می‌توانید مگامنو و زیرمنو بسازید')
      : 'عنوان را وارد کنید و ذخیره کنید';
    els.del.disabled = !editing;
    els.dup.disabled = !editing;
    els.addChild.disabled = !editing;
  }

  function readForm(){
    const g = id => document.getElementById(id);
    const val = id => (g(id)?.value ?? '').trim();
    const num = id => {
      const v = val(id);
      return v === '' ? null : +v;
    };
    return {
      title: val('f_title') || 'بدون عنوان',
      type: val('f_type') || 'link',
      url: val('f_url'),
      parent_id: num('f_parent_id'),
      category_id: num('f_category_id'),
      badge: val('f_badge'),
      icon: val('f_icon'),
      description: val('f_description'),
      image_url: val('f_image_url'),
      bg_image_url: val('f_bg_image_url'),
      accent_color: val('f_accent_color') || '#e23d12',
      css_class: val('f_css_class'),
      columns: Math.max(1, Math.min(6, +(val('f_columns') || 3))),
      sort_order: +(val('f_sort_order') || 0),
      animation: val('f_animation') || 'fade',
      effect: val('f_effect') || 'shadow',
      panel_width: val('f_panel_width') || 'wide',
      search_placeholder: val('f_search_placeholder'),
      tab_label: val('f_tab_label'),
      form_type: val('f_form_type') || 'none',
      html: g('f_html')?.value || '',
      form_html: g('f_form_html')?.value || '',
      icon_image_url: val('f_icon_image_url'),
      font_family: val('f_font_family'),
      title_color: val('f_title_color'),
      link_color: val('f_link_color'),
      hover_color: val('f_hover_color'),
      text_color: val('f_text_color'),
      panel_bg_color: val('f_panel_bg_color'),
      panel_radius: Math.max(0, Math.min(40, +(val('f_panel_radius') || 18))),
      icon_size: Math.max(12, Math.min(48, +(val('f_icon_size') || 18))),
      open_mode: val('f_open_mode') || 'hover',
      panel_align: val('f_panel_align') || 'right',
      is_active: !!g('f_is_active')?.checked,
      open_in_new: !!g('f_open_in_new')?.checked,
      is_mega: !!g('f_is_mega')?.checked,
      show_search: !!g('f_show_search')?.checked,
      is_tabbed: !!g('f_is_tabbed')?.checked,
    };
  }

  function chipHtml(item){
    const bits = [];
    bits.push(`<span class="mm-chip">${TYPES[item.type] || item.type}</span>`);
    if (item.is_mega) bits.push('<span class="mm-chip mega">مگا</span>');
    if (item.badge) bits.push(`<span class="mm-chip badge">${escapeHtml(item.badge)}</span>`);
    if (!item.is_active) bits.push('<span class="mm-chip">خاموش</span>');
    return bits.join('');
  }

  function escapeHtml(s){
    return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  function renderNode(item){
    const li = document.createElement('li');
    li.className = 'mm-item';
    li.dataset.id = item.id;
    const row = document.createElement('div');
    row.className = 'mm-row' + (item.is_active ? '' : ' is-off') + (+selectedId === +item.id ? ' is-active' : '');
    row.innerHTML = `
      <span class="mm-handle" title="جابه‌جایی">⠿</span>
      <span class="mm-icon">${escapeHtml(item.icon || '☰')}</span>
      <span class="mm-title">${escapeHtml(item.title)}</span>
      <span class="mm-meta">${chipHtml(item)}</span>
      <span class="mm-actions">
        <button type="button" data-act="child" title="زیرمنو">+</button>
        <button type="button" data-act="toggle" title="فعال/خاموش">${item.is_active ? '●' : '○'}</button>
        <button type="button" data-act="del" title="حذف">×</button>
      </span>`;
    row.addEventListener('click', (e)=>{
      if (e.target.closest('[data-act]') || e.target.closest('.mm-handle')) return;
      selectItem(item.id);
    });
    row.querySelector('[data-act="child"]').addEventListener('click', (e)=>{ e.stopPropagation(); addChild(item.id); });
    row.querySelector('[data-act="toggle"]').addEventListener('click', (e)=>{ e.stopPropagation(); toggleItem(item.id); });
    row.querySelector('[data-act="del"]').addEventListener('click', (e)=>{ e.stopPropagation(); deleteItem(item.id); });
    li.appendChild(row);

    const nest = document.createElement('ul');
    nest.className = 'mm-nest';
    nest.dataset.parent = item.id;
    (item.children || []).forEach(ch => nest.appendChild(renderNode(ch)));
    li.appendChild(nest);
    return li;
  }

  function destroySortables(){
    sortables.forEach(s => { try{ s.destroy(); }catch(e){} });
    sortables = [];
  }

  function bindSortables(root){
    const lists = [root, ...root.querySelectorAll('.mm-nest')];
    lists.forEach(el => {
      const s = Sortable.create(el, {
        group: 'mega-menu',
        animation: 160,
        handle: '.mm-handle',
        draggable: '.mm-item',
        fallbackOnBody: true,
        swapThreshold: 0.55,
        ghostClass: 'mm-ghost',
        chosenClass: 'mm-chosen',
        dragClass: 'mm-drag',
        onEnd: () => persistOrder(),
      });
      sortables.push(s);
    });
  }

  function serializeDom(ul){
    return [...ul.children].filter(li => li.classList.contains('mm-item')).map(li => {
      const childUl = li.querySelector(':scope > .mm-nest');
      return {
        id: +li.dataset.id,
        children: childUl ? serializeDom(childUl) : [],
      };
    });
  }

  async function persistOrder(){
    const payload = { tree: serializeDom(els.tree) };
    try {
      await api(ROUTES.reorder, 'POST', payload);
      // rebuild local tree from DOM + existing map
      const map = flattenMap(tree);
      function hydrate(nodes){
        return nodes.map(n => {
          const base = map[n.id] || { id: n.id, title: '?', type: 'link', children: [] };
          return Object.assign({}, base, { children: hydrate(n.children || []) });
        });
      }
      tree = hydrate(payload.tree);
      toast('ترتیب ذخیره شد');
    } catch (e) {
      toast(e.message || 'خطا در ذخیره ترتیب', true);
      render();
    }
  }

  function render(){
    destroySortables();
    els.tree.innerHTML = '';
    const has = (tree || []).length > 0;
    els.empty.style.display = has ? 'none' : 'block';
    (tree || []).forEach(item => els.tree.appendChild(renderNode(item)));
    bindSortables(els.tree);
    if (selectedId) {
      const n = findNode(tree, selectedId);
      if (n) fillForm(n);
      else { selectedId = null; fillForm(null); }
    }
  }

  function selectItem(id){
    selectedId = id;
    const n = findNode(tree, id);
    fillForm(n);
    render();
  }

  async function addRoot(){
    try {
      const data = await api(ROUTES.store, 'POST', {
        title: 'منوی جدید',
        type: 'link',
        url: '/',
        parent_id: null,
        is_active: true,
        is_mega: false,
        columns: 3,
        animation: 'fade',
        effect: 'shadow',
        panel_width: 'wide',
        form_type: 'none',
      });
      tree.push(Object.assign(defaults(), data.item, { children: data.item.children || [] }));
      selectedId = data.item.id;
      render();
      toast('منوی اصلی اضافه شد');
      document.getElementById('f_title')?.focus();
    } catch (e) {
      toast(e.message, true);
    }
  }

  async function addChild(parentId){
    parentId = parentId || selectedId;
    if (!parentId) return;
    try {
      const data = await api(ROUTES.store, 'POST', {
        title: 'زیرمنو',
        type: 'link',
        url: '#',
        parent_id: +parentId,
        is_active: true,
        columns: 3,
        animation: 'fade',
        effect: 'shadow',
        panel_width: 'wide',
        form_type: 'none',
      });
      const parent = findNode(tree, parentId);
      if (parent) {
        parent.children = parent.children || [];
        parent.children.push(Object.assign(defaults(), data.item, { children: [] }));
      }
      selectedId = data.item.id;
      render();
      toast('زیرمنو اضافه شد');
    } catch (e) {
      toast(e.message, true);
    }
  }

  async function toggleItem(id){
    try {
      const data = await api(ROUTES.toggle + '/' + id + '/toggle', 'POST');
      const n = findNode(tree, id);
      if (n) n.is_active = data.is_active;
      if (+selectedId === +id) fillForm(n);
      render();
      toast(data.message || 'وضعیت تغییر کرد');
    } catch (e) {
      toast(e.message, true);
    }
  }

  async function deleteItem(id){
    if (!confirm('این آیتم و همه زیرمنوها حذف شوند؟')) return;
    try {
      await api(ROUTES.destroy + '/' + id + '/delete', 'POST', { _method: 'DELETE' });
      function remove(nodes, rid){
        return (nodes||[]).filter(n => {
          if (+n.id === +rid) return false;
          n.children = remove(n.children || [], rid);
          return true;
        });
      }
      tree = remove(tree, id);
      if (+selectedId === +id) { selectedId = null; fillForm(null); }
      render();
      toast('حذف شد');
    } catch (e) {
      toast(e.message, true);
    }
  }

  async function saveForm(e){
    e.preventDefault();
    const payload = readForm();
    const id = document.getElementById('f_id').value;
    try {
      if (id) {
        delete payload.parent_id; // سطح فقط با درگ‌اند‌دراپ عوض می‌شود
        const data = await api(ROUTES.update + '/' + id, 'PUT', payload);
        const n = findNode(tree, id);
        if (n) Object.assign(n, data.item, { children: n.children || [] });
        selectedId = +id;
        toast(data.message || 'ذخیره شد');
      } else {
        const data = await api(ROUTES.store, 'POST', payload);
        if (payload.parent_id) {
          const p = findNode(tree, payload.parent_id);
          if (p) { p.children = p.children || []; p.children.push(Object.assign(defaults(), data.item, { children: [] })); }
        } else {
          tree.push(Object.assign(defaults(), data.item, { children: [] }));
        }
        selectedId = data.item.id;
        toast(data.message || 'اضافه شد');
      }
      render();
    } catch (err) {
      toast(err.message, true);
    }
  }

  async function duplicateSelected(){
    if (!selectedId) return;
    const n = findNode(tree, selectedId);
    if (!n) return;
    const copy = Object.assign({}, n);
    delete copy.id;
    copy.title = (n.title || '') + ' (کپی)';
    copy.children = [];
    copy.parent_id = n.parent_id;
    try {
      const data = await api(ROUTES.store, 'POST', copy);
      if (n.parent_id) {
        const p = findNode(tree, n.parent_id);
        if (p) { p.children = p.children || []; p.children.push(Object.assign(defaults(), data.item, { children: [] })); }
      } else {
        tree.push(Object.assign(defaults(), data.item, { children: [] }));
      }
      selectedId = data.item.id;
      render();
      toast('کپی ساخته شد');
    } catch (e) {
      toast(e.message, true);
    }
  }

  // tabs
  document.querySelectorAll('.mm-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.mm-tab').forEach(t => t.classList.toggle('on', t === tab));
      document.querySelectorAll('.mm-pane').forEach(p => p.classList.toggle('on', p.dataset.pane === tab.dataset.tab));
    });
  });

  els.addRoot.addEventListener('click', addRoot);
  els.addChild.addEventListener('click', () => addChild(selectedId));
  els.del.addEventListener('click', () => selectedId && deleteItem(selectedId));
  els.dup.addEventListener('click', duplicateSelected);
  els.form.addEventListener('submit', saveForm);

  document.getElementById('mm-expand-all')?.addEventListener('click', () => {
    document.querySelectorAll('#mm-tree .mm-nest').forEach(ul => { ul.style.display = ''; });
  });
  document.getElementById('mm-collapse-all')?.addEventListener('click', () => {
    document.querySelectorAll('#mm-tree .mm-item > .mm-nest').forEach(ul => {
      if (ul.children.length) ul.style.display = 'none';
    });
  });

  document.querySelectorAll('#mm-icon-presets button').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('f_icon').value = btn.dataset.icon || '';
      document.querySelectorAll('#mm-icon-presets button').forEach(b => b.classList.toggle('on', b === btn));
    });
  });
  document.querySelectorAll('[data-upload]').forEach(btn => {
    btn.addEventListener('click', async () => {
      try {
        await uploadFile(btn.dataset.file, btn.dataset.upload);
      } catch (e) {
        toast(e.message || 'آپلود ناموفق', true);
      }
    });
  });

  const orgPromoInput = document.getElementById('org_promo_image');
  const orgPromoPreview = document.getElementById('org_promo_preview');
  function syncOrgPromoPreview(){
    if (!orgPromoInput || !orgPromoPreview) return;
    const v = (orgPromoInput.value || '').trim();
    orgPromoPreview.src = v || @json(asset('images/home/mega-promo.jpg'));
    orgPromoPreview.style.opacity = '1';
  }
  if (orgPromoInput) {
    orgPromoInput.addEventListener('input', syncOrgPromoPreview);
    orgPromoInput.addEventListener('change', syncOrgPromoPreview);
  }
  const btnUpOrg = document.getElementById('btn-up-org-promo');
  if (btnUpOrg) {
    btnUpOrg.addEventListener('click', async () => {
      try {
        const fileEl = document.getElementById('up_org_promo');
        if (!fileEl || !fileEl.files || !fileEl.files[0]) {
          toast('ابتدا یک تصویر انتخاب کنید', true);
          return;
        }
        const fd = new FormData();
        fd.append('file', fileEl.files[0]);
        const res = await fetch(ROUTES.upload, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
          body: fd,
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.message || 'آپلود ناموفق');
        if (orgPromoInput) orgPromoInput.value = data.url || data.path || '';
        syncOrgPromoPreview();
        toast('تصویر پیشنهاد سازمانی آپلود شد');
      } catch (e) {
        toast(e.message || 'آپلود ناموفق', true);
      }
    });
  }

  document.querySelectorAll('.mm-media').forEach(box => {
    box.querySelectorAll('button[data-url]').forEach(btn => {
      btn.addEventListener('click', () => {
        const field = document.getElementById('f_' + box.dataset.target);
        if (field) field.value = btn.dataset.url;
        updatePreviews();
        toast('از کتابخانه انتخاب شد');
      });
    });
  });

  window.__mediaPick = function(items, field){
    if (!items || !items.length) return;
    field = field || 'f_image_url';
    const el = document.getElementById(field);
    const first = items[0];
    if (el) el.value = first.url || first.value || '';
    updatePreviews();
    if (field === 'org_promo_image') syncOrgPromoPreview();
    toast('از فایل‌منیجر انتخاب شد');
  };

  if (selectedId) fillForm(findNode(tree, selectedId));
  else fillForm(null);
  render();
})();
</script>
@endsection
