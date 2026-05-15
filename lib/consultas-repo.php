<?php
declare(strict_types=1);

function consulta_param_string(string $key, string $default = ''): string
{
  return trim((string) ($_GET[$key] ?? $_POST[$key] ?? $default));
}

function consulta_param_int(string $key, int $default, int $min = 0, int $max = 999999): int
{
  $v = (int) ($_GET[$key] ?? $_POST[$key] ?? $default);
  return max($min, min($max, $v));
}

/** @return array{items: list<array<string,mixed>>, total: int, page: int, per_page: int, pages: int} */
function consulta_paginar(PDO $pdo, string $sqlBase, string $sqlCount, array $params, int $page, int $perPage): array
{
  $stCount = $pdo->prepare($sqlCount);
  $stCount->execute($params);
  $total = (int) $stCount->fetchColumn();
  $pages = max(1, (int) ceil($total / $perPage));
  $page = max(1, min($page, $pages));
  $offset = ($page - 1) * $perPage;

  $sql = $sqlBase . ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
  $st = $pdo->prepare($sql);
  $st->execute($params);
  $items = $st->fetchAll(PDO::FETCH_ASSOC);

  return [
    'items' => $items,
    'total' => $total,
    'page' => $page,
    'per_page' => $perPage,
    'pages' => $pages,
  ];
}

