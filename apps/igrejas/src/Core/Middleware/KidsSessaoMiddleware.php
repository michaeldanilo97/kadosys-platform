<?php

declare(strict_types=1);

namespace Igrejas\Core\Middleware;

use Igrejas\Core\MiddlewareInterface;
use Igrejas\Core\Request;
use Igrejas\Core\Session;
use Igrejas\Models\KidsCrianca;

/**
 * Protege as rotas publicas de "modo crianca" (/kids, ver
 * KidsAppController) - exige uma sessao valida criada pelo login por
 * PIN (ver KidsLoginController), independente do login administrativo
 * (Igrejas\Core\Auth). Sem isso, redireciona pra tela de login da
 * crianca.
 */
final class KidsSessaoMiddleware implements MiddlewareInterface
{
    public const SESSION_KEY = 'kids_sessao_crianca_id';

    public function __construct(private readonly array $config)
    {
    }

    public function handle(Request $request): void
    {
        $criancaId = Session::get(self::SESSION_KEY);

        if (!is_int($criancaId) || KidsCrianca::find($criancaId) === null) {
            $base = $this->config['base_path'] ?? '';
            header('Location: ' . $base . '/kids/entrar');
            exit;
        }
    }
}
