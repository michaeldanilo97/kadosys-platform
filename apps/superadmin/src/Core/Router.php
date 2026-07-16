<?php

declare(strict_types=1);

namespace Superadmin\Core;

/**
 * Router minimalista baseado em correspondencia exata de rota + metodo.
 *
 * Suporta parametros dinamicos ({id}), middlewares por rota e o prefixo
 * de base (BASE_PATH) necessario para a aplicacao funcionar dentro de
 * /apps/superadmin sem alterar a estrutura de pastas do site institucional.
 */
final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, handler:mixed, middlewares:array}> */
    private array $routes = [];

    public function __construct(
        private readonly Request $request,
        private readonly string $basePath,
        private readonly array $config = [],
    ) {
    }

    public function get(string $pattern, mixed $handler, array $middlewares = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $middlewares);
    }

    public function post(string $pattern, mixed $handler, array $middlewares = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $middlewares);
    }

    private function addRoute(string $method, string $pattern, mixed $handler, array $middlewares): void
    {
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(): void
    {
        $uri = $this->request->uri;

        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }

        $uri = $uri === '' ? '/' : $uri;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->request->method) {
                continue;
            }

            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }

            $params = array_filter(
                $matches,
                static fn ($key) => is_string($key),
                ARRAY_FILTER_USE_KEY
            );

            foreach ($route['middlewares'] as $middlewareClass) {
                /** @var MiddlewareInterface $middleware */
                $middleware = new $middlewareClass($this->config);
                $middleware->handle($this->request);
            }

            $this->callHandler($route['handler'], $params);

            return;
        }

        $this->notFound();
    }

    private function callHandler(mixed $handler, array $params): void
    {
        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;
            $controller = new $controllerClass($this->request, $this->config);
            $controller->$method(...$params);

            return;
        }

        if (is_callable($handler)) {
            $handler($this->request, $params);
        }
    }

    private function notFound(): void
    {
        http_response_code(404);
        $viewsPath = dirname(__DIR__, 2) . '/resources/views/errors/404.php';

        if (is_file($viewsPath)) {
            require $viewsPath;

            return;
        }

        echo '404 - Pagina nao encontrada';
    }
}
