<script>
(function () {
  var root = document.querySelector('.ct');
  if (!root) return;
  var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
  function sleep(ms) { return new Promise(function (r) { setTimeout(r, reduce ? 0 : ms); }); }
  var SAY = ['سلام! چه کمکی از ما برمی‌آید؟', 'موضوع را انتخاب کنید.', 'یا مستقیم با مشاور فروش تماس بگیرید.'];
  var bubble = document.getElementById('ctBubble');
  (async function () {
    if (!bubble || reduce) return;
    for (var k = 0; ; k = (k + 1) % SAY.length) {
      bubble.textContent = '';
      for (var i = 0; i < SAY[k].length; i++) { bubble.textContent += SAY[k][i]; await sleep(40); }
      await sleep(2200);
    }
  })();
  var buttons = root.querySelectorAll('.ct-tp');
  var warn = document.getElementById('ctWarn');
  var sup = document.getElementById('ctSup');
  function pick(t) {
    buttons.forEach(function (x) { x.classList.toggle('on', x.getAttribute('data-t') === t); });
    if (warn) warn.classList.toggle('on', t === 'بازیابی اطلاعات');
    if (sup) sup.classList.toggle('on', t === 'پشتیبانی مشتریان');
  }
  buttons.forEach(function (x) {
    x.addEventListener('click', function () {
      pick(x.getAttribute('data-t'));
      if (window.innerWidth < 900) x.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'nearest' });
    });
  });
  pick('حسابداری و نرم‌افزار');
})();
</script>
