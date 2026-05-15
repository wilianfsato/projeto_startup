<?php
/**
 * Copie para config.local.php e preencha usuário/senha do MySQL.
 * config.local.php não vai para o git.
 */
declare(strict_types=1);

if (!defined('DB_USER')) {
  define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
  define('DB_PASS', 'sua_senha_aqui');
}
