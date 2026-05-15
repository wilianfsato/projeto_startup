<?php
/**
 * Migra dados das tabelas em inglês para o modelo em português e remove tabelas antigas.
 * Uso: php scripts/migrate-para-portugues.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/lib/db.php';

if (!db_is_mysql()) {
  fwrite(STDERR, "Esta migração só roda com DB_DRIVER=mysql.\n");
  exit(1);
}

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function table_exists(PDO $pdo, string $table): bool
{
  $st = $pdo->prepare(
    'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
  );
  $st->execute([$table]);
  return (bool) $st->fetchColumn();
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
  $st = $pdo->prepare(
    'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
  );
  $st->execute([$table, $column]);
  return (bool) $st->fetchColumn();
}

function add_column(PDO $pdo, string $table, string $column, string $definition): void
{
  if (!column_exists($pdo, $table, $column)) {
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    echo "  + coluna {$table}.{$column}\n";
  }
}

echo "=== Migração para tabelas em português ===\n\n";

echo "1) Expandindo usuarios...\n";
add_column($pdo, 'usuarios', 'senha_hash', 'VARCHAR(255) NULL');
add_column($pdo, 'usuarios', 'url_avatar', 'TEXT NULL');
add_column($pdo, 'usuarios', 'foto_perfil', 'LONGTEXT NULL');
add_column($pdo, 'usuarios', 'foto_fundo', 'LONGTEXT NULL');
add_column($pdo, 'usuarios', 'provedor_login', "VARCHAR(32) NOT NULL DEFAULT 'email'");
add_column($pdo, 'usuarios', 'cpf', 'TEXT NULL');
add_column($pdo, 'usuarios', 'notif_app', 'TINYINT NOT NULL DEFAULT 1');
add_column($pdo, 'usuarios', 'notif_email', 'TINYINT NOT NULL DEFAULT 1');
add_column($pdo, 'usuarios', 'notif_whatsapp', 'TINYINT NOT NULL DEFAULT 0');
add_column($pdo, 'usuarios', 'notif_freq', "VARCHAR(32) NOT NULL DEFAULT 'evento'");

echo "2) Expandindo animais...\n";
add_column($pdo, 'animais', 'tipo', 'VARCHAR(80) NULL');
add_column($pdo, 'animais', 'alergia', 'VARCHAR(255) NULL');
add_column($pdo, 'animais', 'foto', 'TEXT NULL');
add_column($pdo, 'animais', 'whatsapp', 'VARCHAR(30) NULL');
add_column($pdo, 'animais', 'nome_ong', 'VARCHAR(200) NULL');
add_column($pdo, 'animais', 'cadastro_site', 'TINYINT NOT NULL DEFAULT 0');
if (column_exists($pdo, 'animais', 'idade')) {
  $pdo->exec('ALTER TABLE animais MODIFY idade VARCHAR(50) NULL');
}

echo "3) Limpando ongs (colunas duplicadas em inglês)...\n";
foreach (['id', 'rua', 'numero', 'bairro', 'cidade', 'estado', 'contato'] as $col) {
  if (column_exists($pdo, 'ongs', $col)) {
    /* Migra contato → telefone se telefone vazio */
    if ($col === 'contato' && column_exists($pdo, 'ongs', 'telefone')) {
      $pdo->exec('UPDATE ongs SET telefone = contato WHERE (telefone IS NULL OR telefone = "") AND contato IS NOT NULL AND contato != ""');
    }
    if ($col === 'cidade' && column_exists($pdo, 'ongs', 'localizacao')) {
      $pdo->exec('UPDATE ongs SET cidade = localizacao WHERE (cidade IS NULL OR cidade = "") AND localizacao IS NOT NULL');
    }
  }
}
foreach (['id', 'rua', 'numero', 'bairro', 'estado', 'contato'] as $col) {
  if (column_exists($pdo, 'ongs', $col)) {
    $pdo->exec("ALTER TABLE ongs DROP COLUMN `{$col}`");
    echo "  - drop ongs.{$col}\n";
  }
}
add_column($pdo, 'ongs', 'rua', 'VARCHAR(200) NULL');
add_column($pdo, 'ongs', 'numero', 'VARCHAR(20) NULL');
add_column($pdo, 'ongs', 'bairro', 'VARCHAR(120) NULL');
add_column($pdo, 'ongs', 'cidade', 'VARCHAR(120) NULL');
add_column($pdo, 'ongs', 'estado', 'VARCHAR(2) NULL');

