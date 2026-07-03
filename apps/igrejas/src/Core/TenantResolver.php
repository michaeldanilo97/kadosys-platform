<?php

declare(strict_types=1);

namespace Igrejas\Core;

use Igrejas\Models\Tenant;

/**
 * Resolve, a partir do Host da requisicao, se ela e de um subdominio de
 * uma igreja provisionada automaticamente (ver Provisionador) - se for,
 * troca o banco de dados usado pro resto da requisicao
 * (Database::usarCredenciais()) pro banco daquela igreja especifica, de
 * forma transparente pro resto do sistema (nenhum Model/Controller
 * existente precisa saber disso).
 *
 * Se o Host nao bater com nenhum subdominio de igreja - o caso de toda
 * requisicao ate esta funcionalidade ser configurada (CPANEL_ROOT_DOMAIN
 * vazio), e tambem da instalacao "central" atual (kadosys.com.br) - esta
 * funcao nao faz nada, e o sistema continua usando o banco fixo de
 * config/database.php exatamente como sempre funcionou.
 */
final class TenantResolver
{
    public static function resolver(string $host): void
    {
        $cpanelConfig = require dirname(__DIR__, 2) . '/config/cpanel.php';
        $rootDomain = $cpanelConfig['root_domain'];

        if ($rootDomain === '' || !str_ends_with($host, '.' . $rootDomain)) {
            return;
        }

        try {
            $tenant = Tenant::buscarPorSubdominio($host);
        } catch (\Throwable) {
            // Falha ao consultar o banco central (ex.: fora do ar) nao
            // pode derrubar a requisicao inteira - so segue sem trocar
            // de banco, equivalente a "nenhuma igreja encontrada nesse
            // subdominio".
            return;
        }

        if ($tenant === null || $tenant->status !== 'ativo') {
            return;
        }

        Database::usarCredenciais($tenant->dbName, $tenant->dbUser, $tenant->dbPassword);
    }
}
