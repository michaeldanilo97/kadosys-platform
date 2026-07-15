<?php

declare(strict_types=1);

namespace Barbearias\Core\Middleware;

use Barbearias\Core\Auth;
use Barbearias\Core\MiddlewareInterface;
use Barbearias\Core\Request;

/**
 * Garante que apenas usuarios autenticados acessem rotas protegidas
 * (ex.: /dashboard e suas subpaginas). Visitantes sao redirecionados
 * para a tela de login.
 *
 * Ainda nao tem bloqueio de plano/fatura vencida (Barbearias nao tem
 * cobranca de assinatura implementada nesta fase inicial) - ver o
 * equivalente em Igrejas\Core\Middleware\AuthMiddleware quando essa
 * regra for adicionada aqui tambem.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function handle(Request $request): void
    {
        $auth = new Auth($this->config);

        if (!$auth->check()) {
            $base = $this->config['base_path'] ?? '';
            header('Location: ' . $base . '/login');
            exit;
        }
    }
}
