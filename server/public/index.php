<?php
// Strict typing, CORS, and preflight (OPTIONS)
declare(strict_types=1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load helpers, small JSON db, and validators
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/DataStore.php';
require __DIR__ . '/../src/Validators.php';

// Removing /api prefix, trim trailing /, normalize empty to /, prepping datastore
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim(preg_replace('#^/api#', '', $path), '/');
if ($path === '') $path = '/';
$method = $_SERVER['REQUEST_METHOD'];
$input  = read_json();
$store  = new DataStore(__DIR__ . '/../data');

// Routing controller using Switch/Case for one-file simplicity
switch (true){
  // Staff
  case $method === 'GET'  && $path === '/staff':
    json_response($store->getAll('staff'));
    break;
  case $method === 'POST' && $path === '/staff':
    $data = validate_staff($input);
    json_response($store->create('staff', $data), 201);
    break;
    
  // Shifts
  case $method === 'GET'  && $path === '/shifts':
    json_response($store->getAll('shifts'));
    break;
  case $method === 'POST' && $path === '/shifts':
    $data = validate_shift($input);
    json_response($store->create('shifts', $data), 201);
    break;

  // Assign
  case $method === 'POST' && preg_match('#^/shifts/([a-f0-9]{32})/assign$#', $path, $m):
    $assign = validate_assign($input);
    json_response(assign_shift($store, $m[1], $assign['staffId']), 200);
    break;

  // 404 anything else
  default:
    json_response(['error' => ['message' => 'Not found']], 404);

}