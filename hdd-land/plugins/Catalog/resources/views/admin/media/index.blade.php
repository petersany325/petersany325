@extends('layouts.admin')
@section('title','کتابخانه فایل')
@section('content')
@php
  $s = $s ?? [];
  $path = $path ?? '';
  $picker = !empty($picker);
  $pickField = $pickField ?? '';
  $pickAs = $pickAs ?? 'path';
  $pickMulti = !empty($pickMulti);
  $baseUrl = url('/admin/media');
  $pickQs = $picker ? ('&picker=1'.($pickField !== '' ? '&field='.urlencode($pickField) : '').'&as='.urlencode($pickAs).($pickMulti ? '&multi=1' : '')) : '';
@endphp
<style>
.fm{display:grid;grid-template-columns:220px 1fr;gap:1rem;align-items:start}
.fm-side,.fm-main{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow)}
.fm-side{padding:1rem}
.fm-main{padding:1rem;min-height:520px}
.fm-crumbs{display:flex;flex-wrap:wrap;gap:.35rem;align-items:center;margin-bottom:.9rem;font-size:.9rem}
.fm-crumbs a{color:var(--brand);font-weight:700}
.fm-toolbar{display:flex;flex-wrap:wrap;gap:.45rem;margin-bottom:1rem}
.fm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:.75rem}
.fm-item{border:1px solid var(--line);border-radius:14px;padding:.55rem;cursor:pointer;background:#fafbfc;position:relative;user-select:none}
.fm-item:hover{border-color:#f0b09d}
.fm-item.selected{outline:2px solid var(--brand);background:#fff7f4}
.fm-thumb{height:96px;border-radius:10px;background:#eef1f6;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:.45rem}
.fm-thumb img,.fm-thumb video{width:100%;height:100%;object-fit:cover}
.fm-name{font-size:.8rem;font-weight:700;word-break:break-word;line-height:1.35}
.fm-meta{font-size:.72rem;color:var(--muted);margin-top:.2rem}
.fm-folder .fm-thumb{font-size:2rem}
.fm-check{position:absolute;top:.4rem;right:.4rem}
.fm-actions{display:flex;gap:.35rem;margin-top:.55rem;position:relative;z-index:2}
.fm-action{border:1px solid var(--line);background:#fff;border-radius:7px;padding:.25rem .45rem;font:inherit;font-size:.72rem;cursor:pointer}
.fm-action:hover{border-color:var(--brand);color:var(--brand)}
.fm-action-danger{color:#a32012}
.fm-empty{padding:2rem;text-align:center;color:var(--muted)}
.fm-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:50;align-items:center;justify-content:center;padding:1rem}
.fm-modal.show{display:flex}
.fm-dialog{background:#fff;border-radius:16px;padding:1.1rem;width:min(420px,100%);box-shadow:0 20px 50px rgba(0,0,0,.2)}
@media(max-width:900px){.fm{grid-template-columns:1fr}}
</style>

<div class="row" style="justify-content:space-between;margin-bottom:1rem">
  <div>
    <h1 style="margin:0">کتابخانه فایل / فایل‌منیجر</h1>
    <p class="muted" style="margin:.35rem 0 0">پوشه‌بندی، آپلود عکس و ویدیو، انتقال، کپی و حذف</p>
    @if(!empty($purgedBroken))
      <p class="muted" style="margin:.4rem 0 0;color:#b45309">{{ (int)$purgedBroken }} پوشه خالی با نام خراب حذف و کتابخانه تعمیر شد.</p>
    @endif
  </div>
  <div class="row">
    <a class="btn btn-outline" href="{{ url('/admin/media/settings') }}">تنظیمات دسترسی</a>
    @if($picker)
      <button type="button" class="btn btn-primary" id="btnPick">انتخاب و بازگشت</button>
    @endif
  </div>
</div>

<div class="fm">
  <aside class="fm-side">
    <strong>میانبر</strong>
    <div style="margin-top:.7rem;display:grid;gap:.35rem">
      <a class="btn btn-outline btn-sm" href="{{ $baseUrl }}{{ $picker ? '?picker=1'.($pickField !== '' ? '&field='.urlencode($pickField) : '').'&as='.urlencode($pickAs).($pickMulti ? '&multi=1' : '') : '' }}">ریشه کتابخانه</a>
      <a class="btn btn-outline btn-sm" href="{{ url('/admin/categories') }}">دسته‌بندی‌ها</a>
      <a class="btn btn-outline btn-sm" href="{{ url('/admin/products') }}">محصولات</a>
    </div>
    <hr style="border:none;border-top:1px solid var(--line);margin:1rem 0">
    <div class="muted" style="font-size:.82rem;line-height:1.7">
      حداکثر آپلود: {{ (int)($s['max_upload_kb'] ?? 5120) }}KB<br>
      فرمت‌ها: {{ $s['allowed_extensions'] ?? '' }}
    </div>
  </aside>

  <section class="fm-main">
    <div class="fm-crumbs">
      @foreach($crumbs as $i => $c)
        @if($i>0)<span class="muted">/</span>@endif
        <a href="{{ $baseUrl }}?path={{ urlencode($c['path']) }}{{ $pickQs }}">{{ $c['label'] }}</a>
      @endforeach
    </div>

    <div class="fm-toolbar">
      @if(!empty($s['allow_mkdir']))
        <button type="button" class="btn btn-dark btn-sm" id="btnNewFolder">پوشه جدید</button>
      @endif
      @if(!empty($s['allow_upload']))
        <label class="btn btn-primary btn-sm" style="margin:0;cursor:pointer">
          آپلود در این پوشه
          <input type="file" id="fileInput" multiple hidden accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov,.avi,.pdf,.zip">
        </label>
      @endif
      @if(!empty($s['allow_move']))
        <button type="button" class="btn btn-outline btn-sm" id="btnMove">انتقال</button>
      @endif
      @if(!empty($s['allow_copy']))
        <button type="button" class="btn btn-outline btn-sm" id="btnCopy">کپی</button>
      @endif
      @if(!empty($s['allow_rename']))
        <button type="button" class="btn btn-outline btn-sm" id="btnRename">تغییر نام</button>
      @endif
      @if(!empty($s['allow_delete']))
        <button type="button" class="btn btn-outline btn-sm" id="btnDelete" style="color:#a32012">حذف</button>
      @endif
    </div>

    <div class="fm-grid" id="fmGrid">
      @foreach($folders as $f)
        <div class="fm-item fm-folder" data-path="{{ $f['path'] }}" data-type="folder" data-name="{{ $f['name'] }}">
          <input type="checkbox" class="fm-check" value="{{ $f['path'] }}">
          <a href="{{ $baseUrl }}?path={{ urlencode($f['path']) }}{{ $pickQs }}" style="text-decoration:none;color:inherit">
            <div class="fm-thumb">📁</div>
            <div class="fm-name">{{ $f['name'] }}</div>
            <div class="fm-meta">{{ !empty($f['broken']) ? 'پوشه آسیب‌دیده — حذف یا تغییر نام کنید' : 'پوشه' }}</div>
          </a>
          <div class="fm-actions">
            @if(!empty($s['allow_rename']))
              <button type="button" class="fm-action js-folder-rename">تغییر نام</button>
            @endif
            @if(!empty($s['allow_delete']))
              <button type="button" class="fm-action fm-action-danger js-folder-delete">حذف پوشه</button>
            @endif
          </div>
        </div>
      @endforeach
      @foreach($files as $f)
        <div class="fm-item fm-file" data-path="{{ $f['path'] }}" data-type="file" data-name="{{ $f['name'] }}" data-url="{{ $f['url'] }}">
          <input type="checkbox" class="fm-check" value="{{ $f['path'] }}">
          <div class="fm-thumb">
            @if(!empty($f['is_image']))
              <img src="{{ $f['url'] }}" alt="">
            @elseif(!empty($f['is_video']))
              <video src="{{ $f['url'] }}" muted preload="metadata"></video>
            @else
              📄
            @endif
          </div>
          <div class="fm-name">{{ $f['name'] }}</div>
          <div class="fm-meta">{{ strtoupper($f['ext']) }} · {{ number_format(($f['size'] ?? 0)/1024,1) }} KB</div>
          @if(!empty($s['allow_delete']))
            <div class="fm-actions">
              <button type="button" class="fm-action fm-action-danger js-file-delete">حذف تصویر</button>
            </div>
          @endif
        </div>
      @endforeach
      @if(empty($folders) && empty($files))
        <div class="fm-empty" style="grid-column:1/-1">این پوشه خالی است. پوشه بسازید یا فایل آپلود کنید.</div>
      @endif
    </div>
  </section>
</div>

<div class="fm-modal" id="modal">
  <div class="fm-dialog">
    <h3 id="modalTitle" style="margin-top:0">عملیات</h3>
    <div id="modalBody"></div>
    <div class="row" style="justify-content:flex-end;margin-top:1rem">
      <button type="button" class="btn btn-outline" id="modalCancel">انصراف</button>
      <button type="button" class="btn btn-primary" id="modalOk">تأیید</button>
    </div>
  </div>
</div>

<form id="postForm" method="post" style="display:none">@csrf</form>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const CUR_PATH = @json($path);
const PICKER = @json($picker);
const BASE = @json($baseUrl);
const ROOT_FOLDER = @json($s['root_folder'] ?? 'media');
const PICK_FIELD = @json($pickField);
const PICK_AS = @json($pickAs);
const PICK_MULTI = @json($pickMulti);

function selected(){
  return [...document.querySelectorAll('.fm-check:checked')].map(c => c.value);
}
function openModal(title, html){
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalBody').innerHTML = html;
  document.getElementById('modal').classList.add('show');
  return new Promise((resolve)=>{
    const ok = document.getElementById('modalOk');
    const cancel = document.getElementById('modalCancel');
    const close = (v)=>{ document.getElementById('modal').classList.remove('show'); ok.onclick=null; cancel.onclick=null; resolve(v); };
    cancel.onclick = ()=>close(null);
    ok.onclick = ()=>close(true);
  });
}
async function post(url, data){
  const fd = new FormData();
  fd.append('_token', CSRF);
  Object.entries(data).forEach(([k,v])=>{
    if(Array.isArray(v)) v.forEach(x=>fd.append(k+'[]', x));
    else fd.append(k, v);
  });
  let res;
  try {
    res = await fetch(url, {method:'POST', body:fd, headers:{'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'}});
  } catch (_) {
    alert('ارتباط با سرور برقرار نشد. دوباره تلاش کنید.');
    return false;
  }
  const json = await res.json().catch(()=>({ok:false,message:'پاسخ نامعتبر از سرور'}));
  if(!json.ok){ alert(json.message||'خطا'); return false; }
  location.reload();
  return true;
}

document.getElementById('btnNewFolder')?.addEventListener('click', async ()=>{
  const ok = await openModal('پوشه جدید', '<label>نام پوشه<input id="m_name" style="width:100%;margin-top:.4rem"></label>');
  if(!ok) return;
  const name = document.getElementById('m_name')?.value?.trim();
  if(!name) return;
  await post(BASE+'/mkdir', {path: CUR_PATH, name});
});

async function clientWebp(file){
  if(!/^image\/(jpeg|png|webp)$/i.test(file.type || '')) return file;
  try {
    const bitmap = await createImageBitmap(file);
    const ratio = Math.min(1, 1920 / bitmap.width, 1920 / bitmap.height);
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(bitmap.width * ratio));
    canvas.height = Math.max(1, Math.round(bitmap.height * ratio));
    canvas.getContext('2d', {alpha:true}).drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close?.();
    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/webp', .80));
    if(!blob || blob.type !== 'image/webp') return file;
    const base = file.name.replace(/\.[^.]+$/, '');
    return new File([blob], base+'.webp', {type:'image/webp', lastModified:file.lastModified});
  } catch (_) { return file; }
}

document.getElementById('fileInput')?.addEventListener('change', async (e)=>{
  const files = e.target.files;
  if(!files?.length) return;
  const optimizedFiles = await Promise.all([...files].map(clientWebp));
  const fd = new FormData();
  fd.append('_token', CSRF);
  fd.append('path', CUR_PATH);
  optimizedFiles.forEach(f=>fd.append('files[]', f));
  let res;
  try {
    res = await fetch(BASE+'/upload', {method:'POST', body:fd, headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
  } catch (_) {
    alert('آپلود به سرور نرسید. اتصال اینترنت را بررسی و دوباره تلاش کنید.');
    e.target.value = '';
    return;
  }
  const json = await res.json().catch(()=>({ok:false,message:'پاسخ نامعتبر از سرور'}));
  e.target.value = '';
  if(!json.ok){ alert(json.message||'خطا'); return; }
  location.reload();
});

document.getElementById('btnDelete')?.addEventListener('click', async ()=>{
  const items = selected();
  if(!items.length){ alert('موردی انتخاب نشده'); return; }
  const ok = await openModal('حذف', '<p>'+items.length+' مورد حذف شود؟</p>');
  if(!ok) return;
  await post(BASE+'/delete', {path: CUR_PATH, items});
});

document.getElementById('btnRename')?.addEventListener('click', async ()=>{
  const items = selected();
  if(items.length!==1){ alert('فقط یک مورد را انتخاب کنید'); return; }
  const el = document.querySelector('.fm-item[data-path="'+CSS.escape(items[0])+'"]');
  const cur = el?.dataset.name || '';
  const ok = await openModal('تغییر نام', '<label>نام جدید<input id="m_name" value="'+cur.replace(/"/g,'&quot;')+'" style="width:100%;margin-top:.4rem"></label>');
  if(!ok) return;
  const name = document.getElementById('m_name')?.value?.trim();
  if(!name) return;
  await post(BASE+'/rename', {from: items[0], to: name});
});

async function transfer(kind){
  const items = selected();
  if(!items.length){ alert('موردی انتخاب نشده'); return; }
  const ok = await openModal(kind==='move'?'انتقال':'کپی', '<label>مسیر مقصد (نسبت به ریشه)<input id="m_dest" placeholder="مثلاً products/hdd" style="width:100%;margin-top:.4rem" value="'+CUR_PATH+'"></label><p class="muted" style="font-size:.82rem">خالی = ریشه کتابخانه</p>');
  if(!ok) return;
  const destination = document.getElementById('m_dest')?.value?.trim() || '';
  await post(BASE+'/'+(kind==='move'?'move':'copy'), {destination, items});
}
document.getElementById('btnMove')?.addEventListener('click', ()=>transfer('move'));
document.getElementById('btnCopy')?.addEventListener('click', ()=>transfer('copy'));

document.querySelectorAll('.fm-item').forEach(item=>{
  item.addEventListener('click', (e)=>{
    if(e.target.classList.contains('fm-check') || e.target.closest('a')) return;
    const chk = item.querySelector('.fm-check');
    chk.checked = !chk.checked;
    item.classList.toggle('selected', chk.checked);
  });
  item.querySelector('.fm-check')?.addEventListener('change', (e)=>{
    item.classList.toggle('selected', e.target.checked);
  });
});

document.querySelectorAll('.js-folder-rename, .js-folder-delete, .js-file-delete').forEach(button=>{
  button.addEventListener('click', (e)=>{
    e.preventDefault();
    e.stopPropagation();
    const item = button.closest('.fm-item');
    document.querySelectorAll('.fm-check').forEach(check=>{
      check.checked = false;
      check.closest('.fm-item')?.classList.remove('selected');
    });
    const check = item?.querySelector('.fm-check');
    if(!check) return;
    check.checked = true;
    item.classList.add('selected');
    const isDelete = button.classList.contains('js-folder-delete') || button.classList.contains('js-file-delete');
    document.getElementById(isDelete ? 'btnDelete' : 'btnRename')?.click();
  });
});

document.getElementById('btnPick')?.addEventListener('click', ()=>{
  let items = selected().map(p=>{
    const el = document.querySelector('.fm-item[data-path="'+CSS.escape(p)+'"]');
    return el && el.dataset.type==='file' ? {path: ROOT_FOLDER+'/'+p, url: el.dataset.url, name: el.dataset.name} : null;
  }).filter(Boolean);
  if(!PICK_MULTI) items = items.slice(0,1);
  if(!items.length){ alert('یک فایل انتخاب کنید'); return; }
  if(PICK_AS === 'url'){
    items = items.map(i => ({...i, value: i.url}));
  } else {
    items = items.map(i => ({...i, value: i.path}));
  }
  if(window.opener && typeof window.opener.__mediaPick === 'function'){
    window.opener.__mediaPick(items, PICK_FIELD || null, PICK_AS);
    window.close();
  } else {
    alert((PICK_AS==='url'?'آدرس':'مسیر')+':\n'+items.map(i=>i.value).join('\n'));
  }
});
</script>
@endsection
