<?php

declare(strict_types=1);

/**
 * Rotas do aplicativo "escola".
 *
 * A variavel $router (instancia de Kadosys\Core\Routing\Router)
 * esta disponivel automaticamente neste arquivo.
 *
 * Aplicativo ainda nao implementado nesta v1 — apenas infraestrutura
 * base, conforme especificacao da Kadosys Platform.
 */

$router->get("/", function () {
    return "<h1>Aplicativo Escola</h1><p>Infraestrutura pronta. Modulos de negocio ainda nao implementados nesta v1.</p>";
})->setName("escola.home");
