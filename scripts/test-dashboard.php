<?php
declare(strict_types=1);

ob_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
include dirname(__DIR__) . '/dashboard.php';
$html = ob_get_clean();

$ok = true;
$checks = [];

if (strpos($html, 'Painel de indicadores') === false) {
  $ok = false;
  $checks[] = 'Título do painel não encontrado';
}
if (strpos($html, 'chart.js') === false && strpos($html, 'Chart') === false) {
  $ok = false;
  $checks[] = 'Chart.js não referenciado';
}
if (!preg_match('/class="numero">\d+/', $html)) {
  $ok = false;
  $checks[] = 'KPIs numéricos não renderizados';
}
if (strpos($html, 'graficoEspecie') === false) {
  $ok = false;
  $checks[] = 'Canvas do gráfico ausente';
}
if (strpos($html, 'Fatal error') !== false || strpos($html, 'PDOException') !== false) {
  $ok = false;
  $checks[] = 'Erro PHP na página';
}

echo $ok ? "OK: dashboard renderizou sem erros.\n" : "FALHA:\n";
foreach ($checks as $c) {
  echo "  - {$c}\n";
}
exit($ok ? 0 : 1);
