<?php

declare(strict_types=1);

namespace Academias\Core\Middleware;

use Academias\Core\Auth;
use Academias\Core\MiddlewareInterface;
use Academias\Core\Request;

/**
 * Garante que apenas visitantes nao autenticados acessem rotas como
 * /login. Usuarios ja autenticados sao redirecionados direto para o
 * dashboard.
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
