<?php

/**
 * Root installer entry for shared hosting when Document Root is the project folder
 * and mod_rewrite/.htaccess may not map /install.php → /public/install.php.
 */
require __DIR__.'/public/install.php';
