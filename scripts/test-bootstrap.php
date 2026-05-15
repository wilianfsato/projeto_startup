<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'bootstrap';

ob_start();
require dirname(__DIR__) . '/api.php';
$raw = ob_get_clean();
$data = json_decode($raw, true);

if (!is_array($data) || empty($data['ok'])) {
  fwrite(STDERR, "Bootstrap falhou:\n{$raw}\n");
  exit(1);
}

echo 'animais: ' . count($data['animais'] ?? []) . "\n";
echo 'adotados: ' . json_encode($data['adotados'] ?? []) . "\n";
echo 'usuarios (sessao): ' . ($data['user'] ? 'logado' : 'anon') . "\n";
