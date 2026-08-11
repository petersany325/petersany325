<?php
declare(strict_types=1);
?>
  </div>
</main>
<script>
(function () {
  var btn = document.getElementById('menuToggle');
  var overlay = document.getElementById('sidebarOverlay');
  function closeNav() { document.body.classList.remove('nav-open'); }
  function toggleNav() { document.body.classList.toggle('nav-open'); }
  if (btn) btn.addEventListener('click', toggleNav);
  if (overlay) overlay.addEventListener('click', closeNav);
  document.querySelectorAll('.sidebar nav a').forEach(function (a) {
    a.addEventListener('click', closeNav);
  });
})();
</script>
</body>
</html>