echo "4) Criando tabelas auxiliares em português...\n";
$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS favoritos (
  id_usuario INT NOT NULL,
  id_animal INT NOT NULL,
  PRIMARY KEY (id_usuario, id_animal),
  CONSTRAINT fk_fav_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  CONSTRAINT fk_fav_animal FOREIGN KEY (id_animal) REFERENCES animais(id_animal) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS avaliacoes_app (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  estrelas TINYINT UNSIGNED NOT NULL,
  comentario TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_av_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS recuperacao_senha (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  token_hash VARCHAR(128) NOT NULL,
  expira_em DATETIME NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rec_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  INDEX idx_rec_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

$pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS tokens_exclusao_conta (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario INT NOT NULL,
  token_hash VARCHAR(128) NOT NULL,
  expira_em DATETIME NOT NULL,
  CONSTRAINT fk_exc_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  INDEX idx_exc_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

echo "5) Migrando users → usuarios...\n";
if (table_exists($pdo, 'users')) {
  $rows = $pdo->query('SELECT * FROM users')->fetchAll(PDO::FETCH_ASSOC);
  $mapUser = [];
  $ins = $pdo->prepare(
    'INSERT INTO usuarios (id_usuario, nome, email, senha, senha_hash, telefone, tipo, url_avatar, foto_perfil, foto_fundo, provedor_login, cpf, notif_app, notif_email, notif_whatsapp, notif_freq, criado_em)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE
       nome = VALUES(nome),
       senha_hash = VALUES(senha_hash),
       telefone = VALUES(telefone),
       url_avatar = VALUES(url_avatar),
       foto_perfil = VALUES(foto_perfil),
       foto_fundo = VALUES(foto_fundo),
       provedor_login = VALUES(provedor_login),
       cpf = VALUES(cpf),
       notif_app = VALUES(notif_app),
       notif_email = VALUES(notif_email),
       notif_whatsapp = VALUES(notif_whatsapp),
       notif_freq = VALUES(notif_freq)'
  );
  foreach ($rows as $u) {
    $oldId = (int) $u['id'];
    $ins->execute([
      $oldId,
      $u['display_name'] ?? $u['email'],
      $u['email'],
      null,
      $u['password_hash'] ?? null,
      $u['telefone'] ?? null,
      'usuario',
      $u['avatar_url'] ?? null,
      $u['foto_perfil_data'] ?? null,
      $u['foto_fundo_data'] ?? null,
      $u['login_provider'] ?? 'email',
      $u['cpf'] ?? null,
      (int) ($u['notif_app'] ?? 1),
      (int) ($u['notif_email'] ?? 1),
      (int) ($u['notif_whatsapp'] ?? 0),
      $u['notif_freq'] ?? 'evento',
      $u['created_at'] ?? date('Y-m-d H:i:s'),
    ]);
    $mapUser[$oldId] = $oldId;
    echo "  usuário {$u['email']} → id_usuario {$oldId}\n";
  }
  $pdo->exec('ALTER TABLE usuarios AUTO_INCREMENT = ' . (max(array_keys($mapUser) ?: [0]) + 1));
}

echo "6) Sincronizando animais com animals_catalog...\n";
if (table_exists($pdo, 'animals_catalog')) {
  $cat = $pdo->query('SELECT * FROM animals_catalog ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  $up = $pdo->prepare(
    'UPDATE animais SET nome=?, tipo=?, idade=?, sexo=?, vacinado=?, deficiencia=?, alergia=?, foto=?, whatsapp=?, nome_ong=?, status="disponivel" WHERE id_animal=?'
  );
  $ins = $pdo->prepare(
    'INSERT INTO animais (id_animal, nome, tipo, idade, sexo, vacinado, deficiencia, alergia, foto, whatsapp, nome_ong, status, cadastro_site)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,"disponivel",0)'
  );
  foreach ($cat as $c) {
    $id = (int) $c['id'];
    $exists = $pdo->prepare('SELECT 1 FROM animais WHERE id_animal = ?');
    $exists->execute([$id]);
    if ($exists->fetch()) {
      $up->execute([
        $c['nome'], $c['tipo'], $c['idade'], $c['sexo'], $c['vacinas'],
        $c['deficiencia'], $c['alergia'], $c['foto'], $c['whatsapp'], $c['ong'], $id,
      ]);
    } else {
      $ins->execute([
        $id, $c['nome'], $c['tipo'], $c['idade'], $c['sexo'], $c['vacinas'],
        $c['deficiencia'], $c['alergia'], $c['foto'], $c['whatsapp'], $c['ong'],
      ]);
    }
  }
  echo '  ' . count($cat) . " animais do catálogo\n";
}

if (table_exists($pdo, 'animals_extra')) {
  $extras = $pdo->query('SELECT * FROM animals_extra ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  $ins = $pdo->prepare(
    'INSERT INTO animais (id_animal, nome, tipo, idade, sexo, vacinado, deficiencia, alergia, foto, whatsapp, nome_ong, status, cadastro_site)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,"disponivel",1)
     ON DUPLICATE KEY UPDATE nome=VALUES(nome), tipo=VALUES(tipo), idade=VALUES(idade), sexo=VALUES(sexo),
       vacinado=VALUES(vacinado), deficiencia=VALUES(deficiencia), alergia=VALUES(alergia), foto=VALUES(foto),
       whatsapp=VALUES(whatsapp), nome_ong=VALUES(nome_ong), cadastro_site=1'
  );
  foreach ($extras as $e) {
    $ins->execute([
      (int) $e['id'], $e['nome'], $e['tipo'], $e['idade'], $e['sexo'], $e['vacinas'],
      $e['deficiencia'], $e['alergia'], $e['foto'], $e['whatsapp'], $e['ong'],
    ]);
  }
  echo '  ' . count($extras) . " animais extras\n";
}

echo "7) Migrando favoritos e adoções...\n";
if (table_exists($pdo, 'favorites')) {
  $pdo->exec(
    'INSERT IGNORE INTO favoritos (id_usuario, id_animal)
     SELECT user_id, animal_id FROM favorites'
  );
}
if (table_exists($pdo, 'adoptions')) {
  foreach ($pdo->query('SELECT animal_id FROM adoptions')->fetchAll(PDO::FETCH_COLUMN) as $aid) {
    $aid = (int) $aid;
    $pdo->prepare('UPDATE animais SET status = "adotado" WHERE id_animal = ?')->execute([$aid]);
    if (table_exists($pdo, 'favorites')) {
      $uid = $pdo->query(
        'SELECT f.user_id FROM favorites f WHERE f.animal_id = ' . $aid . ' LIMIT 1'
      )->fetchColumn();
      if ($uid) {
        $pdo->prepare(
          'INSERT IGNORE INTO adocoes (id_usuario, id_animal, status, data) VALUES (?,?,"aprovado",NOW())'
        )->execute([(int) $uid, $aid]);
      }
    }
  }
  echo "  animais marcados como adotado\n";
}

if (table_exists($pdo, 'ongs') && table_exists($pdo, 'users')) {
  /* ongs inseridas pelo site usavam coluna id — já tratado no passo 3 */
}

echo "8) Migrando avaliações e tokens...\n";
if (table_exists($pdo, 'app_ratings')) {
  $pdo->exec(
    'INSERT INTO avaliacoes_app (id_usuario, estrelas, comentario, criado_em)
     SELECT user_id, stars, comment, COALESCE(created_at, NOW()) FROM app_ratings'
  );
}
if (table_exists($pdo, 'password_resets')) {
  $pdo->exec(
    'INSERT INTO recuperacao_senha (id_usuario, token_hash, expira_em, criado_em)
     SELECT user_id, token_hash, expires_at, COALESCE(created_at, NOW()) FROM password_resets'
  );
}
if (table_exists($pdo, 'account_deletion_tokens')) {
  $pdo->exec(
    'INSERT INTO tokens_exclusao_conta (id_usuario, token_hash, expira_em)
     SELECT user_id, token_hash, expires_at FROM account_deletion_tokens'
  );
}

echo "9) Removendo tabelas em inglês / duplicadas...\n";
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$drop = [
  'favorites', 'adoptions', 'users', 'animals_extra', 'animals_catalog',
  'app_ratings', 'password_resets', 'account_deletion_tokens', 'user_passkeys',
  'interesses',
];
foreach ($drop as $t) {
  if (table_exists($pdo, $t)) {
    $pdo->exec("DROP TABLE `{$t}`");
    echo "  DROP {$t}\n";
  }
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "\n=== Migração concluída ===\n";
echo "Execute: php scripts/test-db.php\n";
