<?php
declare(strict_types=1);

session_set_cookie_params([
 'lifetime' => 0,
  'path' => '/',
  'secure' => false,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/modelo.php';

function password_policy_error_pt(): string
{
  return 'A senha deve ter no mínimo 8 caracteres, com ao menos uma letra maiúscula, uma minúscula e um número.';
}

function password_meets_policy(string $password): bool
{
  if (strlen($password) < 8) {
    return false;
  }
  return (bool) preg_match('/[a-z]/u', $password)
    && (bool) preg_match('/[A-Z]/u', $password)
    && (bool) preg_match('/[0-9]/', $password);
}

function hash_token(string $rawToken): string
{
  return hash('sha256', $rawToken, false);
}

function json_out(array $data, int $code = 200): void
{
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function json_err(string $message, int $code = 400, ?string $errorCode = null): void
{
  $out = ['ok' => false, 'error' => $message];
  if ($errorCode !== null) {
    $out['code'] = $errorCode;
  }
  json_out($out, $code);
}

function current_user_id(PDO $pdo): ?int
{
  if (empty($_SESSION['user_id'])) {
    return null;
  }
  $id = (int) $_SESSION['user_id'];
  $st = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario = ?');
  $st->execute([$id]);
  return $st->fetch() ? $id : null;
}

function require_login(PDO $pdo): int
{
  $uid = current_user_id($pdo);
  if ($uid === null) {
    json_err('Faça login para continuar.', 401);
  }
  return $uid;
}

function user_row(PDO $pdo, int $id): ?array
{
  $st = $pdo->prepare('SELECT * FROM usuarios WHERE id_usuario = ?');
  $st->execute([$id]);
  $r = $st->fetch();
  return $r ?: null;
}

function user_last_rating(PDO $pdo, int $userId): ?array
{
  $st = $pdo->prepare(
    'SELECT estrelas, comentario FROM avaliacoes_app WHERE id_usuario = ? ORDER BY id DESC LIMIT 1'
  );
  $st->execute([$userId]);
  $r = $st->fetch();
  if (!$r) {
    return null;
  }
  return [
    'stars' => (int) $r['estrelas'],
    'comment' => $r['comentario'] ?? '',
  ];
}

function bootstrap_payload(PDO $pdo): array
{
  $uid = current_user_id($pdo);
  $user = $uid !== null ? user_row($pdo, $uid) : null;

  $favoritos = [];
  if ($uid !== null) {
    $st = $pdo->prepare('SELECT id_animal FROM favoritos WHERE id_usuario = ? ORDER BY id_animal');
    $st->execute([$uid]);
    $favoritos = array_map('intval', array_column($st->fetchAll(), 'id_animal'));
  }

  $st = $pdo->query('SELECT id_animal FROM animais WHERE status = "adotado" ORDER BY id_animal');
  $adotados = array_map('intval', array_column($st->fetchAll(), 'id_animal'));

  $ongsRows = $pdo->query(
    'SELECT id_ong, nome, rua, numero, bairro, cidade, estado, telefone FROM ongs ORDER BY id_ong'
  )->fetchAll();
  $ongs = array_map('map_ong_publica', $ongsRows);

  $animaisRows = $pdo
    ->query(
      'SELECT id_animal, nome, tipo, idade, sexo, vacinado, deficiencia, alergia, foto, whatsapp, nome_ong, cadastro_site
       FROM animais WHERE status = "disponivel" ORDER BY id_animal'
    )
    ->fetchAll();
  $animaisLista = [];
  $animaisExtras = [];
  foreach ($animaisRows as $row) {
    $mapped = map_animal_publico($row);
    $animaisLista[] = $mapped;
    if ((int) ($row['cadastro_site'] ?? 0) === 1) {
      $animaisExtras[] = $mapped;
    }
  }

  $den = $pdo
    ->query(
      'SELECT id, denunciante, cidade, endereco, tipo, descricao, contato, foto_url, foto_data_url, data_label FROM denuncias ORDER BY id DESC'
    )
    ->fetchAll();

  $myRating = null;
  if ($uid !== null) {
    $myRating = user_last_rating($pdo, $uid);
  }

  return [
    'ok' => true,
    'meta' => [
      'app_name' => APP_NAME,
      'app_version' => APP_VERSION,
      'password_policy' => password_policy_error_pt(),
    ],
    'user' => $user ? map_usuario_publico($user) : null,
    'my_rating' => $myRating,
    'favoritos' => $favoritos,
    'adotados' => $adotados,
    'ongs' => $ongs,
    'animais' => $animaisLista,
    'animaisExtras' => $animaisExtras,
    'denuncias' => array_map(
      static function (array $d): array {
        return [
          'id' => (int) $d['id'],
          'denunciante' => $d['denunciante'],
          'cidade' => $d['cidade'],
          'endereco' => $d['endereco'],
          'tipo' => $d['tipo'],
          'descricao' => $d['descricao'],
          'contato' => $d['contato'],
          'fotoUrl' => $d['foto_url'] ?? '',
          'fotoDataUrl' => $d['foto_data_url'] ?? '',
          'dataLabel' => $d['data_label'],
        ];
      },
      $den
    ),
  ];
}

function verify_google_id_token(string $token): ?array
{
  if (GOOGLE_CLIENT_ID === '') {
    return null;
  }
  $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($token);
  $ctx = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
  $raw = @file_get_contents($url, false, $ctx);
  if ($raw === false) {
    return null;
  }
  $data = json_decode($raw, true);
  if (!is_array($data) || empty($data['email'])) {
    return null;
  }
  if (($data['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
    return null;
  }
  return $data;
}

// ——— Roteamento ———

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'bootstrap') {
  json_out(bootstrap_payload(db()));
}

if ($method !== 'POST') {
  json_err('Método não permitido', 405);
}

$raw = file_get_contents('php://input');
$input = $raw !== '' ? json_decode($raw, true) : [];
if (!is_array($input)) {
  $input = [];
}
$action = $input['action'] ?? $action;
$pdo = db();
if (db_is_mysql()) {
  require_once __DIR__ . '/includes/funcoes.php';
  garantir_campos_dashboard($pdo);
}

switch ($action) {
  case 'register':
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = trim((string) ($input['password'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      json_err('E-mail inválido.');
    }
    if (!password_meets_policy($password)) {
      json_err(password_policy_error_pt());
    }
    try {
      $st = $pdo->prepare(
        'INSERT INTO usuarios (email, senha_hash, nome, provedor_login, tipo) VALUES (?, ?, ?, ?, ?)'
      );
      $st->execute([$email, password_hash($password, PASSWORD_DEFAULT), $email, 'email', 'usuario']);
    } catch (PDOException $e) {
      json_err('Este e-mail já está cadastrado.');
    }
    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    json_out(bootstrap_payload($pdo));
    break;

  case 'login':
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = trim((string) ($input['password'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      json_err('Informe um e-mail válido.');
    }
    if ($password === '') {
      json_err('Informe a senha.');
    }
    $st = $pdo->prepare('SELECT * FROM usuarios WHERE LOWER(TRIM(email)) = ?');
    $st->execute([$email]);
    $user = $st->fetch();
    if (!$user) {
      json_err(
        'Este e-mail ainda não está cadastrado. Use «Criar minha conta» (mesmo e-mail e senha) ou o botão de confirmação quando o app oferecer.',
        400,
        'EMAIL_NOT_FOUND'
      );
    }
    $hash = $user['senha_hash'] ?? $user['senha'] ?? null;
    if ($hash === null || $hash === '') {
      $prov = (string) ($user['provedor_login'] ?? '');
      if ($prov === 'google') {
        json_err(
          'Esta conta foi criada com Google. Não use senha aqui: clique em «Entrar com Google».',
          400,
          'GOOGLE_ONLY'
        );
      }
      json_err('Não é possível entrar com senha nesta conta. Use a recuperação de senha ou o login com Google, se for o caso.');
    }
    if (!password_verify($password, $hash)) {
      json_err(
        'Senha incorreta. Verifique caps lock; se esqueceu, use «Esqueci minha senha» abaixo. No cadastro, a senha precisa: 8+ caracteres, letra maiúscula, minúscula e um número (ex.: MinhaSenh4).',
        400,
        'WRONG_PASSWORD'
      );
    }
    $_SESSION['user_id'] = (int) $user['id_usuario'];
    json_out(bootstrap_payload($pdo));
    break;

  case 'logout':
    $_SESSION = [];
    if (session_id() !== '') {
      session_destroy();
    }
    session_start();
    json_out(bootstrap_payload(db()));
    break;

  case 'google_login':
    $data = verify_google_id_token((string) ($input['credential'] ?? ''));
    if ($data === null) {
      json_err('Token Google inválido ou Google não configurado no servidor.');
    }
    $email = strtolower(trim((string) $data['email']));
    $name = $data['name'] ?? '';
    $picture = $data['picture'] ?? '';
    $st = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE LOWER(TRIM(email)) = ?');
    $st->execute([$email]);
    $row = $st->fetch();
    if ($row) {
      $uid = (int) $row['id_usuario'];
      $pdo
        ->prepare(
          'UPDATE usuarios SET nome = ?, url_avatar = ?, provedor_login = ? WHERE id_usuario = ?'
        )
        ->execute([$name, $picture, 'google', $uid]);
      $_SESSION['user_id'] = $uid;
    } else {
      $pdo
        ->prepare(
          'INSERT INTO usuarios (email, senha_hash, nome, url_avatar, provedor_login, tipo) VALUES (?, NULL, ?, ?, ?, ?)'
        )
        ->execute([$email, $name, $picture, 'google', 'usuario']);
      $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    }
    json_out(bootstrap_payload($pdo));
    break;

  case 'set_favorites':
    $uid = require_login($pdo);
    $ids = $input['ids'] ?? [];
    if (!is_array($ids)) {
      json_err('Lista de favoritos inválida.');
    }
    $pdo->prepare('DELETE FROM favoritos WHERE id_usuario = ?')->execute([$uid]);
    $ins = $pdo->prepare('INSERT INTO favoritos (id_usuario, id_animal) VALUES (?, ?)');
    foreach ($ids as $id) {
      $ins->execute([$uid, (int) $id]);
    }
    json_out(bootstrap_payload($pdo));
    break;

  case 'add_adoption':
    $uid = require_login($pdo);
    $aid = (int) ($input['animal_id'] ?? 0);
    if ($aid < 1) {
      json_err('Animal inválido.');
    }
    $pdo->prepare('UPDATE animais SET status = ? WHERE id_animal = ?')->execute(['adotado', $aid]);
    $pdo->prepare(
      sql_insert_ignore() . ' INTO adocoes (id_usuario, id_animal, status, data) VALUES (?, ?, ?, NOW())'
    )->execute([$uid, $aid, 'aprovado']);
    $pdo->prepare('DELETE FROM favoritos WHERE id_usuario = ? AND id_animal = ?')->execute([$uid, $aid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'remove_adoption':
    require_login($pdo);
    $aid = (int) ($input['animal_id'] ?? 0);
    $pdo->prepare('UPDATE animais SET status = ? WHERE id_animal = ?')->execute(['disponivel', $aid]);
    $pdo->prepare('DELETE FROM adocoes WHERE id_animal = ?')->execute([$aid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'add_ong':
    $nome = trim((string) ($input['nome'] ?? ''));
    $rua = trim((string) ($input['rua'] ?? ''));
    $numero = trim((string) ($input['numero'] ?? ''));
    $bairro = trim((string) ($input['bairro'] ?? ''));
    $cidade = trim((string) ($input['cidade'] ?? ''));
    $estado = trim((string) ($input['estado'] ?? ''));
    $contato = trim((string) ($input['contato'] ?? ''));
    if ($nome === '' || $rua === '' || $numero === '' || $bairro === '' || $cidade === '' || $estado === '' || $contato === '') {
      json_err('Preencha todos os campos da ONG.');
    }
    $st = $pdo->prepare(
      'INSERT INTO ongs (nome, rua, numero, bairro, cidade, estado, telefone, ativa) VALUES (?,?,?,?,?,?,?,1)'
    );
    $st->execute([$nome, $rua, $numero, $bairro, $cidade, $estado, $contato]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'remove_ong':
    $id = (int) ($input['id'] ?? 0);
    if ($id < 1) {
      json_err('ONG inválida.');
    }
    $pdo->prepare('DELETE FROM ongs WHERE id_ong = ?')->execute([$id]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'add_animal':
    $nome = trim((string) ($input['nome'] ?? ''));
    $tipo = trim((string) ($input['tipo'] ?? ''));
    $idade = trim((string) ($input['idade'] ?? ''));
    $sexo = trim((string) ($input['sexo'] ?? ''));
    $vacinas = trim((string) ($input['vacinas'] ?? ''));
    $deficiencia = trim((string) ($input['deficiencia'] ?? ''));
    $alergia = trim((string) ($input['alergia'] ?? ''));
    $foto = trim((string) ($input['foto'] ?? ''));
    $whatsapp = trim((string) ($input['whatsapp'] ?? ''));
    $ong = trim((string) ($input['ong'] ?? ''));
    if ($nome === '' || $idade === '' || $vacinas === '' || $foto === '') {
      json_err('Preencha os campos obrigatórios do animal.');
    }
    $nextId = (int) $pdo->query(
      'SELECT GREATEST(COALESCE(MAX(id_animal), 0), 10000) + 1 FROM animais'
    )->fetchColumn();
    $st = $pdo->prepare(
      'INSERT INTO animais (id_animal, nome, tipo, idade, sexo, vacinado, deficiencia, alergia, foto, whatsapp, nome_ong, status, cadastro_site)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    $st->execute([
      $nextId,
      $nome,
      $tipo,
      $idade,
      $sexo,
      $vacinas,
      $deficiencia ?: 'Nenhuma',
      $alergia ?: 'Não possui',
      $foto,
      $whatsapp,
      $ong,
      'disponivel',
      1,
    ]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'remove_animal':
    require_login($pdo);
    $aid = (int) ($input['animal_id'] ?? 0);
    if ($aid < 1) {
      json_err('Animal inválido.');
    }
    $chk = $pdo->prepare('SELECT 1 FROM animais WHERE id_animal = ? AND cadastro_site = 1');
    $chk->execute([$aid]);
    if (!$chk->fetch()) {
      json_err('Só é possível remover animais cadastrados pelo site (não os da lista fixa).');
    }
    $pdo->prepare('DELETE FROM favoritos WHERE id_animal = ?')->execute([$aid]);
    $pdo->prepare('DELETE FROM adocoes WHERE id_animal = ?')->execute([$aid]);
    $pdo->prepare('DELETE FROM animais WHERE id_animal = ?')->execute([$aid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'add_denuncia':
    $denunciante = trim((string) ($input['denunciante'] ?? ''));
    $cidade = trim((string) ($input['cidade'] ?? ''));
    $endereco = trim((string) ($input['endereco'] ?? ''));
    $tipo = trim((string) ($input['tipo'] ?? ''));
    $descricao = trim((string) ($input['descricao'] ?? ''));
    $contato = trim((string) ($input['contato'] ?? ''));
    $fotoUrl = trim((string) ($input['fotoUrl'] ?? ''));
    $fotoDataUrl = (string) ($input['fotoDataUrl'] ?? '');
    if (
      $denunciante === '' ||
      $cidade === '' ||
      $endereco === '' ||
      $tipo === '' ||
      $descricao === '' ||
      $contato === ''
    ) {
      json_err('Preencha todos os campos obrigatórios da denúncia.');
    }
    if (strlen($fotoDataUrl) > 2_000_000) {
      json_err('Imagem da denúncia muito grande.');
    }
    $dataLabel = (new DateTimeImmutable('now'))->format('d/m/Y H:i');
    $st = $pdo->prepare(
      'INSERT INTO denuncias (denunciante, cidade, endereco, tipo, descricao, contato, foto_url, foto_data_url, data_label, status) VALUES (?,?,?,?,?,?,?,?,?,?)'
    );
    $st->execute([
      $denunciante,
      $cidade,
      $endereco,
      $tipo,
      $descricao,
      $contato,
      $fotoUrl,
      $fotoDataUrl,
      $dataLabel,
      'pendente',
    ]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'set_profile_photo':
    $uid = require_login($pdo);
    $dataUrl = (string) ($input['foto_data_url'] ?? '');
    if (strlen($dataUrl) > 1_500_000) {
      json_err('Imagem muito grande.');
    }
    if ($dataUrl !== '' && strpos($dataUrl, 'data:image/') !== 0) {
      json_err('Formato de imagem inválido.');
    }
    $pdo->prepare('UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?')->execute([$dataUrl, $uid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'clear_profile_photo':
    $uid = require_login($pdo);
    $pdo->prepare('UPDATE usuarios SET foto_perfil = NULL WHERE id_usuario = ?')->execute([$uid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'set_background_photo':
    $uid = require_login($pdo);
    $dataUrl = (string) ($input['foto_data_url'] ?? '');
    if (strlen($dataUrl) > 1_500_000) {
      json_err('Imagem de fundo muito grande.');
    }
    if ($dataUrl !== '' && strpos($dataUrl, 'data:image/') !== 0) {
      json_err('Formato de imagem inválido.');
    }
    $pdo->prepare('UPDATE usuarios SET foto_fundo = ? WHERE id_usuario = ?')->execute([$dataUrl ?: null, $uid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'clear_background_photo':
    $uid = require_login($pdo);
    $pdo->prepare('UPDATE usuarios SET foto_fundo = NULL WHERE id_usuario = ?')->execute([$uid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'update_profile':
    $uid = require_login($pdo);
    $display = trim((string) ($input['display_name'] ?? ''));
    $tel = trim((string) ($input['telefone'] ?? ''));
    if ($display === '') {
      json_err('Informe um nome de exibição.');
    }
    if (strlen($display) > 200) {
      json_err('Nome muito longo.');
    }
    if (strlen($tel) > 40) {
      json_err('Telefone inválido.');
    }
    $pdo->prepare('UPDATE usuarios SET nome = ?, telefone = ? WHERE id_usuario = ?')->execute([$display, $tel, $uid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'update_notifications':
    $uid = require_login($pdo);
    $app = !empty($input['notif_app']);
    $em = !empty($input['notif_email']);
    $wa = !empty($input['notif_whatsapp']);
    $freq = (string) ($input['notif_freq'] ?? 'evento');
    $allowed = ['evento', 'diario', 'programado'];
    if (!in_array($freq, $allowed, true)) {
      $freq = 'evento';
    }
    $pdo
      ->prepare('UPDATE usuarios SET notif_app = ?, notif_email = ?, notif_whatsapp = ?, notif_freq = ? WHERE id_usuario = ?')
      ->execute([$app ? 1 : 0, $em ? 1 : 0, $wa ? 1 : 0, $freq, $uid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'change_password':
    $uid = require_login($pdo);
    $u = user_row($pdo, $uid);
    if (!$u) {
      json_err('Usuário não encontrado.');
    }
    $current = trim((string) ($input['current_password'] ?? ''));
    $newPass = trim((string) ($input['new_password'] ?? ''));
    if (!password_meets_policy($newPass)) {
      json_err(password_policy_error_pt());
    }
    $hash = $u['senha_hash'] ?? $u['senha'] ?? null;
    if ($hash !== null && $hash !== '') {
      if ($current === '' || !password_verify($current, $hash)) {
        json_err('Senha atual incorreta.');
      }
    }
    $pdo
      ->prepare('UPDATE usuarios SET senha_hash = ?, provedor_login = ? WHERE id_usuario = ?')
      ->execute([password_hash($newPass, PASSWORD_DEFAULT), 'email', $uid]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'forgot_password':
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      json_err('Informe um e-mail válido.');
    }
    $st = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE LOWER(TRIM(email)) = ?');
    $st->execute([$email]);
    $row = $st->fetch();
    $payload = [
      'ok' => true,
      'message' => 'Se o e-mail estiver cadastrado, você poderá redefinir a senha com o código enviado.',
    ];
    if ($row) {
      $userId = (int) $row['id_usuario'];
      $raw = bin2hex(random_bytes(32));
      $exp = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
      $pdo->prepare('DELETE FROM recuperacao_senha WHERE id_usuario = ?')->execute([$userId]);
      $pdo
        ->prepare('INSERT INTO recuperacao_senha (id_usuario, token_hash, expira_em) VALUES (?,?,?)')
        ->execute([$userId, hash_token($raw), $exp]);
      if (PASSWORD_RESET_DEMO_REVEAL) {
        $payload['reset_token'] = $raw;
        $payload['message'] =
          'Token gerado (modo demonstração). Em produção seria enviado por e-mail. Use-o abaixo para redefinir a senha.';
      }
    }
    json_out($payload);
    break;

  case 'reset_password':
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $token = trim((string) ($input['token'] ?? ''));
    $newPass = trim((string) ($input['new_password'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      json_err('E-mail inválido.');
    }
    if ($token === '' || strlen($token) < 16) {
      json_err('Token inválido.');
    }
    if (!password_meets_policy($newPass)) {
      json_err(password_policy_error_pt());
    }
    $st = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE LOWER(TRIM(email)) = ?');
    $st->execute([$email]);
    $ur = $st->fetch();
    if (!$ur) {
      json_err('Não foi possível redefinir a senha.');
    }
    $userId = (int) $ur['id_usuario'];
    $chk = $pdo->prepare(
      'SELECT id FROM recuperacao_senha WHERE id_usuario = ? AND token_hash = ? AND expira_em > ' . sql_now_expr()
    );
    $chk->execute([$userId, hash_token($token)]);
    if (!$chk->fetch()) {
      json_err('Token inválido ou expirado. Solicite nova recuperação.');
    }
    $pdo->prepare('DELETE FROM recuperacao_senha WHERE id_usuario = ?')->execute([$userId]);
    $pdo
      ->prepare('UPDATE usuarios SET senha_hash = ?, provedor_login = ? WHERE id_usuario = ?')
      ->execute([password_hash($newPass, PASSWORD_DEFAULT), 'email', $userId]);
    json_out(['ok' => true, 'message' => 'Senha redefinida. Você já pode entrar com a nova senha.']);
    break;

  case 'submit_rating':
    $uid = require_login($pdo);
    $stars = (int) ($input['stars'] ?? 0);
    $comment = trim((string) ($input['comment'] ?? ''));
    if ($stars < 1 || $stars > 5) {
      json_err('Selecione de 1 a 5 estrelas.');
    }
    if (strlen($comment) > 2000) {
      json_err('Comentário muito longo.');
    }
    $pdo->prepare('INSERT INTO avaliacoes_app (id_usuario, estrelas, comentario) VALUES (?,?,?)')->execute([$uid, $stars, $comment]);
    json_out(bootstrap_payload($pdo));
    break;

  case 'request_account_deletion':
    $uid = require_login($pdo);
    $raw = bin2hex(random_bytes(24));
    $exp = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');
    $pdo->prepare('DELETE FROM tokens_exclusao_conta WHERE id_usuario = ?')->execute([$uid]);
    $pdo
      ->prepare('INSERT INTO tokens_exclusao_conta (id_usuario, token_hash, expira_em) VALUES (?,?,?)')
      ->execute([$uid, hash_token($raw), $exp]);
    $out = [
      'ok' => true,
      'message' => 'Confirme a exclusão informando o código e, se aplicável, sua senha.',
    ];
    if (PASSWORD_RESET_DEMO_REVEAL) {
      $out['deletion_token'] = $raw;
      $out['message'] =
        'Código de exclusão gerado (demonstração). Em produção seria validado por e-mail ou segundo fator.';
    }
    json_out($out);
    break;

  case 'confirm_account_deletion':
    $uid = require_login($pdo);
    $token = trim((string) ($input['deletion_token'] ?? ''));
    $password = trim((string) ($input['password'] ?? ''));
    if ($token === '' || strlen($token) < 8) {
      json_err('Informe o código de confirmação.');
    }
    $u = user_row($pdo, $uid);
    if (!$u) {
      json_err('Sessão inválida.');
    }
    $chk = $pdo->prepare(
      'SELECT id FROM tokens_exclusao_conta WHERE id_usuario = ? AND token_hash = ? AND expira_em > ' . sql_now_expr()
    );
    $chk->execute([$uid, hash_token($token)]);
    if (!$chk->fetch()) {
      json_err('Código inválido ou expirado. Solicite um novo código.');
    }
    $hash = $u['senha_hash'] ?? $u['senha'] ?? null;
    if ($hash !== null && $hash !== '') {
      if ($password === '' || !password_verify($password, $hash)) {
        json_err('Informe sua senha atual para confirmar a exclusão.');
      }
    }
    $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = ?')->execute([$uid]);
    $_SESSION = [];
    if (session_id() !== '') {
      session_destroy();
    }
    session_start();
    json_out(bootstrap_payload(db()));
    break;

  default:
    json_err('Ação desconhecida.', 400);
}
