<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['modulo'] = 'animais';

ob_start();
include dirname(__DIR__) . '/consultas.php';
$html = ob_get_clean();

$ok = strpos($html, 'Consultas avançadas') !== false
  && strpos($html, 'tabela-admin') !== false
  && strpos($html, 'exportar-relatorio') !== false
  && strpos($html, 'Fatal error') === false;

echo $ok ? "OK: consultas.php renderizou.\n" : "FALHA na página de consultas.\n";
exit($ok ? 0 : 1);
