<?php

declare(strict_types=1);

/**
 * helpers.php
 *
 * Funcoes globais auxiliares da Kadosys Platform, carregadas
 * automaticamente via composer (autoload.files). Todas respeitam
 * APP_URL e APP_BASE_PATH, garantindo que nenhuma URL fixa precise
 * existir no codigo da plataforma ou dos aplicativos.
 */

use Kadosys\Core\Core\Application;
use Kadosys\Core\Core\Config;
use Kadosys\Core\Core\Env;
use Kadosys\Core\Http\Response;
use Kadosys\Core\Security\Csrf;
use Kadosys\Core\Security\Session;
use Kadosys\Core\View\View;

if (!function_exists('env')) {
    /**
     * Recupera uma variavel de ambiente do .env.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Recupera (ou define, se $value for informado) uma configuracao.
     */
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('base_path')) {
    /**
     * Caminho absoluto a partir da raiz da plataforma (public_html/).
     */
    function base_path(string $path = ''): string
    {
        return Application::getInstance()->basePath($path);
    }
}

if (!function_exists('app_path')) {
    /**
     * Caminho absoluto a partir da pasta do aplicativo ativo (apps/{app}).
     */
    function app_path(string $path = ''): string
    {
        return Application::getInstance()->appPath($path);
    }
}

if (!function_exists('url')) {
    /**
     * Gera uma URL absoluta combinando APP_URL + APP_BASE_PATH + caminho relativo.
     * Nunca utilizar URLs fixas no codigo: sempre usar url('/algum/caminho').
     */
    function url(string $path = ''): string
    {
        $appUrl = rtrim((string) Config::get('app.url', ''), '/');
        $basePath = trim((string) Config::get('app.base_path', ''), '/');
        $path = '/' . trim($path, '/');

        $prefix = $basePath !== '' ? '/' . $basePath : '';

        $result = $appUrl . $prefix . $path;

        return $path === '/' ? rtrim($result, '/') . '/' : $result;
    }
}

if (!function_exists('asset')) {
    /**
     * Gera a URL para um asset estatico do aplicativo ativo
     * (apps/{app}/Assets/...) respeitando APP_BASE_PATH.
     */
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('route')) {
    /**
     * Gera a URL de uma rota nomeada, com parametros opcionais.
     *
     * @param array<string, string|int> $params
     */
    function route(string $name, array $params = []): string
    {
        $router = Application::getInstance()->router();

        return url($router->urlFor($name, $params));
    }
}

if (!function_exists('redirect')) {
    /**
     * Cria uma Response de redirecionamento para a URL/rota informada.
     */
    function redirect(string $path, int $statusCode = 302): Response
    {
        $target = str_starts_with($path, 'http') ? $path : url($path);

        return Response::redirect($target, $statusCode);
    }
}

if (!function_exists('view')) {
    /**
     * Renderiza uma view do aplicativo ativo (ou do Core como fallback)
     * e retorna como Response HTML.
     *
     * @param array<string, mixed> $data
     */
    function view(string $view, array $data = []): Response
    {
        return Response::html(View::render($view, $data));
    }
}

if (!function_exists('csrf')) {
    /**
     * Retorna o token CSRF atual da sessao (gerando um novo se necessario).
     */
    function csrf(): string
    {
        return (new Csrf())->token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Retorna um input hidden HTML pronto para uso em formularios.
     */
    function csrf_field(): string
    {
        $fieldName = (string) Config::get('security.csrf_token_name', '_csrf_token');
        $token = csrf();

        return sprintf('<input type="hidden" name="%s" value="%s">', $fieldName, $token);
    }
}

if (!function_exists('session')) {
    /**
     * Acessa (get) ou define (put) um valor de sessao.
     */
    function session(string $key, mixed $default = null): mixed
    {
        return Session::get($key, $default);
    }
}

if (!function_exists('old')) {
    /**
     * Recupera o valor antigo de um campo de formulario (flash data),
     * util para repopular formularios apos erro de validacao.
     */
    function old(string $key, mixed $default = null): mixed
    {
        $oldInput = Session::getFlash('_old_input', []);

        return is_array($oldInput) ? ($oldInput[$key] ?? $default) : $default;
    }
}

if (!function_exists('abort')) {
    /**
     * Interrompe a execucao imediatamente retornando um erro HTTP.
     */
    function abort(int $statusCode, string $message = ''): never
    {
        $message = $message !== '' ? $message : "Erro {$statusCode}";

        http_response_code($statusCode);
        echo $message;
        exit;
    }
}

if (!function_exists('dd')) {
    /**
     * "Dump and Die": exibe variaveis de forma legivel e encerra a execucao.
     * Apenas para uso em ambiente de desenvolvimento.
     */
    function dd(mixed ...$vars): never
    {
        echo '<pre style="background:#1e1e2e;color:#f8f8f2;padding:1rem;border-radius:8px;">';

        foreach ($vars as $var) {
            var_dump($var);
        }

        echo '</pre>';
        exit;
    }
}
