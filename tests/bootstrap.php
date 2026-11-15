<?php

/**
 * Bootstrap da suíte de testes.
 *
 * Define as constantes de conexão ANTES de o config/bootstrap.php ler o
 * .env (ele só define o que ainda não existe). Assim, nenhum teste consegue
 * abrir conexão com o banco real: se algum esquecer de usar mock, a conexão
 * falha com PDOException em vez de ler/gravar dados de verdade.
 */

define('APP_ENV', 'testing');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '1');
define('DB_NAME', 'brafood_sem_banco_nos_testes');
define('DB_USER', 'nenhum');
define('DB_PASSWORD', '');

require_once __DIR__ . '/../vendor/autoload.php';
