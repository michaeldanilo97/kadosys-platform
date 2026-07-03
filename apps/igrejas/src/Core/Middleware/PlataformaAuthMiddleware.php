<?php

declare(strict_types=1);

namespace Igrejas\Core\Middleware;

use Igrejas\Core\MiddlewareInterface;
use Igrejas\Core\Request;
use Igrejas\Core\Session;

/**
 * Protege o painel administrativo da plataforma (/plataforma/*, ver
 * PlataformaController) - autenticacao totalmente separada da dos
 * usuarios de cada igreja (AuthMiddleware), baseada numa chave mestra
 * unica (ver config/plataforma.php), nao em usuario/senha por pessoa.
 */
final class PlataformaAuthMiddleware implements MiddlewareInterface
{
    public const SESSION_KEY = '_plataforma_admin_autenticado';

    public function __construct(private readonly array $config)
    {
    }

    public function handle(Request $request): void
    {
        if (Session::get(self::SESSION_KEY) !== true) {
            $base = $this->config['base_path'] ?? '';
            header('Location: ' . $base . '/plataforma/entrar');
            exit;
        }
    }
}
