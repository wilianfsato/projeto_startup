<?php
declare(strict_types=1);

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
  require $localConfig;
}

if (!defined('DB_DRIVER')) {
  define('DB_DRIVER', 'mysql');
}
if (!defined('DB_HOST')) {
  define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_PORT')) {
  define('DB_PORT', 3306);
}
if (!defined('DB_NAME')) {
  define('DB_NAME', 'vidadequatropatas');
}
if (!defined('DB_USER')) {
  define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
  define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
  define('DB_CHARSET', 'utf8mb4');
}
if (!defined('DB_PATH')) {
  define('DB_PATH', __DIR__ . '/data/app.sqlite');
}
if (!defined('GOOGLE_CLIENT_ID')) {
  define('GOOGLE_CLIENT_ID', '');
}
if (!defined('PASSWORD_RESET_DEMO_REVEAL')) {
  define('PASSWORD_RESET_DEMO_REVEAL', true);
}
if (!defined('APP_NAME')) {
  define('APP_NAME', 'Vida de Quatro Patas');
}
if (!defined('APP_VERSION')) {
  define('APP_VERSION', '1.0.0-fase2');
}
