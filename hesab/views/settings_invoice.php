<?php /** @var array $s */ ?>
<form method="post" action="<?= e(url('/settings/invoice')) ?>" class="form">
  <?= csrf_field() ?>
  <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px">
    <div class="panel">
      <div class="hd"><strong>هویت شرکت روی فاکتور</strong></div>
      <div class="bd" style="padding:12px;display:grid;gap:8px">
        <label>نام شرکت / برند<input name="inv_company_name" value="<?= e($s['inv_company_name']) ?>"></label>
        <label>آدرس<textarea name="inv_company_address" rows="2"><?= e($s['inv_company_address']) ?></textarea></label>
        <label>تلفن<input name="inv_company_phone" value="<?= e($s['inv_company_phone']) ?>"></label>
        <label>فکس<input name="inv_company_fax" value="<?= e($s['inv_company_fax']) ?>"></label>
        <label>کد اقتصادی<input name="inv_economic_code" value="<?= e($s['inv_economic_code']) ?>"></label>
        <label>شناسه ملی<input name="inv_national_id" value="<?= e($s['inv_national_id']) ?>"></label>
        <label>کد پستی<input name="inv_postal_code" value="<?= e($s['inv_postal_code']) ?>"></label>
        <label>آدرس لوگو (URL)<input name="inv_logo_url" value="<?= e($s['inv_logo_url']) ?>" dir="ltr"></label>
      </div>
    </div>
    <div class="panel">
      <div class="hd"><strong>شماره‌گذاری و مالیات</strong></div>
      <div class="bd" style="padding:12px;display:grid;gap:8px">
        <label>پیشوند شماره فاکتور<input name="inv_prefix" value="<?= e($s['inv_prefix']) ?>"></label>
        <label>شماره بعدی<input name="inv_next_number" value="<?= e($s['inv_next_number']) ?>" class="num"></label>
        <label>درصد مالیات<input name="inv_tax_percent" value="<?= e($s['inv_tax_percent']) ?>" class="num"></label>
        <label>واحد پول<input name="inv_currency" value="<?= e($s['inv_currency']) ?>"></label>
        <label>یادداشت پیش‌فرض<textarea name="inv_default_note" rows="2"><?= e($s['inv_default_note']) ?></textarea></label>
        <label><input type="checkbox" name="inv_show_tax" <?= $s['inv_show_tax']==='1'?'checked':'' ?>> نمایش مالیات روی فاکتور</label>
      </div>
    </div>
    <div class="panel">
      <div class="hd"><strong>چاپ پیشرفته</strong></div>
      <div class="bd" style="padding:12px;display:grid;gap:8px">
        <label>نوع کاغذ
          <select name="print_paper">
            <?php foreach (['A4'=>'A4','A5'=>'A5','thermal80'=>'حرارتی ۸۰mm'] as $k=>$lab): ?>
              <option value="<?= e($k) ?>" <?= $s['print_paper']===$k?'selected':'' ?>><?= e($lab) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>جهت
          <select name="print_orientation">
            <option value="portrait" <?= $s['print_orientation']==='portrait'?'selected':'' ?>>عمودی</option>
            <option value="landscape" <?= $s['print_orientation']==='landscape'?'selected':'' ?>>افقی</option>
          </select>
        </label>
        <label>حاشیه (mm)<input name="print_margin_mm" value="<?= e($s['print_margin_mm']) ?>" class="num"></label>
        <label>اندازه فونت<input name="print_font_size" value="<?= e($s['print_font_size']) ?>" class="num"></label>
        <label>رنگ تأکید<input name="print_color_accent" value="<?= e($s['print_color_accent']) ?>" dir="ltr"></label>
        <label>تعداد کپی<input name="print_copies" value="<?= e($s['print_copies']) ?>" class="num"></label>
        <label>واترمارک<input name="print_watermark" value="<?= e($s['print_watermark']) ?>"></label>
        <label><input type="checkbox" name="print_show_logo" <?= $s['print_show_logo']==='1'?'checked':'' ?>> نمایش لوگو</label>
        <label><input type="checkbox" name="print_show_signature" <?= $s['print_show_signature']==='1'?'checked':'' ?>> محل امضا</label>
        <label><input type="checkbox" name="print_show_stamp" <?= $s['print_show_stamp']==='1'?'checked':'' ?>> محل مهر</label>
        <label><input type="checkbox" name="print_show_qr" <?= $s['print_show_qr']==='1'?'checked':'' ?>> QR کد فاکتور</label>
        <label><input type="checkbox" name="print_auto_open" <?= $s['print_auto_open']==='1'?'checked':'' ?>> باز شدن خودکار پنجره چاپ بعد از صدور</label>
      </div>
    </div>
    <div class="panel">
      <div class="hd"><strong>سربرگ و پاورقی HTML</strong></div>
      <div class="bd" style="padding:12px;display:grid;gap:8px">
        <label>سربرگ سفارشی<textarea name="print_header_html" rows="4" dir="rtl"><?= e($s['print_header_html']) ?></textarea></label>
        <label>پاورقی<textarea name="print_footer_html" rows="3"><?= e($s['print_footer_html']) ?></textarea></label>
        <p style="color:var(--muted);font-size:12px;margin:0">متغیرها: {page} {company} {date}</p>
        <div style="display:flex;gap:8px">
          <button class="btn" type="submit">ذخیره تنظیمات فاکتور/چاپ</button>
          <a class="btn ghost" href="<?= e(url('/settings')) ?>">بازگشت</a>
        </div>
      </div>
    </div>
  </div>
</form>
