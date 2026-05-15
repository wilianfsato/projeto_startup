<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/lib/consultas-repo.php';

garantir_campos_dashboard($pdo);

$modulo = consulta_param_string('modulo', 'animais');
$modulos = [
  'animais' => 'Animais',
  'adocoes' => 'Adoções',
  'usuarios' => 'Usuários',
  'denuncias' => 'Denúncias',
  'ongs' => 'ONGs',
];
if (!isset($modulos[$modulo])) {
  $modulo = 'animais';
}

$data = match ($modulo) {
  'adocoes' => consulta_adocoes($pdo),
  'usuarios' => consulta_usuarios($pdo),
  'denuncias' => consulta_denuncias($pdo),
  'ongs' => consulta_ongs($pdo),
  default => consulta_animais($pdo),
};

$exportBase = 'exportar-relatorio.php?' . consulta_query_string(['modulo' => $modulo]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Consultas e relatórios — Vida de Quatro Patas</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/admin.css">
  <style>
    .tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
    .tabs a { padding: 8px 14px; border-radius: 999px; text-decoration: none; font-weight: 600; font-size: 0.85rem;
      background: #fff; color: #5b21b6; border: 1px solid #ddd6fe; }
    .tabs a.ativo { background: #6d28d9; color: #fff; border-color: #6d28d9; }
    .filtros { background: #fff; padding: 16px; border-radius: 12px; margin-bottom: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,.06); display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; align-items: end; }
    .filtros label { display: block; font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 4px; }
    .filtros input, .filtros select { width: 100%; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; font: inherit; }
    .filtros .acoes { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 8px; }
    .btn { padding: 8px 16px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; font: inherit; text-decoration: none; display: inline-block; }
    .btn-prim { background: #6d28d9; color: #fff; }
    .btn-sec { background: #f1f5f9; color: #334155; }
    .btn-export { background: #059669; color: #fff; font-size: 0.85rem; }
    .paginacao { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .paginacao a { padding: 6px 12px; background: #fff; border-radius: 8px; text-decoration: none; color: #5b21b6; border: 1px solid #e2e8f0; }
    .paginacao span { color: #64748b; font-size: 0.9rem; }
    .resumo { margin-bottom: 12px; color: #64748b; font-size: 0.9rem; }
    .export-bar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
  </style>
</head>
<body class="dashboard-body">
<header class="dashboard-top">
  <h1>🐾 Consultas e relatórios</h1>
  <nav>
    <a href="dashboard.php">Painel</a> ·
    <a href="index.php">Site</a>
  </nav>
</header>

<div class="dashboard-wrap">
  <div class="page-header">
    <h2>Consultas avançadas</h2>
    <p class="meta">Busca, filtros, ordenação e exportação — Etapa 4</p>
  </div>

  <nav class="tabs" aria-label="Módulos">
    <?php foreach ($modulos as $key => $label): ?>
      <a href="?modulo=<?= urlencode($key) ?>" class="<?= $key === $modulo ? 'ativo' : '' ?>"><?= sanitize($label) ?></a>
    <?php endforeach; ?>
  </nav>

  <form class="filtros" method="get">
    <input type="hidden" name="modulo" value="<?= sanitize($modulo) ?>">

    <?php if ($modulo === 'animais'): ?>
      <div><label>Busca (nome/ONG)</label><input type="search" name="q" value="<?= sanitize($data['filtros']['busca'] ?? '') ?>"></div>
      <div><label>Tipo</label><input type="text" name="tipo" value="<?= sanitize($data['filtros']['tipo'] ?? '') ?>" placeholder="Gato, Cachorro…"></div>
      <div><label>Status</label>
        <select name="status">
          <option value="">Todos</option>
          <option value="disponivel" <?= ($data['filtros']['status'] ?? '') === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
          <option value="adotado" <?= ($data['filtros']['status'] ?? '') === 'adotado' ? 'selected' : '' ?>>Adotado</option>
        </select>
      </div>
      <div><label>Porte</label><input type="text" name="porte" value="<?= sanitize($data['filtros']['porte'] ?? '') ?>"></div>
    <?php elseif ($modulo === 'adocoes'): ?>
      <div><label>Busca</label><input type="search" name="q" value="<?= sanitize($data['filtros']['busca'] ?? '') ?>"></div>
      <div><label>Status</label>
        <select name="status">
          <option value="">Todos</option>
          <?php foreach (['pendente', 'aprovado', 'recusado'] as $s): ?>
            <option value="<?= $s ?>" <?= ($data['filtros']['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Data de</label><input type="date" name="de" value="<?= sanitize($data['filtros']['de'] ?? '') ?>"></div>
      <div><label>Data até</label><input type="date" name="ate" value="<?= sanitize($data['filtros']['ate'] ?? '') ?>"></div>
    <?php elseif ($modulo === 'usuarios'): ?>
      <div><label>Busca (nome/e-mail)</label><input type="search" name="q" value="<?= sanitize($data['filtros']['busca'] ?? '') ?>"></div>
      <div><label>Tipo</label>
        <select name="tipo">
          <option value="">Todos</option>
          <?php foreach (['usuario', 'ong', 'admin'] as $t): ?>
            <option value="<?= $t ?>" <?= ($data['filtros']['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php elseif ($modulo === 'denuncias'): ?>
      <div><label>Busca</label><input type="search" name="q" value="<?= sanitize($data['filtros']['busca'] ?? '') ?>"></div>
      <div><label>Cidade</label><input type="text" name="cidade" value="<?= sanitize($data['filtros']['cidade'] ?? '') ?>"></div>
      <div><label>Tipo</label><input type="text" name="tipo_denuncia" value="<?= sanitize($data['filtros']['tipo'] ?? '') ?>"></div>
      <div><label>Status</label>
        <select name="status">
          <option value="">Todos</option>
          <option value="pendente" <?= ($data['filtros']['status'] ?? '') === 'pendente' ? 'selected' : '' ?>>Pendente</option>
          <option value="resolvida" <?= ($data['filtros']['status'] ?? '') === 'resolvida' ? 'selected' : '' ?>>Resolvida</option>
        </select>
      </div>
    <?php else: ?>
      <div><label>Busca</label><input type="search" name="q" value="<?= sanitize($data['filtros']['busca'] ?? '') ?>"></div>
      <div><label>Cidade</label><input type="text" name="cidade" value="<?= sanitize($data['filtros']['cidade'] ?? '') ?>"></div>
      <div><label>Ativa</label>
        <select name="ativa">
          <option value="">Todas</option>
          <option value="1" <?= ($data['filtros']['ativa'] ?? '') === '1' ? 'selected' : '' ?>>Sim</option>
          <option value="0" <?= ($data['filtros']['ativa'] ?? '') === '0' ? 'selected' : '' ?>>Não</option>
        </select>
      </div>
    <?php endif; ?>

    <div class="acoes">
      <button type="submit" class="btn btn-prim">Filtrar</button>
      <a href="?modulo=<?= urlencode($modulo) ?>" class="btn btn-sec">Limpar</a>
    </div>
  </form>

  <p class="resumo"><?= (int) $data['total'] ?> registro(s) encontrado(s)</p>

  <div class="export-bar">
    <a class="btn btn-export" href="<?= sanitize($exportBase . '&format=csv') ?>">⬇ CSV (Excel)</a>
    <a class="btn btn-export" href="<?= sanitize($exportBase . '&format=xlsx') ?>">⬇ XLSX</a>
    <a class="btn btn-export" href="<?= sanitize($exportBase . '&format=pdf') ?>" target="_blank" rel="noopener">⬇ PDF</a>
  </div>

  <div class="grafico-card">
    <table class="tabela-admin">
      <thead>
        <tr>
          <?php foreach (consulta_colunas_modulo($modulo) as $col): ?>
            <th><?= sanitize($col) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (count($data['items']) === 0): ?>
          <tr><td colspan="<?= count(consulta_colunas_modulo($modulo)) ?>" class="dashboard-vazio">Nenhum registro com estes filtros.</td></tr>
        <?php else: ?>
          <?php foreach ($data['items'] as $row): ?>
            <tr>
              <?php foreach (consulta_linha_export($modulo, $row) as $cel): ?>
                <td><?= sanitize((string) $cel) ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($data['pages'] > 1): ?>
  <nav class="paginacao" aria-label="Paginação">
    <?php if ($data['page'] > 1): ?>
      <a href="?<?= sanitize(consulta_query_string(['page' => $data['page'] - 1])) ?>">← Anterior</a>
    <?php endif; ?>
    <span>Página <?= (int) $data['page'] ?> de <?= (int) $data['pages'] ?></span>
    <?php if ($data['page'] < $data['pages']): ?>
      <a href="?<?= sanitize(consulta_query_string(['page' => $data['page'] + 1])) ?>">Próxima →</a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
</div>
</body>
</html>
