<?php
declare(strict_types=1);

function db_is_mysql(): bool
{
  return defined('DB_DRIVER') && DB_DRIVER === 'mysql';
}

/** Expressão SQL para “agora” (comparação de expiração de tokens). */
function sql_now_expr(): string
{
  return db_is_mysql() ? 'NOW()' : "datetime('now')";
}

/** INSERT que ignora duplicata (adoptions, etc.). */
function sql_insert_ignore(): string
{
  return db_is_mysql() ? 'INSERT IGNORE' : 'INSERT OR IGNORE';
}

function db(): PDO
{
  static $pdo = null;
  if ($pdo instanceof PDO) {
    return $pdo;
  }

  if (db_is_mysql()) {
    $pdo = connect_mysql();
  } else {
    $pdo = connect_sqlite();
    init_schema($pdo);
    migrate_schema($pdo);
  }

  return $pdo;
}

function connect_mysql(): PDO
{
  $dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    DB_HOST,
    (int) DB_PORT,
    DB_NAME,
    DB_CHARSET
  );

  try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
  } catch (PDOException $e) {
    throw new RuntimeException(
      'Não foi possível conectar ao MySQL (' . DB_NAME . '@' . DB_HOST . '). '
      . 'Verifique host, usuário e senha em config.php / config.local.php. '
      . 'Detalhe: ' . $e->getMessage(),
      0,
      $e
    );
  }

  return $pdo;
}

function connect_sqlite(): PDO
{
  $dir = dirname(DB_PATH);
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }

  $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  $pdo->exec('PRAGMA foreign_keys = ON');

  return $pdo;
}

/** Adiciona colunas novas em bases já existentes (SQLite). */
function ensure_column(PDO $pdo, string $table, string $column, string $sqlType): void
{
  $info = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC);
  foreach ($info as $col) {
    if (($col['name'] ?? '') === $column) {
      return;
    }
  }
  $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $sqlType);
}

function migrate_schema(PDO $pdo): void
{
  ensure_column($pdo, 'users', 'cpf', 'TEXT');
  ensure_column($pdo, 'users', 'telefone', 'TEXT');
  ensure_column($pdo, 'users', 'foto_fundo_data', 'TEXT');
  ensure_column($pdo, 'users', 'notif_app', 'INTEGER NOT NULL DEFAULT 1');
  ensure_column($pdo, 'users', 'notif_email', 'INTEGER NOT NULL DEFAULT 1');
  ensure_column($pdo, 'users', 'notif_whatsapp', 'INTEGER NOT NULL DEFAULT 0');
  ensure_column(
    $pdo,
    'users',
    'notif_freq',
    "TEXT NOT NULL DEFAULT 'evento'"
  );

  $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS password_resets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token_hash TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_password_resets_user ON password_resets(user_id);

CREATE TABLE IF NOT EXISTS account_deletion_tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token_hash TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_account_del_user ON account_deletion_tokens(user_id);

CREATE TABLE IF NOT EXISTS app_ratings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  stars INTEGER NOT NULL CHECK(stars >= 1 AND stars <= 5),
  comment TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL);
}

function init_schema(PDO $pdo): void
{
  $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT,
  display_name TEXT,
  avatar_url TEXT,
  foto_perfil_data TEXT,
  login_provider TEXT NOT NULL DEFAULT 'email',
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS favorites (
  user_id INTEGER NOT NULL,
  animal_id INTEGER NOT NULL,
  PRIMARY KEY (user_id, animal_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS adoptions (
  animal_id INTEGER PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS ongs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nome TEXT NOT NULL,
  rua TEXT,
  numero TEXT,
  bairro TEXT,
  cidade TEXT NOT NULL,
  estado TEXT,
  contato TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS animals_extra (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nome TEXT NOT NULL,
  tipo TEXT,
  idade TEXT,
  sexo TEXT,
  vacinas TEXT,
  deficiencia TEXT,
  alergia TEXT,
  foto TEXT NOT NULL,
  whatsapp TEXT,
  ong TEXT
);

CREATE TABLE IF NOT EXISTS denuncias (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  denunciante TEXT NOT NULL,
  cidade TEXT NOT NULL,
  endereco TEXT NOT NULL,
  tipo TEXT NOT NULL,
  descricao TEXT NOT NULL,
  contato TEXT NOT NULL,
  foto_url TEXT,
  foto_data_url TEXT,
  data_label TEXT NOT NULL
);
SQL);

  /* Próximos IDs de animals_extra ficam ≥ 10001 para não colidir com os 15 animais fixos do JS. */
  $seq = $pdo->query("SELECT seq FROM sqlite_sequence WHERE name = 'animals_extra'")->fetchColumn();
  $maxId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) FROM animals_extra")->fetchColumn();
  if (($seq === false || (int) $seq < 9999) && $maxId < 16) {
    $pdo->exec(
      "INSERT INTO animals_extra (id, nome, tipo, idade, sexo, vacinas, deficiencia, alergia, foto, whatsapp, ong)
       VALUES (10000, '__init__', '-', '-', '-', '-', '-', '-', 'https://', '', '')"
    );
    $pdo->exec("DELETE FROM animals_extra WHERE id = 10000");
  }
}
