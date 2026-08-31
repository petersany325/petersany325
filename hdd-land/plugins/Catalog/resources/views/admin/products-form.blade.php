@extends('layouts.admin')
@section('title', $product->exists ? 'ویرایش محصول' : 'محصول جدید')
@section('content')
@php
  $cs = old('card_settings', $product->cardSettings());
  $warrantyLabels = \Plugins\Catalog\src\Models\Product::WARRANTY_TYPES;
  $conditionLabels = \Plugins\Catalog\src\Models\Product::CONDITIONS;
  $stockLabels = \Plugins\Catalog\src\Models\Product::STOCK_STATUSES;
  $partLabels = \Plugins\Catalog\src\Models\Product::PART_TYPES;
@endphp
<style>
.woo-layout{display:grid;grid-template-columns:1fr 340px;gap:1.1rem;align-items:start}
.woo-tabs{display:grid;grid-template-columns:repeat(3,1fr);gap:.55rem;margin:1.2rem 0;border:0;padding:0}
.woo-tab{border:1px solid var(--line);background:#fff;border-radius:14px;padding:.7rem .75rem;cursor:pointer;font:inherit;color:var(--muted);display:grid;grid-template-columns:34px 1fr;gap:.55rem;text-align:right;align-items:center}
.woo-tab b{width:34px;height:34px;border-radius:10px;background:#f1f5f9;color:#475569;display:grid;place-items:center;font-size:.92rem}
.woo-tab span{font-weight:800;color:#1f2937;font-size:.85rem}.woo-tab small{display:block;font-size:.68rem;color:#94a3b8;margin-top:.1rem}
.woo-tab.active{background:#fff7f4;color:#fff;border-color:#ef8b6d;box-shadow:0 0 0 3px rgba(226,61,18,.1)}
.woo-tab.active b{background:var(--brand);color:#fff}.woo-tab.active span{color:var(--brand)}
.woo-pane{display:none}.woo-pane.active{display:block}
.editor-progress{display:grid;grid-template-columns:1fr auto;gap:.75rem;align-items:center;background:#fff;border:1px solid var(--line);border-radius:14px;padding:.75rem 1rem;margin-bottom:1rem}
.editor-progress-track{height:8px;background:#edf1f5;border-radius:999px;overflow:hidden;margin-top:.35rem}.editor-progress-bar{height:100%;width:0;background:linear-gradient(90deg,#e23d12,#fb923c);transition:width .25s}
.editor-savebar{position:sticky;bottom:.65rem;z-index:12;background:rgba(17,24,39,.94);backdrop-filter:blur(10px);padding:.7rem;border-radius:14px;display:flex;justify-content:space-between;align-items:center;gap:.6rem;margin-top:1rem;color:#fff;box-shadow:0 15px 35px rgba(15,23,42,.25)}
.side-box{background:#fff;border:1px solid var(--line);border-radius:16px;padding:1rem;margin-bottom:1rem;box-shadow:var(--shadow)}
.card-opt{display:flex;justify-content:space-between;align-items:center;gap:.75rem;padding:.45rem 0;border-bottom:1px solid #f0f2f5;font-size:.9rem}
.card-opt:last-child{border-bottom:0}
.preview-sticky{position:sticky;top:1rem}
.preview-label{font-size:.8rem;color:var(--muted);margin-bottom:.55rem;font-weight:700}
@media(max-width:1100px){.woo-layout{grid-template-columns:1fr}.preview-sticky{position:static}}
@media(max-width:760px){.woo-tabs{grid-template-columns:1fr 1fr}.editor-savebar{bottom:.35rem}}
</style>

<div class="row" style="justify-content:space-between;margin-bottom:1rem">
  <div>
    <h1 style="margin:0">{{ $product->exists ? 'ویرایش محصول' : 'افزودن محصول' }}</h1>
    <p class="muted" style="margin:.35rem 0 0">عکس، سریال، گارانتی و گزینه‌های کادر را تنظیم کنید — پیش‌نمایش زنده سمت چپ</p>
  </div>
  <a class="btn btn-outline" href="{{ route('admin.products.index') }}">بازگشت به لیست</a>
</div>

<form method="post" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update',$product) : route('admin.products.store') }}" id="productForm">
@csrf
@if($product->exists) @method('PUT') @endif

<div class="woo-layout">
  <div class="panel">
    <div class="editor-progress">
      <div><strong>تکمیل اطلاعات کالا</strong><div class="editor-progress-track"><div class="editor-progress-bar" id="editorProgressBar"></div></div></div>
      <strong id="editorProgressText">۰٪</strong>
    </div>
    <label>نام محصول<input name="name" id="f_name" value="{{ old('name',$product->name) }}" required placeholder="مثلاً اس‌اس‌دی سامسونگ ۱ ترابایت"></label>
    <label style="margin-top:.8rem">توضیح کوتاه (روی کادر)<textarea name="short_description" id="f_short" rows="2">{{ old('short_description',$product->short_description) }}</textarea></label>
    <label style="margin-top:.8rem">توضیحات بیشتر (صفحه محصول)
      <textarea name="description" rows="6">{{ old('description',$product->description) }}</textarea>
      <small class="muted" style="display:block;margin-top:.35rem">می‌توانید HTML ساده (مثل p / ul / strong) بنویسید. رندر HTML از <a href="{{ url('/admin/products/display-settings') }}">تنظیمات نمایش محصول</a> کنترل می‌شود.</small>
    </label>

    <div class="woo-tabs" style="margin-top:1.2rem">
      <button type="button" class="woo-tab active" data-tab="general"><b>₮</b><div><span>قیمت و مالی</span><small>فروش، خرید و سود</small></div></button>
      <button type="button" class="woo-tab" data-tab="inventory"><b>▦</b><div><span>انبار و شناسه</span><small>SKU و موجودی</small></div></button>
      <button type="button" class="woo-tab" data-tab="warranty" id="tabBtnWarranty"><b>⌁</b><div><span>گارانتی و سریال</span><small>رهگیری و بارکدخوان</small></div></button>
      <button type="button" class="woo-tab" data-tab="attrs"><b>≡</b><div><span>مشخصات فنی</span><small>ویژگی‌های کالا</small></div></button>
      <button type="button" class="woo-tab" data-tab="card"><b>▤</b><div><span>نمایش فروشگاه</span><small>کنترل کارت کالا</small></div></button>
      <button type="button" class="woo-tab" data-tab="advanced"><b>⚙</b><div><span>تنظیمات پیشرفته</span><small>آدرس و ترتیب</small></div></button>
    </div>

    <div class="woo-pane active" id="tab-general">
      <div class="form-2">
        <label>قیمت فروش قطعه (تومان)
          <input type="number" name="price" id="f_price" value="{{ old('price',$product->price ?? 0) }}" required min="0">
        </label>
        <label>قیمت قبل / ویژه
          <input type="number" name="compare_price" id="f_compare" value="{{ old('compare_price',$product->compare_price) }}" placeholder="اختیاری">
        </label>
        <label>قیمت خرید قطعه (تومان)
          <input type="number" name="cost_price" id="f_cost" value="{{ old('cost_price',$product->cost_price) }}" placeholder="بهای تمام‌شده برای محاسبه سود" min="0">
        </label>
        <label>دسته
          <select name="category_id">
            <option value="">— بدون دسته —</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" @selected(old('category_id',$product->category_id)==$cat->id)>{{ $cat->name }}</option>
            @endforeach
          </select>
        </label>
        <label>برند<input name="brand" id="f_brand" value="{{ old('brand',$product->brand) }}" placeholder="Samsung, WD..."></label>
      </div>
      <div id="profitBox" style="margin-top:1rem;padding:1rem 1.1rem;border-radius:14px;border:1px solid #86efac;background:linear-gradient(135deg,#ecfdf5,#f0f9ff)">
        <strong style="display:block;margin-bottom:.45rem">سود فروش این قطعه</strong>
        <div class="row" style="gap:1.5rem;flex-wrap:wrap">
          <div><span class="muted" style="font-size:.8rem">سود واحد</span><div id="profitAmt" style="font-size:1.25rem;font-weight:800;color:#047857">—</div></div>
          <div><span class="muted" style="font-size:.8rem">حاشیه سود</span><div id="profitPct" style="font-size:1.25rem;font-weight:800">—</div></div>
        </div>
        <p class="muted" style="margin:.55rem 0 0;font-size:.8rem">کمیسیون کارمند روی همین سود محاسبه و به کیف پولش واریز می‌شود.</p>
      </div>
    </div>

    <div class="woo-pane" id="tab-inventory">
      <div class="form-2">
        <label>SKU / کد کالا<input name="sku" value="{{ old('sku',$product->sku) }}"></label>
        <label>سریال نمایشی روی کادر<input name="display_serial" id="f_serial" value="{{ old('display_serial',$product->display_serial) }}" placeholder="HDD-XXXX"></label>
        <label>وضعیت انبار
          <select name="stock_status" id="f_stock_status">
            @foreach($stockLabels as $key=>$label)
              <option value="{{ $key }}" @selected(old('stock_status',$product->stock_status ?? 'instock')===$key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <label style="display:flex;gap:.5rem;align-items:center;font-weight:500"><input type="checkbox" name="manage_stock" value="1" id="f_manage" @checked(old('manage_stock',$product->manage_stock ?? true))> مدیریت موجودی</label>
        <label>تعداد موجودی<input type="number" name="stock" id="f_stock" value="{{ old('stock',$product->stock ?? 0) }}" required min="0"></label>
        <label>آستانه موجودی کم<input type="number" name="low_stock_amount" value="{{ old('low_stock_amount',$product->low_stock_amount) }}" min="0"></label>
      </div>
    </div>

    @php
      $companies = $warrantyCompanies ?? collect();
      $hasW = (bool) old('has_warranty', $product->has_warranty ?? ($product->warranty_type && $product->warranty_type !== 'none'));
      $reqSerial = (bool) old('requires_serial', $product->requires_serial ?? false);
      if (!empty($withSerialFocus)) { $hasW = true; }
    @endphp
    <div class="woo-pane" id="tab-warranty">
      <div id="serial-warranty" style="padding:1rem;border:1px solid var(--line);border-radius:16px;background:#fafbfc">
        <h3 style="margin:0 0 .75rem;font-size:1.05rem">۱) گارانتی می‌خواهید؟</h3>
        <label>گارانتی محصول
          <select name="has_warranty" id="f_has_warranty">
            <option value="0" @selected(!$hasW)>خیر — بدون گارانتی</option>
            <option value="1" @selected($hasW)>بله — دارای گارانتی</option>
          </select>
        </label>

        <div id="warrantyBox" style="margin-top:1rem;{{ $hasW ? '' : 'display:none' }}">
          <h3 style="margin:0 0 .75rem;font-size:1.05rem">۲) شرکت گارانتی را انتخاب کنید</h3>
          @if($companies->isEmpty())
            <p class="muted" style="margin:0 0 .75rem">هنوز شرکتی تعریف نشده. <a href="{{ url('/admin/warranty-companies') }}" target="_blank">ثبت شرکت گارانتی</a></p>
          @else
            <div class="form-2">
              <label>لیست شرکت‌های گارانتی
                <select name="warranty_company" id="f_warranty_company">
                  <option value="">— انتخاب کنید —</option>
                  @foreach($companies as $c)
                    <option value="{{ $c->name }}" data-months="{{ (int)$c->default_months }}" @selected(old('warranty_company',$product->warranty_company)===$c->name)>{{ $c->name }} ({{ (int)$c->default_months }} ماه)</option>
                  @endforeach
                </select>
              </label>
              <label>مدت گارانتی (ماه)<input type="number" name="warranty_months" id="f_wmonths" value="{{ old('warranty_months',$product->warranty_months) }}" min="0" max="120"></label>
              <label>نوع گارانتی
                <select name="warranty_type" id="f_warranty">
                  @foreach($warrantyLabels as $key=>$label)
                    @if($key !== 'none')
                      <option value="{{ $key }}" @selected(old('warranty_type',$product->warranty_type ?? 'official')===$key)>{{ $label }}</option>
                    @endif
                  @endforeach
                </select>
              </label>
            </div>
          @endif

          <h3 style="margin:1.2rem 0 .75rem;font-size:1.05rem">۳) فروش با سریال؟</h3>
          <p class="muted" style="margin:0 0 .55rem;font-size:.85rem">فقط وقتی گارانتی فعال است می‌توانید سریال ثبت کنید.</p>
          <label>سریال محصول
            <select name="requires_serial" id="f_requires_serial">
              <option value="0" @selected(!$reqSerial)>خیر — بدون سریال</option>
              <option value="1" @selected($reqSerial)>بله — موجودی از سریال‌ها</option>
            </select>
          </label>
          <div id="serialHint" style="margin-top:.85rem;padding:.75rem 1rem;border-radius:12px;background:#fff7f4;border:1px solid #f0b09d;font-size:.88rem;line-height:1.7;{{ $reqSerial && $hasW ? '' : 'display:none' }}">
            بعد از ذخیره، صفحه <b>ورود سریال</b> باز می‌شود و سریال‌ها با شرکت گارانتی انتخاب‌شده ثبت می‌شوند (دستی / بارکد / اکسل).
          </div>
          @if($product->exists && $reqSerial)
            <div style="margin-top:.85rem">
              <a class="btn btn-dark btn-sm" href="{{ url('/admin/products/'.$product->id.'/serials') }}">مدیریت سریال‌های این محصول</a>
            </div>
          @endif
        </div>
      </div>
    </div>

    <div class="woo-pane" id="tab-attrs">
      <div class="form-2">
        <label>نوع قطعه
          <select name="part_type" id="f_part">
            <option value="">—</option>
            @foreach($partLabels as $key=>$label)
              <option value="{{ $key }}" @selected(old('part_type',$product->part_type)===$key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <label>وضعیت کالا
          <select name="condition" id="f_condition">
            @foreach($conditionLabels as $key=>$label)
              <option value="{{ $key }}" @selected(old('condition',$product->condition ?? 'new')===$key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <label>ظرفیت<input name="capacity" id="f_capacity" value="{{ old('capacity',$product->capacity) }}" placeholder="1TB"></label>
        <label>رابط<input name="interface" value="{{ old('interface',$product->interface) }}" placeholder="SATA / NVMe"></label>
        <label>فرم‌فکتور<input name="form_factor" value="{{ old('form_factor',$product->form_factor) }}" placeholder="2.5 / M.2"></label>
      </div>
      <h3 style="margin:1.2rem 0 .5rem">ویژگی‌های سفارشی</h3>
      @php
        $specs = old('spec_keys')
          ? collect(old('spec_keys'))->map(fn($k,$i)=>['k'=>$k,'v'=>old('spec_values')[$i] ?? ''])
          : collect($product->specs ?? [])->map(fn($v,$k)=>['k'=>$k,'v'=>$v])->values();
        if ($specs->isEmpty()) { $specs = collect([['k'=>'','v'=>''],['k'=>'','v'=>'']]); }
      @endphp
      @foreach($specs as $row)
        <div class="form-2" style="margin-bottom:.5rem">
          <input name="spec_keys[]" value="{{ $row['k'] }}" placeholder="نام ویژگی">
          <input name="spec_values[]" value="{{ $row['v'] }}" placeholder="مقدار">
        </div>
      @endforeach
      <div class="form-2">
        <input name="spec_keys[]" placeholder="ویژگی جدید">
        <input name="spec_values[]" placeholder="مقدار">
      </div>
    </div>

    <div class="woo-pane" id="tab-card">
      <p class="muted" style="margin:0 0 .8rem">هر محصول می‌تواند بخش‌های کادر فروشگاه را جداگانه روشن/خاموش کند.</p>
      @foreach([
        'show_short_desc'=>'توضیح کوتاه',
        'show_meta'=>'برند / ظرفیت / نوع قطعه',
        'show_warranty'=>'وضعیت گارانتی',
        'show_condition'=>'وضعیت کالا (نو/استوک...)',
        'show_serial'=>'سریال روی کادر',
        'show_stock'=>'موجودی روی عکس',
        'show_add_cart'=>'دکمه افزودن به سبد',
        'show_buy_now'=>'دکمه پرداخت / خرید',
        'show_preorder'=>'دکمه پیش‌خرید (ناموجود)',
      ] as $key=>$lab)
        <label class="card-opt">
          <span>{{ $lab }}</span>
          <input type="checkbox" class="card-set" name="card_settings[{{ $key }}]" value="1" data-key="{{ $key }}" @checked(!empty($cs[$key]))>
        </label>
      @endforeach
    </div>

    <div class="woo-pane" id="tab-advanced">
      <div class="form-2">
        <label>اسلاگ<input name="slug" value="{{ old('slug',$product->slug) }}"></label>
        <label>ترتیب منو<input type="number" name="menu_order" value="{{ old('menu_order',$product->menu_order ?? 0) }}"></label>
      </div>
    </div>
    <div class="editor-savebar">
      <span id="editorSaveHint">اطلاعات کالا را بررسی و ذخیره کنید.</span>
      <div class="row" style="gap:.4rem">
        <button class="btn btn-outline btn-sm" type="submit" name="save_as_draft" value="1" style="color:#fff;border-color:#6b7280">ذخیره پیش‌نویس</button>
        <button class="btn btn-primary btn-sm" type="submit">ذخیره کالا</button>
      </div>
    </div>
  </div>

  <aside class="preview-sticky">
    <div class="side-box">
      <strong>انتشار</strong>
      <label style="margin-top:.7rem">وضعیت
        <select name="status">
          @foreach(\Plugins\Catalog\src\Models\Product::STATUSES as $key=>$label)
            <option value="{{ $key }}" @selected(old('status',$product->status ?? 'publish')===$key)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label style="display:flex;gap:.5rem;align-items:center;font-weight:500;margin-top:.7rem">
        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured',$product->is_featured ?? false))> محصول ویژه
      </label>
      <button class="btn btn-primary" type="submit" style="width:100%;margin-top:1rem">ذخیره محصول</button>
      <button class="btn btn-dark" type="submit" name="open_serials" value="1" id="btnSaveSerials" style="width:100%;margin-top:.5rem;{{ $hasW && $reqSerial ? '' : 'display:none' }}">
        ذخیره و ورود سریال
      </button>
    </div>

    <div class="side-box">
      <div class="preview-label">پیش‌نمایش زنده کادر فروشگاه</div>
      <article class="shop-card" id="liveCard" style="max-width:100%">
        <div class="shop-card__media">
          <span class="shop-card__img-wrap">
            <img id="liveImg" src="{{ $product->image ? $product->imageUrl() : 'https://placehold.co/400x400/f4f6f9/e23d12?text=Photo' }}" alt="" width="400" height="400">
          </span>
          <span class="shop-card__ribbon" id="liveRibbon" style="display:none">٪</span>
          <span class="shop-card__stock shop-card__stock--instock" id="liveStock">موجود · 0 عدد</span>
        </div>
        <div class="shop-card__body">
          <div class="shop-card__meta" id="liveMeta"></div>
          <h3 class="shop-card__title"><a href="#" id="liveTitle">نام محصول</a></h3>
          <p class="shop-card__desc" id="liveDesc"></p>
          <div class="shop-card__badges" id="liveBadges"></div>
          <div class="shop-card__serial" id="liveSerial" style="display:none"><span>سریال</span><code id="liveSerialCode"></code></div>
          <div class="shop-card__price-row">
            <div class="shop-card__price" id="livePrice">۰ تومان</div>
            <div class="shop-card__old" id="liveOld" style="display:none"></div>
          </div>
          <div class="shop-card__actions">
            <button class="btn btn-dark btn-sm" type="button" id="btnCart">افزودن به سبد</button>
            <button class="btn btn-primary btn-sm" type="button" id="btnBuy">پرداخت / خرید</button>
          </div>
        </div>
      </article>
    </div>

    <div class="side-box">
      <strong>تصویر شاخص</strong>
      <img id="feat-preview" src="{{ $product->image ? $product->imageUrl() : '' }}" alt="" style="width:100%;border-radius:12px;margin:.7rem 0;border:1px solid var(--line);{{ $product->image ? '' : 'display:none' }}">
      <p class="muted" style="font-size:.82rem;margin:.4rem 0">آپلود از کامپیوتر:</p>
      <input type="file" name="image" id="f_image" accept="image/*">
      <input type="hidden" name="clear_image" id="clear_image" value="0">
      <button type="button" class="btn btn-outline" id="btnClearImage" style="width:100%;margin:.65rem 0;color:#b42318;border-color:#fecaca;background:#fff7f7">حذف تصویر محصول</button>
      <input type="hidden" name="image_library_path" id="image_library_path" value="">
      <button type="button" class="btn btn-primary" data-media-pick data-field="image_library_path" data-as="path" data-title="انتخاب تصویر محصول" style="width:100%;margin:.75rem 0 .5rem">انتخاب تصویر محصول از کتابخانه</button>
      <div class="media-lib" style="display:grid;grid-template-columns:repeat(3,1fr);gap:.4rem;max-height:200px;overflow:auto">
        @foreach(($mediaLibrary ?? []) as $m)
          <button type="button" class="lib-pick" data-path="{{ $m['path'] }}" data-url="{{ $m['url'] }}" style="padding:0;border:1px solid var(--line);border-radius:8px;overflow:hidden;background:#fff;cursor:pointer">
            <img src="{{ $m['url'] }}" alt="" style="width:100%;height:64px;object-fit:cover;display:block">
          </button>
        @endforeach
        @if(empty($mediaLibrary))
          <span class="muted" style="grid-column:1/-1;font-size:.8rem">کتابخانه خالی است — اول آپلود کنید.</span>
        @endif
      </div>
    </div>

    <div class="side-box">
      <strong>گالری</strong>
      <input type="file" name="gallery[]" accept="image/*" multiple style="margin-top:.5rem">
      <p class="muted" style="font-size:.82rem;margin:.8rem 0 .4rem">از کتابخانه:</p>
      <button type="button" class="btn btn-outline" data-media-pick data-field="gallery_library_paths" data-as="path" data-multi="1" data-title="انتخاب گالری محصول" style="width:100%;margin-bottom:.5rem">انتخاب تصاویر گالری از کتابخانه</button>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.4rem;max-height:160px;overflow:auto" id="galleryLibBox">
        @foreach(($mediaLibrary ?? []) as $m)
          <label style="display:block;border:1px solid var(--line);border-radius:8px;overflow:hidden;cursor:pointer;margin:0">
            <input type="checkbox" name="gallery_library_paths[]" value="{{ $m['path'] }}" style="display:none" class="gal-check">
            <img src="{{ $m['url'] }}" alt="" style="width:100%;height:64px;object-fit:cover;display:block;opacity:.85">
          </label>
        @endforeach
      </div>
      @if(!empty($product->gallery))
        <label style="display:flex;gap:.5rem;align-items:center;font-weight:500;margin-top:.7rem">
          <input type="checkbox" name="clear_gallery" value="1"> پاک کردن گالری فعلی
        </label>
      @endif
    </div>
  </aside>
</div>
</form>

<script>
const W = @json($warrantyLabels);
const C = @json($conditionLabels);
const S = @json($stockLabels);
const P = @json($partLabels);
const nf = n => new Intl.NumberFormat('fa-IR').format(Number(n||0));

function setOn(el, on){ if(!el) return; el.style.display = on ? '' : 'none'; }
function cardFlag(key){
  const el = document.querySelector('.card-set[data-key="'+key+'"]');
  return el ? el.checked : true;
}
function refreshPreview(){
  const name = document.getElementById('f_name').value || 'نام محصول';
  const short = document.getElementById('f_short').value || '';
  const brand = document.getElementById('f_brand').value;
  const capacity = document.getElementById('f_capacity').value;
  const part = document.getElementById('f_part').value;
  const price = Number(document.getElementById('f_price').value||0);
  const compare = Number(document.getElementById('f_compare').value||0);
  const serial = document.getElementById('f_serial').value.trim();
  const stock = document.getElementById('f_stock').value || 0;
  const stockStatus = document.getElementById('f_stock_status').value || 'instock';
  const warranty = document.getElementById('f_warranty').value;
  const wmonths = document.getElementById('f_wmonths').value;
  const condition = document.getElementById('f_condition').value;

  document.getElementById('liveTitle').textContent = name;
  document.getElementById('liveDesc').textContent = short.slice(0,90);
  setOn(document.getElementById('liveDesc'), cardFlag('show_short_desc') && short);

  const meta = [];
  if(brand) meta.push('<span>'+brand+'</span>');
  if(capacity) meta.push('<span>'+capacity+'</span>');
  if(part && P[part]) meta.push('<span>'+P[part]+'</span>');
  document.getElementById('liveMeta').innerHTML = meta.join('');
  setOn(document.getElementById('liveMeta'), cardFlag('show_meta') && meta.length);

  const badges = [];
  if(cardFlag('show_warranty')){
    const hasW = warranty && warranty !== 'none';
    badges.push('<span class="shop-badge '+(hasW?'shop-badge--ok':'shop-badge--none')+'">'+(hasW?'گارانتی دارد':'فاقد گارانتی')+'</span>');
    if(hasW){
      let t = (W[warranty]||warranty);
      if(wmonths) t += ' — '+wmonths+' ماه';
      badges.push('<span class="shop-badge shop-badge--info">'+t+'</span>');
    }
  }
  if(cardFlag('show_condition') && condition && C[condition]){
    badges.push('<span class="shop-badge">'+C[condition]+'</span>');
  }
  document.getElementById('liveBadges').innerHTML = badges.join('');

  setOn(document.getElementById('liveSerial'), cardFlag('show_serial') && !!serial);
  document.getElementById('liveSerialCode').textContent = serial;

  document.getElementById('livePrice').textContent = nf(price)+' تومان';
  const onSale = compare > price && price > 0;
  setOn(document.getElementById('liveOld'), onSale);
  document.getElementById('liveOld').textContent = nf(compare)+' تومان';
  setOn(document.getElementById('liveRibbon'), onSale);
  if(onSale){
    const pct = Math.round(((compare-price)/compare)*100);
    document.getElementById('liveRibbon').textContent = pct+'٪';
  }

  const stockEl = document.getElementById('liveStock');
  stockEl.textContent = (S[stockStatus]||stockStatus)+' · '+stock+' عدد';
  stockEl.className = 'shop-card__stock shop-card__stock--'+stockStatus;
  setOn(stockEl, cardFlag('show_stock'));

  setOn(document.getElementById('btnCart'), cardFlag('show_add_cart'));
  setOn(document.getElementById('btnBuy'), cardFlag('show_buy_now'));
}

document.querySelectorAll('.woo-tab').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.querySelectorAll('.woo-tab').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.woo-pane').forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-'+btn.dataset.tab).classList.add('active');
  });
});

['f_name','f_short','f_brand','f_capacity','f_part','f_price','f_compare','f_serial','f_stock','f_stock_status','f_warranty','f_wmonths','f_condition']
  .forEach(id=> document.getElementById(id)?.addEventListener('input', refreshPreview));
document.querySelectorAll('.card-set').forEach(el=> el.addEventListener('change', refreshPreview));

document.getElementById('f_image')?.addEventListener('change', e=>{
  const f = e.target.files?.[0];
  if(!f) return;
  document.getElementById('clear_image').value = '0';
  const url = URL.createObjectURL(f);
  document.getElementById('liveImg').src = url;
  const prev = document.getElementById('feat-preview');
  prev.src = url; prev.style.display='block';
});
document.querySelectorAll('.lib-pick').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('clear_image').value = '0';
    document.getElementById('image_library_path').value = btn.dataset.path;
    document.getElementById('liveImg').src = btn.dataset.url;
    const prev = document.getElementById('feat-preview');
    prev.src = btn.dataset.url; prev.style.display='block';
    document.querySelectorAll('.lib-pick').forEach(b=>b.style.outline='');
    btn.style.outline = '2px solid #e23d12';
  });
});
document.getElementById('btnClearImage')?.addEventListener('click', ()=>{
  document.getElementById('clear_image').value = '1';
  document.getElementById('image_library_path').value = '';
  const file = document.getElementById('f_image');
  if(file) file.value = '';
  const prev = document.getElementById('feat-preview');
  if(prev){ prev.removeAttribute('src'); prev.style.display = 'none'; }
  const live = document.getElementById('liveImg');
  if(live) live.removeAttribute('src');
  document.querySelectorAll('.lib-pick').forEach(b=>b.style.outline='');
});
document.querySelectorAll('.gal-check').forEach(chk=>{
  chk.addEventListener('change',()=>{
    chk.parentElement.style.outline = chk.checked ? '2px solid #e23d12' : '';
    chk.nextElementSibling.style.opacity = chk.checked ? '1' : '.85';
  });
});
refreshPreview();

(function(){
  const required = ['f_name','f_price','f_brand','f_stock'];
  const optional = ['f_cost','f_short','f_image','f_capacity','f_part'];
  function updateCompletion(){
    let score=0, total=required.length*2+optional.length;
    required.forEach(id=>{ const el=document.getElementById(id); if(el && String(el.value||'').trim()!=='') score+=2; });
    optional.forEach(id=>{ const el=document.getElementById(id); if(el && ((el.type==='file'&&el.files?.length)||String(el.value||'').trim()!=='')) score++; });
    const percent=Math.round((score/total)*100);
    const bar=document.getElementById('editorProgressBar');
    const text=document.getElementById('editorProgressText');
    const hint=document.getElementById('editorSaveHint');
    if(bar) bar.style.width=percent+'%';
    if(text) text.textContent=percent+'٪';
    if(hint) hint.textContent=percent>=80 ? 'کالا آماده انتشار است.' : 'اطلاعات اصلی، قیمت، موجودی و تصویر را کامل کنید.';
  }
  [...required,...optional].forEach(id=>{
    const el=document.getElementById(id); el?.addEventListener('input',updateCompletion); el?.addEventListener('change',updateCompletion);
  });
  updateCompletion();
})();

(function(){
  const hasW = document.getElementById('f_has_warranty');
  const box = document.getElementById('warrantyBox');
  const sel = document.getElementById('f_requires_serial');
  const hint = document.getElementById('serialHint');
  const btn = document.getElementById('btnSaveSerials');
  const company = document.getElementById('f_warranty_company');
  const months = document.getElementById('f_wmonths');
  function sync(){
    const wOn = hasW && hasW.value === '1';
    if(box) box.style.display = wOn ? '' : 'none';
    if(!wOn && sel) sel.value = '0';
    const on = wOn && sel && sel.value === '1';
    if(hint) hint.style.display = on ? '' : 'none';
    if(btn) btn.style.display = on ? '' : 'none';
  }
  hasW?.addEventListener('change', sync);
  sel?.addEventListener('change', sync);
  company?.addEventListener('change', function(){
    const o = company.options[company.selectedIndex];
    if(months && o && o.dataset.months) months.value = o.dataset.months;
  });
  sync();
  @if(!empty($withSerialFocus))
  setTimeout(function(){
    document.querySelector('.woo-tab[data-tab="warranty"]')?.click();
    document.getElementById('serial-warranty')?.scrollIntoView({behavior:'smooth',block:'center'});
  }, 180);
  @endif
})();

window.__mediaPick = function(items, field){
  if(!items || !items.length) return;
  field = field || 'image_library_path';
  if(field === 'gallery_library_paths'){
    const box = document.getElementById('galleryLibBox');
    items.forEach(it=>{
      const label = document.createElement('label');
      label.style.cssText = 'display:block;border:1px solid var(--line);border-radius:8px;overflow:hidden;cursor:pointer;margin:0;outline:2px solid #e23d12';
      label.innerHTML = '<input type="checkbox" name="gallery_library_paths[]" value="'+it.path+'" style="display:none" class="gal-check" checked>'
        + (/\.(mp4|webm|mov)$/i.test(it.path||'')
          ? '<div style="height:64px;display:flex;align-items:center;justify-content:center;background:#111;color:#fff;font-size:.7rem">ویدیو</div>'
          : '<img src="'+it.url+'" alt="" style="width:100%;height:64px;object-fit:cover;display:block">');
      box?.prepend(label);
    });
    return;
  }
  const first = items[0];
  document.getElementById('clear_image').value = '0';
  document.getElementById('image_library_path').value = first.path;
  document.getElementById('liveImg').src = first.url;
  const prev = document.getElementById('feat-preview');
  if(prev){ prev.src = first.url; prev.style.display='block'; }
};

(function(){
  function nf(n){ return (n||0).toLocaleString('fa-IR'); }
  function syncProfit(){
    var sell = parseInt(document.getElementById('f_price')?.value || '0', 10) || 0;
    var cost = parseInt(document.getElementById('f_cost')?.value || '0', 10) || 0;
    var profit = sell - cost;
    var pct = sell > 0 ? Math.round((profit / sell) * 1000) / 10 : 0;
    var a = document.getElementById('profitAmt');
    var p = document.getElementById('profitPct');
    if(a){ a.textContent = nf(profit) + ' تومان'; a.style.color = profit >= 0 ? '#047857' : '#a32012'; }
    if(p){ p.textContent = pct + '٪'; }
  }
  document.getElementById('f_price')?.addEventListener('input', syncProfit);
  document.getElementById('f_cost')?.addEventListener('input', syncProfit);
  syncProfit();
})();
</script>
@endsection
