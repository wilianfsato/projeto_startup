<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/lib/consultas-repo.php';

garantir_campos_dashboard($pdo);

$modulo = consulta_param_string('modulo', 'animais');
$format = strtolower(consulta_param_string('format', 'csv'));
$allowedModulos = ['animais', 'adocoes', 'usuarios', 'denuncias', 'ongs'];
if (!in_array($modulo, $allowedModulos, true)) {
  http_response_code(400);
  exit('Módulo inválido.');
}

$data = consulta_exportar_todos($pdo, $modulo);
$colunas = consulta_colunas_modulo($modulo);
$linhas = [];
foreach ($data['items'] as $row) {
  $linhas[] = consulta_linha_export($modulo, $row);
}

$titulo = 'Relatório – ' . consulta_titulo_modulo($modulo);
$geradoEm = date('d/m/Y H:i');
$filtrosTxt = [];
foreach ($data['filtros'] ?? [] as $k => $v) {
  if ($v !== '' && $v !== null) {
    $filtrosTxt[] = "{$k}={$v}";
  }
}
$filtrosResumo = $filtrosTxt ? implode(' | ', $filtrosTxt) : 'Nenhum filtro aplicado';

if ($format === 'csv') {
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="relatorio_' . $modulo . '_' . date('Y-m-d') . '.csv"');
  echo "\xEF\xBB\xBF";
  $out = fopen('php://output', 'w');
  fputcsv($out, [$titulo], ';');
  fputcsv($out, ['Gerado em', $geradoEm], ';');
  fputcsv($out, ['Filtros', $filtrosResumo], ';');
  fputcsv($out, [], ';');
  fputcsv($out, $colunas, ';');
  foreach ($linhas as $linha) {
    fputcsv($out, $linha, ';');
  }
  fclose($out);
  exit;
}

if ($format === 'xlsx') {
  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header('Content-Disposition: attachment; filename="relatorio_' . $modulo . '_' . date('Y-m-d') . '.xls"');
  echo "\xEF\xBB\xBF";
  echo '<html><head><meta charset="UTF-8"></head><body>';
  echo '<h2>' . sanitize($titulo) . '</h2>';
  echo '<p><strong>Gerado em:</strong> ' . sanitize($geradoEm) . '</p>';
  echo '<p><strong>Filtros:</strong> ' . sanitize($filtrosResumo) . '</p>';
  echo '<table border="1"><thead><tr>';
  foreach ($colunas as $c) {
    echo '<th>' . sanitize($c) . '</th>';
  }
  echo '</tr></thead><tbody>';
  foreach ($linhas as $linha) {
    echo '<tr>';
    foreach ($linha as $cel) {
      echo '<td>' . sanitize((string) $cel) . '</td>';
    }
    echo '</tr>';
  }
  echo '</tbody></table></body></html>';
  exit;
}

if ($format === 'pdf') {
  header('Content-Type: text/html; charset=UTF-8');
  ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title><?= sanitize($titulo) ?></title>
  <style>
    body { font-family: "Segoe UI", sans-serif; margin: 24px; color: #111; }
    h1 { font-size: 1.25rem; margin-bottom: 4px; }
    .meta { color: #555; font-size: 0.9rem; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    th { background: #f1f5f9; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body>
  <p class="no-print"><button onclick="window.print()">Imprimir / Salvar como PDF</button></p>
  <h1><?= sanitize($titulo) ?></h1>
  <p class="meta">Gerado em: <?= sanitize($geradoEm) ?><br>Filtros: <?= sanitize($filtrosResumo) ?><br>Total: <?= (int) $data['total'] ?> registro(s)</p>
  <table>
    <thead><tr><?php foreach ($colunas as $c): ?><th><?= sanitize($c) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
      <?php foreach ($linhas as $linha): ?>
      <tr><?php foreach ($linha as $cel): ?><td><?= sanitize((string) $cel) ?></td><?php endforeach; ?></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <script>window.addEventListener('load', () => { setTimeout(() => window.print(), 400); });</script>
</body>
</html>
  <?php
  exit;
}

http_response_code(400);
echo 'Formato inválido. Use csv, xlsx ou pdf.';