/** @return array{items: list<array<string,mixed>>, total: int, page: int, per_page: int, pages: int, filtros: array<string,string>} */
function consulta_animais(PDO $pdo): array
{
  $page = consulta_param_int('page', 1, 1);
  $perPage = consulta_param_int('per_page', 20, 5, 100);
  $busca = consulta_param_string('q');
  $tipo = consulta_param_string('tipo');
  $status = consulta_param_string('status');
  $porte = consulta_param_string('porte');
  $ordem = consulta_param_string('ordem', 'id_animal');
  $dir = strtoupper(consulta_param_string('dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

  $allowedOrder = ['id_animal', 'nome', 'tipo', 'status', 'idade', 'porte'];
  if (!in_array($ordem, $allowedOrder, true)) {
    $ordem = 'id_animal';
  }

  $where = ['1=1'];
  $params = [];
  if ($busca !== '') {
    $where[] = '(nome LIKE ? OR nome_ong LIKE ?)';
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
  }
  if ($tipo !== '') {
    $where[] = 'tipo = ?';
    $params[] = $tipo;
  }
  if ($status !== '' && in_array($status, ['disponivel', 'adotado'], true)) {
    $where[] = 'status = ?';
    $params[] = $status;
  }
  if ($porte !== '') {
    $where[] = 'porte = ?';
    $params[] = $porte;
  }

  $whereSql = implode(' AND ', $where);
  $sqlBase = "SELECT id_animal, nome, tipo, sexo, idade, porte, status, vacinado, nome_ong, cadastro_site
              FROM animais WHERE {$whereSql} ORDER BY {$ordem} {$dir}";
  $sqlCount = "SELECT COUNT(*) FROM animais WHERE {$whereSql}";

  $result = consulta_paginar($pdo, $sqlBase, $sqlCount, $params, $page, $perPage);
  $result['filtros'] = compact('busca', 'tipo', 'status', 'porte', 'ordem', 'dir');
  return $result;
}

/** @return array{items: list<array<string,mixed>>, total: int, page: int, per_page: int, pages: int, filtros: array<string,string>} */
function consulta_adocoes(PDO $pdo): array
{
  $page = consulta_param_int('page', 1, 1);
  $perPage = consulta_param_int('per_page', 20, 5, 100);
  $status = consulta_param_string('status');
  $de = consulta_param_string('de');
  $ate = consulta_param_string('ate');
  $busca = consulta_param_string('q');

  $where = ['1=1'];
  $params = [];
  if ($status !== '' && in_array($status, ['pendente', 'aprovado', 'recusado'], true)) {
    $where[] = 'ad.status = ?';
    $params[] = $status;
  }
  if ($de !== '') {
    $where[] = 'DATE(ad.`data`) >= ?';
    $params[] = $de;
  }
  if ($ate !== '') {
    $where[] = 'DATE(ad.`data`) <= ?';
    $params[] = $ate;
  }
  if ($busca !== '') {
    $where[] = '(a.nome LIKE ? OR u.nome LIKE ? OR u.email LIKE ?)';
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
  }

  $whereSql = implode(' AND ', $where);
  $sqlBase = "SELECT ad.id_adocao, ad.`data`, ad.status, ad.observacao,
                     a.id_animal, a.nome AS animal, u.nome AS usuario, u.email
              FROM adocoes ad
              INNER JOIN animais a ON ad.id_animal = a.id_animal
              INNER JOIN usuarios u ON ad.id_usuario = u.id_usuario
              WHERE {$whereSql}
              ORDER BY ad.`data` DESC";
  $sqlCount = "SELECT COUNT(*) FROM adocoes ad
               INNER JOIN animais a ON ad.id_animal = a.id_animal
               INNER JOIN usuarios u ON ad.id_usuario = u.id_usuario
               WHERE {$whereSql}";

  $result = consulta_paginar($pdo, $sqlBase, $sqlCount, $params, $page, $perPage);
  $result['filtros'] = compact('status', 'de', 'ate', 'busca');
  return $result;
}

/** @return array{items: list<array<string,mixed>>, total: int, page: int, per_page: int, pages: int, filtros: array<string,string>} */
function consulta_usuarios(PDO $pdo): array
{
  $page = consulta_param_int('page', 1, 1);
  $perPage = consulta_param_int('per_page', 20, 5, 100);
  $busca = consulta_param_string('q');
  $tipo = consulta_param_string('tipo');

  $where = ['1=1'];
  $params = [];
  if ($busca !== '') {
    $where[] = '(nome LIKE ? OR email LIKE ?)';
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
  }
  if ($tipo !== '' && in_array($tipo, ['usuario', 'ong', 'admin'], true)) {
    $where[] = 'tipo = ?';
    $params[] = $tipo;
  }

  $whereSql = implode(' AND ', $where);
  $sqlBase = "SELECT id_usuario, nome, email, telefone, tipo, cidade, criado_em, provedor_login
              FROM usuarios WHERE {$whereSql} ORDER BY nome ASC";
  $sqlCount = "SELECT COUNT(*) FROM usuarios WHERE {$whereSql}";

  $result = consulta_paginar($pdo, $sqlBase, $sqlCount, $params, $page, $perPage);
  $result['filtros'] = compact('busca', 'tipo');
  return $result;
}

/** @return array{items: list<array<string,mixed>>, total: int, page: int, per_page: int, pages: int, filtros: array<string,string>} */
function consulta_denuncias(PDO $pdo): array
{
  $page = consulta_param_int('page', 1, 1);
  $perPage = consulta_param_int('per_page', 20, 5, 100);
  $busca = consulta_param_string('q');
  $status = consulta_param_string('status');
  $cidade = consulta_param_string('cidade');
  $tipo = consulta_param_string('tipo_denuncia');

  $where = ['1=1'];
  $params = [];
  if ($busca !== '') {
    $where[] = '(denunciante LIKE ? OR descricao LIKE ? OR endereco LIKE ?)';
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
  }
  if ($status !== '' && in_array($status, ['pendente', 'resolvida'], true)) {
    $where[] = 'status = ?';
    $params[] = $status;
  }
  if ($cidade !== '') {
    $where[] = 'cidade LIKE ?';
    $params[] = '%' . $cidade . '%';
  }
  if ($tipo !== '') {
    $where[] = 'tipo = ?';
    $params[] = $tipo;
  }

  $whereSql = implode(' AND ', $where);
  $sqlBase = "SELECT id, denunciante, cidade, endereco, tipo, descricao, contato, status, data_label
              FROM denuncias WHERE {$whereSql} ORDER BY id DESC";
  $sqlCount = "SELECT COUNT(*) FROM denuncias WHERE {$whereSql}";

  $result = consulta_paginar($pdo, $sqlBase, $sqlCount, $params, $page, $perPage);
  $result['filtros'] = compact('busca', 'status', 'cidade', 'tipo');
  return $result;
}

/** @return array{items: list<array<string,mixed>>, total: int, page: int, per_page: int, pages: int, filtros: array<string,string>} */
function consulta_ongs(PDO $pdo): array
{
  $page = consulta_param_int('page', 1, 1);
  $perPage = consulta_param_int('per_page', 20, 5, 100);
  $busca = consulta_param_string('q');
  $cidade = consulta_param_string('cidade');
  $ativa = consulta_param_string('ativa');

  $where = ['1=1'];
  $params = [];
  if ($busca !== '') {
    $where[] = '(nome LIKE ? OR email LIKE ? OR telefone LIKE ?)';
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
    $params[] = '%' . $busca . '%';
  }
  if ($cidade !== '') {
    $where[] = 'cidade LIKE ?';
    $params[] = '%' . $cidade . '%';
  }
  if ($ativa === '1' || $ativa === '0') {
    $where[] = 'ativa = ?';
    $params[] = (int) $ativa;
  }

  $whereSql = implode(' AND ', $where);
  $sqlBase = "SELECT id_ong, nome, cidade, estado, rua, numero, bairro, telefone, email, ativa
              FROM ongs WHERE {$whereSql} ORDER BY nome ASC";
  $sqlCount = "SELECT COUNT(*) FROM ongs WHERE {$whereSql}";

  $result = consulta_paginar($pdo, $sqlBase, $sqlCount, $params, $page, $perPage);
  $result['filtros'] = compact('busca', 'cidade', 'ativa');
  return $result;
}

/** Lista completa para exportação (sem paginação). */
function consulta_exportar_todos(PDO $pdo, string $modulo): array
{
  $_GET['page'] = 1;
  $_GET['per_page'] = 10000;
  return match ($modulo) {
    'animais' => consulta_animais($pdo),
    'adocoes' => consulta_adocoes($pdo),
    'usuarios' => consulta_usuarios($pdo),
    'denuncias' => consulta_denuncias($pdo),
    'ongs' => consulta_ongs($pdo),
    default => ['items' => [], 'total' => 0, 'filtros' => []],
  };
}

/** @return list<string> */
function consulta_colunas_modulo(string $modulo): array
{
  return match ($modulo) {
    'animais' => ['ID', 'Nome', 'Tipo', 'Sexo', 'Idade', 'Porte', 'Status', 'Vacinas', 'ONG'],
    'adocoes' => ['ID', 'Data', 'Status', 'Animal', 'Usuário', 'E-mail', 'Observação'],
    'usuarios' => ['ID', 'Nome', 'E-mail', 'Telefone', 'Tipo', 'Cidade', 'Cadastro', 'Login'],
    'denuncias' => ['ID', 'Denunciante', 'Cidade', 'Tipo', 'Status', 'Endereço', 'Contato', 'Data'],
    'ongs' => ['ID', 'Nome', 'Cidade', 'Estado', 'Telefone', 'E-mail', 'Ativa'],
    default => [],
  };
}

/** @param array<string,mixed> $row */
function consulta_linha_export(string $modulo, array $row): array
{
  return match ($modulo) {
    'animais' => [
      $row['id_animal'],
      $row['nome'],
      $row['tipo'] ?? '',
      $row['sexo'] ?? '',
      $row['idade'] ?? '',
      $row['porte'] ?? '',
      $row['status'] ?? '',
      $row['vacinado'] ?? '',
      $row['nome_ong'] ?? '',
    ],
    'adocoes' => [
      $row['id_adocao'],
      $row['data'] ?? '',
      $row['status'] ?? '',
      $row['animal'] ?? '',
      $row['usuario'] ?? '',
      $row['email'] ?? '',
      $row['observacao'] ?? '',
    ],
    'usuarios' => [
      $row['id_usuario'],
      $row['nome'],
      $row['email'],
      $row['telefone'] ?? '',
      $row['tipo'] ?? '',
      $row['cidade'] ?? '',
      $row['criado_em'] ?? '',
      $row['provedor_login'] ?? '',
    ],
    'denuncias' => [
      $row['id'],
      $row['denunciante'],
      $row['cidade'],
      $row['tipo'],
      $row['status'] ?? 'pendente',
      $row['endereco'],
      $row['contato'],
      $row['data_label'],
    ],
    'ongs' => [
      $row['id_ong'],
      $row['nome'],
      $row['cidade'] ?? '',
      $row['estado'] ?? '',
      $row['telefone'] ?? '',
      $row['email'] ?? '',
      ((int) ($row['ativa'] ?? 1)) === 1 ? 'Sim' : 'Não',
    ],
    default => [],
  };
}

function consulta_titulo_modulo(string $modulo): string
{
  return match ($modulo) {
    'animais' => 'Animais',
    'adocoes' => 'Adoções',
    'usuarios' => 'Usuários',
    'denuncias' => 'Denúncias',
    'ongs' => 'ONGs',
    default => 'Consulta',
  };
}

function consulta_query_string(array $extra = []): string
{
  $params = array_merge($_GET, $extra);
  unset($params['export']);
  return http_build_query($params);
}
