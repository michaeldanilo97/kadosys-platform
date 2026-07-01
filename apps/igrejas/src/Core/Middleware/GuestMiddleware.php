<?php

declare(strict_types=1);

namespace Igrejas\Core\Middleware;

use Igrejas\Core\Auth;
use Igrejas\Core\MiddlewareInterface;
use Igrejas\Core\Request;

/**
 * Garante que apenas visitantes nao autenticados acessem rotas como
 * /login e /esqueci-senha. Usuarios ja autenticados sao redirecionados
 * direto para o dashboard.
 */
final class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function handle(Request $request): void
    {
        $auth = new Auth($this->config);

        if ($auth->check()) {
            $base = $this->config['base_path'] ?? '';
            header('Location: ' . $base . '/dashboard');
            exit;
        }
    }
}
