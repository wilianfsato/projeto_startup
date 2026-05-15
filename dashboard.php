<?php
declare(strict_types=1);

/**
 * Painel de KPIs e gráficos — Etapa 4 (gestão / transparência).
 * Usa o mesmo banco MySQL do site (tabelas em português).
 */
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

garantir_campos_dashboard($pdo);

// ── KPIs principais ───────────────────────────────────────────
$kpis = [
    'total_animais' => (int) $pdo->query('SELECT COUNT(*) FROM animais')->fetchColumn(),
    'disponiveis' => (int) $pdo->query("SELECT COUNT(*) FROM animais WHERE status = 'disponivel'")->fetchColumn(),
    'adotados' => (int) $pdo->query("SELECT COUNT(*) FROM animais WHERE status = 'adotado'")->fetchColumn(),
    'em_processo' => (int) $pdo->query("SELECT COUNT(*) FROM adocoes WHERE status = 'pendente'")->fetchColumn(),
    'total_ongs' => (int) $pdo->query('SELECT COUNT(*) FROM ongs WHERE ativa = 1')->fetchColumn(),
    'total_usuarios' => (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'usuario'")->fetchColumn(),
    'denuncias_abertas' => (int) $pdo->query("SELECT COUNT(*) FROM denuncias WHERE status = 'pendente'")->fetchColumn(),
    'adocoes_mes' => (int) $pdo->query(
        'SELECT COUNT(*) FROM adocoes
         WHERE MONTH(`data`) = MONTH(NOW()) AND YEAR(`data`) = YEAR(NOW())'
    )->fetchColumn(),
];

// ── Gráfico 1: Animais por tipo (espécie) ─────────────────────
$rows = $pdo->query(
    "SELECT COALESCE(NULLIF(TRIM(tipo), ''), 'Não informado') AS especie, COUNT(*) AS total
     FROM animais
     GROUP BY COALESCE(NULLIF(TRIM(tipo), ''), 'Não informado')
     ORDER BY total DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$especies_labels = array_column($rows, 'especie');
$especies_dados = array_map('intval', array_column($rows, 'total'));

// ── Gráfico 2: Adoções por mês (últimos 6 meses) ──────────────
$rows = $pdo->query(
    "SELECT DATE_FORMAT(`data`, '%m/%Y') AS mes, COUNT(*) AS total
     FROM adocoes
     WHERE `data` >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(`data`, '%m/%Y')
     ORDER BY MIN(`data`)"
)->fetchAll(PDO::FETCH_ASSOC);
$meses_labels = array_column($rows, 'mes');
$meses_dados = array_map('intval', array_column($rows, 'total'));

// ── Gráfico 3: Status das adoções ─────────────────────────────
$rows = $pdo->query('SELECT status, COUNT(*) AS total FROM adocoes GROUP BY status')->fetchAll(PDO::FETCH_ASSOC);
$adocao_labels = array_column($rows, 'status');
$adocao_dados = array_map('intval', array_column($rows, 'total'));

// ── Gráfico 4: Animais por porte ──────────────────────────────
$rows = $pdo->query(
    "SELECT COALESCE(NULLIF(TRIM(porte), ''), 'Não informado') AS porte, COUNT(*) AS total
     FROM animais
     GROUP BY COALESCE(NULLIF(TRIM(porte), ''), 'Não informado')
     ORDER BY total DESC"
)->fetchAll(PDO::FETCH_ASSOC);
$porte_labels = array_column($rows, 'porte');
$porte_dados = array_map('intval', array_column($rows, 'total'));

// ── Últimas adoções ───────────────────────────────────────────
$ultimasAdocoes = $pdo->query(
    'SELECT a.nome AS animal, u.nome AS usuario, ad.status, ad.`data` AS data_solicitacao
     FROM adocoes ad
     INNER JOIN animais a ON ad.id_animal = a.id_animal
     INNER JOIN usuarios u ON ad.id_usuario = u.id_usuario
     ORDER BY ad.`data` DESC
     LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

$paleta = ['rgba(109,40,217,.85)', 'rgba(5,150,105,.85)', 'rgba(37,99,235,.85)', 'rgba(217,119,6,.85)', 'rgba(220,38,38,.85)'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel — Vida de Quatro Patas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="dashboard-body">

<header class="dashboard-top">
    <h1>🐾 Vida de Quatro Patas</h1>
    <nav>
        <a href="consultas.php">Consultas</a> ·
        <a href="index.php">← Voltar ao site</a>
    </nav>
</header>

<div class="dashboard-wrap">

<div class="page-header">
    <h2>📊 Painel de indicadores</h2>
    <p class="meta">Atualizado em <?= sanitize(date('d/m/Y H:i')) ?> · dados em tempo real do MySQL</p>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="numero"><?= $kpis['total_animais'] ?></div>
        <div class="label">🐾 Total de animais</div>
    </div>
    <div class="kpi-card">
        <div class="numero" style="color:#16a34a"><?= $kpis['disponiveis'] ?></div>
        <div class="label">✅ Disponíveis</div>
    </div>
    <div class="kpi-card">
        <div class="numero" style="color:#2563eb"><?= $kpis['adotados'] ?></div>
        <div class="label">🏠 Adotados</div>
    </div>
    <div class="kpi-card">
        <div class="numero" style="color:#d97706"><?= $kpis['em_processo'] ?></div>
        <div class="label">⏳ Adoções pendentes</div>
    </div>
    <div class="kpi-card">
        <div class="numero"><?= $kpis['total_ongs'] ?></div>
        <div class="label">🏢 ONGs ativas</div>
    </div>
    <div class="kpi-card">
        <div class="numero"><?= $kpis['total_usuarios'] ?></div>
        <div class="label">👤 Usuários</div>
    </div>
    <div class="kpi-card">
        <div class="numero" style="color:#dc2626"><?= $kpis['denuncias_abertas'] ?></div>
        <div class="label">🚨 Denúncias abertas</div>
    </div>
    <div class="kpi-card">
        <div class="numero" style="color:#6d28d9"><?= $kpis['adocoes_mes'] ?></div>
        <div class="label">📅 Adoções este mês</div>
    </div>
</div>

<div class="graficos-grid">
    <div class="grafico-card">
        <h3>🐕 Animais por tipo</h3>
        <canvas id="graficoEspecie" aria-label="Gráfico de animais por tipo"></canvas>
    </div>
    <div class="grafico-card">
        <h3>📈 Adoções por mês (6 meses)</h3>
        <canvas id="graficoAdocoes" aria-label="Gráfico de adoções por mês"></canvas>
    </div>
    <div class="grafico-card">
        <h3>📋 Status das adoções</h3>
        <canvas id="graficoStatus" aria-label="Gráfico de status das adoções"></canvas>
    </div>
    <div class="grafico-card">
        <h3>📏 Animais por porte</h3>
        <canvas id="graficoPorte" aria-label="Gráfico de animais por porte"></canvas>
    </div>
</div>

<div class="grafico-card" style="margin-top:20px">
    <h3>🕐 Últimas solicitações de adoção</h3>
    <?php if (count($ultimasAdocoes) === 0): ?>
        <p class="dashboard-vazio">Nenhuma adoção registrada ainda. Ao adotar um animal no site, os dados aparecem aqui.</p>
    <?php else: ?>
    <table class="tabela-admin">
        <thead>
            <tr><th>Animal</th><th>Usuário</th><th>Status</th><th>Data</th></tr>
        </thead>
        <tbody>
            <?php foreach ($ultimasAdocoes as $a): ?>
            <tr>
                <td><?= sanitize($a['animal']) ?></td>
                <td><?= sanitize($a['usuario']) ?></td>
                <td><span class="badge badge-<?= sanitize($a['status']) ?>"><?= sanitize($a['status']) ?></span></td>
                <td><?= sanitize(date('d/m/Y H:i', strtotime($a['data_solicitacao']))) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</div>

<script>
const paleta = <?= json_encode($paleta, JSON_UNESCAPED_UNICODE) ?>;

function criarGrafico(id, cfg) {
  const el = document.getElementById(id);
  if (!el) return;
  const labels = cfg.data?.labels || [];
  if (!labels.length) {
    el.parentElement.insertAdjacentHTML('beforeend',
      '<p class="dashboard-vazio" style="margin-top:8px">Sem dados para exibir ainda.</p>');
    return;
  }
  new Chart(el, cfg);
}

criarGrafico('graficoEspecie', {
  type: 'pie',
  data: {
    labels: <?= json_encode($especies_labels, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{ data: <?= json_encode($especies_dados) ?>, backgroundColor: paleta }]
  },
  options: { plugins: { legend: { position: 'bottom' } } }
});

criarGrafico('graficoAdocoes', {
  type: 'line',
  data: {
    labels: <?= json_encode($meses_labels, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{
      label: 'Adoções',
      data: <?= json_encode($meses_dados) ?>,
      borderColor: paleta[0],
      backgroundColor: 'rgba(109,40,217,.12)',
      tension: 0.3,
      fill: true
    }]
  },
  options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

criarGrafico('graficoStatus', {
  type: 'bar',
  data: {
    labels: <?= json_encode($adocao_labels, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{ label: 'Adoções', data: <?= json_encode($adocao_dados) ?>, backgroundColor: paleta }]
  },
  options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
});

criarGrafico('graficoPorte', {
  type: 'bar',
  data: {
    labels: <?= json_encode($porte_labels, JSON_UNESCAPED_UNICODE) ?>,
    datasets: [{ label: 'Animais', data: <?= json_encode($porte_dados) ?>, backgroundColor: paleta }]
  },
  options: { indexAxis: 'y', plugins: { legend: { display: false } } } }
});
</script>

</body>
</html>
