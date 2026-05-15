<?php
declare(strict_types=1);

header('Content-Type: application/javascript; charset=utf-8');
require_once __DIR__ . '/config.php';

/**
 * Caminho absoluto a partir da raiz do site (/pasta/api.php).
 * Usa a pasta de config.js.php, que fica no mesmo diretório de index.php e api.php.
 */
$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$dir = dirname($script);
$dir = str_replace('\\', '/', (string) $dir);
$dir = rtrim($dir, '/');
if ($dir === '' || $dir === '.' || $dir === '/') {
  $apiPath = '/api.php';
} else {
  $apiPath = $dir . '/api.php';
}
echo 'window.__API_URL__=' . json_encode($apiPath, JSON_UNESCAPED_SLASHES) . ';';
echo 'window.__GOOGLE_CLIENT_ID__=' . json_encode(GOOGLE_CLIENT_ID) . ';';
