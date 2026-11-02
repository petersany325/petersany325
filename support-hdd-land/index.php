<?php

/**
 * Shared-hosting entry when Document Root is the project folder (not /public).
 * Forwards all requests to the Laravel public front controller.
 */
require __DIR__.'/public/index.php';
