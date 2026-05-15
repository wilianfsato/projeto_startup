<?php
declare(strict_types=1);

session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'secure' => false,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/config.php';

$htmlPath = __DIR__ . '/index.html';
if (!is_readable($htmlPath)) {
  http_response_code(500);
  echo 'index.html não encontrado.';
  exit;
}

readfile($htmlPath);
