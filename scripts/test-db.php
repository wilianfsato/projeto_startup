<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/lib/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
  $pdo = db();
  $driver = db_is_mysql() ? 'MySQL' : 'SQLite';
  echo "OK: conectado via {$driver}\n";

  if (db_is_mysql()) {
    echo 'Banco: ' . DB_NAME . '@' . DB_HOST . "\n";
  }

  $tables = [
    'usuarios',
    'favoritos',
    'animais',
    'adocoes',
    'ongs',
    'denuncias',
    'avaliacoes_app',
    'recuperacao_senha',
    'tokens_exclusao_conta',
  ];
  foreach ($tables as $table) {
    $n = (int) $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`')->fetchColumn();
    echo "  {$table}: {$n} registro(s)\n";
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo "ERRO: " . $e->getMessage() . "\n";
  exit(1);
}
