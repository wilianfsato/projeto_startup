# Inicia o site (MySQL via config.php; SQLite se DB_DRIVER = sqlite).
# Uso: clique com o botão direito → Executar com PowerShell, ou:
#   cd "pasta do projeto"
#   powershell -ExecutionPolicy Bypass -File .\dev-server.ps1

$ErrorActionPreference = "Stop"

$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpCmd) {
  Write-Host "PHP nao encontrado no PATH. Instale com: winget install PHP.PHP.8.3" -ForegroundColor Red
  exit 1
}

$phpExe = $phpCmd.Source
$phpRoot = Split-Path $phpExe
$ini = Join-Path $phpRoot "php.ini"
$template = Join-Path $phpRoot "php.ini-development"

if (-not (Test-Path $template)) {
  $template = Join-Path $phpRoot "php.ini-production"
}

if (-not (Test-Path $ini)) {
  if (-not (Test-Path $template)) {
    Write-Host "Nao achei php.ini-development em $phpRoot" -ForegroundColor Red
    exit 1
  }
  Copy-Item $template $ini
  Write-Host "Criado: $ini" -ForegroundColor Green
}

$lines = Get-Content $ini
$changed = $false
$newLines = foreach ($line in $lines) {
  if ($line -match '^\s*;\s*extension_dir\s*=\s*"ext"\s*$') {
    $changed = $true
    'extension_dir = "ext"'
  }
  elseif ($line -match '^\s*;\s*extension\s*=\s*pdo_mysql\s*$') {
    $changed = $true
    'extension=pdo_mysql'
  }
  elseif ($line -match '^\s*;\s*extension\s*=\s*mysqli\s*$') {
    $changed = $true
    'extension=mysqli'
  }
  elseif ($line -match '^\s*;\s*extension\s*=\s*pdo_sqlite\s*$') {
    $changed = $true
    'extension=pdo_sqlite'
  }
  elseif ($line -match '^\s*;\s*extension\s*=\s*sqlite3\s*$') {
    $changed = $true
    'extension=sqlite3'
  }
  else {
    $line
  }
}

if ($changed) {
  $newLines | Set-Content -Path $ini
  Write-Host "Ativado pdo_mysql (+ sqlite opcional) em php.ini" -ForegroundColor Green
}

$mods = & $phpExe -m
if ($mods -notcontains "pdo_mysql") {
  Write-Host ""
  Write-Host "ERRO: pdo_mysql ainda nao esta ativo." -ForegroundColor Red
  Write-Host "Abra o arquivo e descomente manualmente as linhas:" -ForegroundColor Yellow
  Write-Host "  extension_dir = `"ext`"" -ForegroundColor Yellow
  Write-Host "  extension=pdo_mysql" -ForegroundColor Yellow
  Write-Host "  extension=mysqli" -ForegroundColor Yellow
  Write-Host "Arquivo: $ini" -ForegroundColor Yellow
  exit 1
}

Write-Host "Servidor: http://localhost:8080/index.php" -ForegroundColor Cyan
Set-Location $PSScriptRoot
& $phpExe -S localhost:8080
