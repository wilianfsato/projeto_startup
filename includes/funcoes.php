<?php
declare(strict_types=1);

function sanitize(?string $value): string
{
  return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function coluna_existe(PDO $pdo, string $tabela, string $coluna): bool
{
  $st = $pdo->prepare(
    'SELECT 1 FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
  );
  $st->execute([$tabela, $coluna]);
  return (bool) $st->fetchColumn();
}

/** Colunas opcionais usadas pelo painel (denuncias.status, ongs.ativa). */
function garantir_campos_dashboard(PDO $pdo): void
{
  if (!coluna_existe($pdo, 'denuncias', 'status')) {
    $pdo->exec(
      "ALTER TABLE denuncias ADD COLUMN status ENUM('pendente','resolvida') NOT NULL DEFAULT 'pendente'"
    );
  }
  if (!coluna_existe($pdo, 'ongs', 'ativa')) {
    $pdo->exec('ALTER TABLE ongs ADD COLUMN ativa TINYINT NOT NULL DEFAULT 1');
  }
}
