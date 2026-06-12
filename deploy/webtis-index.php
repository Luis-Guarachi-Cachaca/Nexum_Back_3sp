<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Laravel vive un nivel arriba de public_html, en ~/nexum-back
$laravelRoot = dirname(__DIR__) . '/nexum-back';

if (file_exists($maintenance = $laravelRoot . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $laravelRoot . '/vendor/autoload.php';

(require_once $laravelRoot . '/bootstrap/app.php')
    ->handleRequest(Request::capture());
