<?php

declare(strict_types=1);

namespace Igrejas\Core\Middleware;

use Igrejas\Core\MiddlewareInterface;
use Igrejas\Core\Request;
use Igrejas\Core\TenantResolver;
use Igrejas\Models\ConfiguracaoIgreja;
use Igrejas\Models\Plano;

/**
 * Bloqueia o acesso a modulos que nao fazem parte do plano contratado da
 * igreja (ver Igrejas\Models\Plano). O slug do modulo e extraido da propria
 * URI (/dashboard/{slug}/...), o que permite reaproveitar esta middleware
 * em qualquer rota de modulo sem precisar de suporte a parametros de rota
 * na interface de middleware (que hoje so recebe a Request).
 */
final class PlanoMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function handle(Request $request): void
    {
        $base = $this->config['base_path'] ?? '';
        $uri = $request->uri;

        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        if (!preg_match('#^/dashboard/([a-z\-]+)#', $uri, $matches)) {
            return;
        }

        $slug = $matches[1];
        $planoAtual = ConfiguracaoIgreja::atual()->plano;
        $emTrial = TenantResolver::atual()?->metodoPagamento === 'trial';

        if (Plano::disponivel($planoAtual, $slug, $emTrial)) {
            return;
        }

        header('Location: ' . $base . '/dashboard/plano-bloqueado?modulo=' . urlencode($slug));
        exit;
    }
}
