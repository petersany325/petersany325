<?php
// Already installed — send users home instead of a dead installer 404.
header('Location: /', true, 302);
exit;
