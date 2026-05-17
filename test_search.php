<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/v1/search/professionals', 'GET');
$controller = new App\Http\Controllers\Api\V1\SearchController();
$response = $controller->professionals($request);
echo json_encode($response->toResponse($request)->getData());
