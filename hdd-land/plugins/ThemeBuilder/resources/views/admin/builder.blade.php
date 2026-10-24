@extends('layouts.admin')
@section('title', 'صفحه‌ساز')
@section('content')
@php
  $current = $current ?? null;
  $groups = $groups ?? [];
@endphp
<link rel="stylesheet" href="{{ asset('css/page-builder.css') }}?v=1">
<div class="pb">
  <aside class="pb-side">
    <h2>همه منوها</h2>
    <p class="hint" style="margin-bottom:.6rem">هر آپشن منو صفحه خودش را دارد. ویرایش همان بوم را باز می‌کند.</p>
    @foreach($groups as $name => $rows)
      <div class="pb-group">
        <b>{{ $name }}</b>
        @foreach($rows as $row)
          <a href="{{ url('/admin/page-builder?page='.$row['id']) }}" class="{{ (string)($current['id'] ?? '') === (string)$row['id'] ? 'is-on' : '' }}">
            {{ $row['menu_label'] ?: $row['title'] }}
          </a>
        @endforeach
      </div>
    @endforeach
  </aside>
  <div class="pb-main">
    @if(!$current)
      <h1>صفحه‌ساز</h1>
      <p class="hint">صفحه‌ای برای ویرایش پیدا نشد.</p>
    @else
      <h1>صفحه‌ساز — {{ $current['title'] }}</h1>
      <p class="hint">متن و عکس همین صفحه را اینجا عوض کنید. لینک منو همان آدرس عمومی است. دکمه ویرایش بالای سایت مستقیم همین بوم را باز می‌کند.</p>
      <div id="pb-msg" hidden></div>
      <div class="pb-meta">
        <label class="pb-field">عنوان صفحه<input id="pb-title" value="{{ $current['title'] }}"></label>
        <label class="pb-field">عنوان منو<input id="pb-menu" value="{{ $current['menu_label'] }}"></label>
        <label class="pb-field">گروه
          <select id="pb-group">
            @foreach(['صفحه اول','طراحی سایت','هوش مصنوعی','حسابداری وب','حسابداری کامپیوتر','تعمیرگاه','فروش سایت','سایت'] as $g)
              <option value="{{ $g }}" @selected(($current['menu_group'] ?? '') === $g)>{{ $g }}</option>
            @endforeach
          </select>
        </label>
        <label class="pb-field">آدرس عمومی<input id="pb-path" value="{{ $current['path'] }}"></label>
        <label class="pb-field">عنوان سئو<input id="pb-seo-title" value="{{ $current['seo_title'] }}"></label>
        <label class="pb-field">وضعیت
          <select id="pb-status">
            <option value="publish" @selected(($current['status'] ?? '') === 'publish')>منتشر</option>
            <option value="draft" @selected(($current['status'] ?? '') === 'draft')>پیش‌نویس</option>
          </select>
        </label>
      </div>
      <label class="pb-field">توضیح سئو<textarea id="pb-seo-desc" rows="2">{{ $current['seo_description'] }}</textarea></label>
      <div class="pb-add">
        <button type="button" data-add="hero">هیرو + عکس</button>
        <button type="button" data-add="richtext">متن غنی</button>
        <button type="button" data-add="image">عکس</button>
        <button type="button" data-add="cards">کارت‌ها</button>
        <button type="button" data-add="list">فهرست</button>
        <button type="button" data-add="steps">مراحل</button>
        <button type="button" data-add="stats">آمار</button>
        <button type="button" data-add="works">نمونه کار</button>
        <button type="button" data-add="cta">دعوت به اقدام</button>
      </div>
      <div id="pb-app"
           data-save="{{ url('/admin/page-builder') }}"
           data-upload="{{ url('/admin/page-builder/upload') }}"
           data-page='@json($current)'>
        <div id="pb-canvas"></div>
      </div>
      <div class="pb-actions">
        <button class="btn btn-primary" type="button" id="pb-save">ذخیره همین صفحه</button>
        <a class="btn" id="pb-view" href="{{ url($current['path'] ?? '/') }}" target="_blank" rel="noopener">مشاهده در سایت</a>
      </div>
    @endif
  </div>
</div>
<script src="{{ asset('js/page-builder.js') }}?v=1"></script>
@endsection
