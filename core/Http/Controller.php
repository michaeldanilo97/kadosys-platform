<?php

declare(strict_types=1);

namespace Kadosys\Core\Http;

use Kadosys\Core\Security\Auth;
use Kadosys\Core\View\View;

/**
 * Controller
 *
 * Classe base abstrata para todos os Controllers da plataforma.
 * Oferece metodos utilitarios comuns (view, json, redirect, auth)
 * para que os Controllers dos aplicativos nao dependam diretamente
 * de helpers globais, favorecendo testabilidade.
 */
abstract class Controller
{
    /**
     * Renderiza uma view e retorna como Response HTML.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = []): Response
    {
        return Response::html(View::render($view, $data));
    }

    /**
     * @param array<string, mixed>|object $data
     */
    protected function json(array|object $data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }

    protected function redirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }

    protected function auth(): Auth
    {
        return \Kadosys\Core\Core\Container::getInstance()->resolve(Auth::class);
    }
}
