<?php
/**
 * Copie para config.php e ajuste.
 * Credenciais sensíveis: copie config.local.example.php → config.local.php (não versionado).
 */
declare(strict_types=1);

/** mysql | sqlite */
const DB_DRIVER = 'mysql';

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'vidadequatropatas';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

/** Usado só quando DB_DRIVER = sqlite */
const DB_PATH = __DIR__ . '/data/app.sqlite';

const GOOGLE_CLIENT_ID = ''; // mesmo ID do cliente OAuth Web usado no front-end

/** Em produção, defina como false: o token de recuperação só deve ir por e-mail. */
const PASSWORD_RESET_DEMO_REVEAL = true;

const APP_NAME = 'Vida de Quatro Patas';
const APP_VERSION = '1.0.0-fase2';
