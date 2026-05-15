<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/lib/db.php';

$pdo = db();
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
  echo "=== {$t} ===\n";
  $st = $pdo->query('DESCRIBE `' . str_replace('`', '``', $t) . '`');
  foreach ($st as $c) {
    echo "  {$c['Field']} | {$c['Type']}\n";
  }
  $n = (int) $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $t) . '`')->fetchColumn();
  echo "  ({$n} registros)\n\n";
}
