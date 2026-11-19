@foreach($nodes as $node)
  <div class="sm-node" data-depth="{{ $depth }}">
    <div class="sm-row">
      <input class="js-label" value="{{ $node['label'] ?? '' }}" maxlength="80" placeholder="نام منو">
      <input class="js-url" value="{{ $node['url'] ?? '/' }}" maxlength="300" dir="ltr" placeholder="/path">
      <div class="sm-ops">
        <button type="button" data-act="up">بالا</button>
        <button type="button" data-act="down">پایین</button>
        @if($depth < 2)
          <button type="button" data-act="child">زیرمنو</button>
        @endif
        <button type="button" data-act="del">حذف</button>
      </div>
    </div>
    <div class="sm-kids">
      @include('studio::admin.site-menu-node', ['nodes' => $node['children'] ?? [], 'depth' => $depth + 1])
    </div>
  </div>
@endforeach
