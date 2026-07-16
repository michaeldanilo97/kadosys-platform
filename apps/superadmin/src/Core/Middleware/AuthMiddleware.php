<?php

declare(strict_types=1);

namespace Superadmin\Core\Middleware;

use Superadmin\Core\MiddlewareInterface;
use Superadmin\Core\Request;
use Superadmin\Core\Session;

/**
 * Protege todo o painel Super Admin com base numa chave mestra unica
 * (ver config/auth.php) - mesmo padrao do painel /plataforma do Igrejas
 * (Igrejas\Core\Middleware\PlataformaAuthMiddleware), sem usuario/senha
 * por pessoa.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public const SESSION_KEY = '_superadmin_autenticado';

    public function __construct(private readonly array $config)
    {
    }

    public function handle(Request $request): void
    {
        if (Session::get(self::SESSION_KEY) !== true) {
            $base = $this->config['base_path'] ?? '';
            header('Location: ' . $base . '/entrar');
            exit;
        }
    }
}
