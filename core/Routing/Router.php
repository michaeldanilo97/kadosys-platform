<?php

declare(strict_types=1);

namespace Kadosys\Core\Routing;

use Kadosys\Core\Core\Container;
use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;
use Kadosys\Core\Middleware\MiddlewarePipeline;
use RuntimeException;

/**
 * Router
 *
 * Motor de roteamento da plataforma. Trabalha exclusivamente com
 * URIs relativas (ex: "/dashboard"), pois o Request ja remove o
 * APP_BASE_PATH antes de qualquer despacho.
 *
 * Suporta:
 * - Verbos GET, POST, PUT, PATCH, DELETE
 * - Prefixos e grupos de rotas
 * - Middlewares por rota e por grupo
 * - Rotas nomeadas
 * - Parametros dinamicos ({id}, {slug}, etc)
 * - Tratamento de 404 e 405
 */
final class Router
{
    /**
     * @var array<int, Route>
     */
    private array $routes = [];

    /**
     * Pilha de prefixos ativos durante a definicao de grupos aninhados.
     *
     * @var array<int, string>
     */
    private array $groupPrefixStack = [];

    /**
     * Pilha de middlewares ativos durante a definicao de grupos aninhados.
     *
     * @var array<int, array<int, string>>
     */
    private array $groupMiddlewareStack = [];

    public function __construct(private readonly string $appBasePath = '')
    {
    }

    public function get(string $uri, string|array|\Closure $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, string|array|\Closure $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, string|array|\Closure $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, string|array|\Closure $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, string|array|\Closure $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Registra uma rota aplicando prefixos e middlewares de grupo ativos.
     */
    private function addRoute(string $method, string $uri, string|array|\Closure $action): Route
    {
        $fullUri = $this->applyGroupPrefix($uri);

        $route = new Route($method, $fullUri, $action);

        foreach ($this->currentGroupMiddlewares() as $middleware) {
            $route->middleware($middleware);
        }

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Agrupa rotas sob um prefixo e/ou middlewares comuns.
     *
     * Exemplo:
     * $router->group(['prefix' => 'admin', 'middleware' => 'auth'], function (Router $router) {
     *     $router->get('/dashboard', [DashboardController::class, 'index']);
     * });
     *
     * @param array{prefix?: string, middleware?: string|array<int, string>} $attributes
     */
    public function group(array $attributes, \Closure $callback): void
    {
        $prefix = trim((string) ($attributes['prefix'] ?? ''), '/');
        $middleware = $attributes['middleware'] ?? [];
        $middlewares = is_array($middleware) ? $middleware : [$middleware];

        $this->groupPrefixStack[] = $prefix;
        $this->groupMiddlewareStack[] = $middlewares;

        $callback($this);

        array_pop($this->groupPrefixStack);
        array_pop($this->groupMiddlewareStack);
    }

    private function applyGroupPrefix(string $uri): string
    {
        $prefixes = array_filter($this->groupPrefixStack, static fn (string $p) => $p !== '');
        $prefix = implode('/', $prefixes);

        $uri = '/' . trim($uri, '/');

        if ($prefix === '') {
            return $uri === '/' ? '/' : $uri;
        }

        $combined = '/' . $prefix . ($uri === '/' ? '' : $uri);

        return $combined;
    }

    /**
     * @return array<int, string>
     */
    private function currentGroupMiddlewares(): array
    {
        $merged = [];

        foreach ($this->groupMiddlewareStack as $middlewares) {
            $merged = array_merge($merged, $middlewares);
        }

        return $merged;
    }

    /**
     * Despacha a requisicao atual, encontrando a rota correspondente,
     * executando middlewares e a action, e retornando a Response.
     */
    public function dispatch(Request $request): Response
    {
        $uri = $request->uri();
        $method = $request->method();

        $matchedByUriButNotMethod = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route->toRegex(), $uri, $matches)) {
                continue;
            }

            if ($route->method() !== $method) {
                $matchedByUriButNotMethod = true;
                continue;
            }

            $params = $this->extractParams($matches);

            return $this->runRoute($route, $request, $params);
        }

        if ($matchedByUriButNotMethod) {
            return Response::methodNotAllowed();
        }

        return Response::notFound();
    }

    /**
     * @param array<int|string, string> $matches
     * @return array<string, string>
     */
    private function extractParams(array $matches): array
    {
        $params = [];

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * Executa a pipeline de middlewares e, em seguida, a action da rota.
     *
     * @param array<string, string> $params
     */
    private function runRoute(Route $route, Request $request, array $params): Response
    {
        $pipeline = new MiddlewarePipeline($route->middlewares());

        return $pipeline->handle($request, function (Request $request) use ($route, $params) {
            return $this->callAction($route->action(), $request, $params);
        });
    }

    /**
     * Invoca a action da rota: Closure, "Controller@metodo" ou [Controller::class, 'metodo'].
     *
     * @param array<string, string> $params
     */
    private function callAction(string|array|\Closure $action, Request $request, array $params): Response
    {
        $container = Container::getInstance();

        if ($action instanceof \Closure) {
            $result = $action($request, ...array_values($params));

            return $this->toResponse($result);
        }

        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
            $action = [$class, $method];
        }

        if (is_array($action)) {
            [$class, $method] = $action;

            /** @var object $controller */
            $controller = $container->resolve($class);

            if (!method_exists($controller, $method)) {
                throw new RuntimeException("Metodo {$method} nao existe no controller {$class}");
            }

            $result = $controller->$method($request, ...array_values($params));

            return $this->toResponse($result);
        }

        throw new RuntimeException('Action de rota invalida.');
    }

    /**
     * Normaliza o retorno da action para sempre ser uma Response.
     */
    private function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || is_object($result)) {
            return Response::json((array) $result);
        }

        return Response::html((string) $result);
    }

    /**
     * Define um nome para a ultima rota registrada (encadeavel).
     * Uso: $router->get('/login', ...)->setName('login');
     * Ou via helper: name('login') chamado em cadeia na propria Route.
     */
    public function findByName(string $name): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Gera a URI de uma rota nomeada substituindo seus parametros.
     *
     * @param array<string, string|int> $params
     */
    public function urlFor(string $name, array $params = []): string
    {
        $route = $this->findByName($name);

        if ($route === null) {
            throw new RuntimeException("Rota nomeada '{$name}' nao encontrada.");
        }

        $uri = $route->uri();

        foreach ($params as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
        }

        return $uri;
    }

    /**
     * @return array<int, Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    public function appBasePath(): string
    {
        return $this->appBasePath;
    }
}
