<?php
declare(strict_types=1);

function mask_cpf(?string $cpf): string
{
  if ($cpf === null || $cpf === '') {
    return '';
  }
  $d = preg_replace('/\D/', '', $cpf);
  if (strlen($d) < 4) {
    return '***';
  }
  return '***.***.***-' . substr($d, -2);
}

/** Mapeia linha de usuarios para o formato JSON esperado pelo front-end. */
function map_usuario_publico(array $u): array
{
  $cpfRaw = $u['cpf'] ?? '';
  $hash = $u['senha_hash'] ?? $u['senha'] ?? null;

  return [
    'email' => $u['email'],
    'display_name' => $u['nome'] ?? '',
    'avatar_url' => $u['url_avatar'] ?? '',
    'login_provider' => $u['provedor_login'] ?? 'email',
    'foto_perfil_data' => $u['foto_perfil'] ?? '',
    'foto_fundo_data' => $u['foto_fundo'] ?? '',
    'cpf_masked' => mask_cpf($cpfRaw !== '' ? $cpfRaw : null),
    'telefone' => $u['telefone'] ?? '',
    'notif_app' => (int) ($u['notif_app'] ?? 1),
    'notif_email' => (int) ($u['notif_email'] ?? 1),
    'notif_whatsapp' => (int) ($u['notif_whatsapp'] ?? 0),
    'notif_freq' => $u['notif_freq'] ?? 'evento',
    'has_password' => !empty($hash),
  ];
}

/** Mapeia linha de animais para o formato do front-end (id, vacinas, ong). */
function map_animal_publico(array $a): array
{
  return [
    'id' => (int) $a['id_animal'],
    'nome' => $a['nome'],
    'tipo' => $a['tipo'] ?? '',
    'idade' => (string) ($a['idade'] ?? ''),
    'sexo' => $a['sexo'] ?? '',
    'vacinas' => $a['vacinado'] ?? '',
    'deficiencia' => $a['deficiencia'] ?? 'Nenhuma',
    'alergia' => $a['alergia'] ?? 'Não possui',
    'foto' => $a['foto'] ?? '',
    'whatsapp' => $a['whatsapp'] ?? '',
    'ong' => $a['nome_ong'] ?? '',
  ];
}

function map_ong_publica(array $o): array
{
  return [
    'id' => (int) $o['id_ong'],
    'nome' => $o['nome'],
    'rua' => $o['rua'] ?? '',
    'numero' => $o['numero'] ?? '',
    'bairro' => $o['bairro'] ?? '',
    'cidade' => $o['cidade'] ?? '',
    'estado' => $o['estado'] ?? '',
    'contato' => $o['telefone'] ?? $o['contato'] ?? '',
  ];
}
