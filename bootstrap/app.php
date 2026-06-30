<?php

declare(strict_types=1);

/**
 * bootstrap/app.php
 *
 * Ponto unico de inicializacao da Kadosys Platform. Carrega o
 * autoload do Composer e inicializa a Application, retornando
 * a instancia pronta para tratar a requisicao (via index.php)
 * ou para uso em comandos de console.
 */

use Kadosys\Core\Core\Application;

require __DIR__ . '/../vendor/autoload.php';

return Application::bootstrap(dirname(__DIR__));
