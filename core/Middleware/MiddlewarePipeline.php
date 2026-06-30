<?php

declare(strict_types=1);

namespace Kadosys\Core\Middleware;

use Kadosys\Core\Core\Container;
use Kadosys\Core\Http\Request;
use Kadosys\Core\Http\Response;
use RuntimeException;

/**
 * MiddlewarePipeline
 *
 * Executa uma cadeia (pipeline) de middlewares antes de chegar
 * a action final da rota, no estilo "onion" (cada middleware
 * pode interceptar a requisicao antes e a resposta depois).
 */
final class MiddlewarePipeline
{
    /**
     * Mapa de aliases de middleware para suas classes completas.
     * Alimentado pelos Service Providers na inicializacao da app.
     *
     * @var array<string, class-string>
     */
    private static array $aliases = [];

    /**
     * @param array<int, string> $middlewares Lista de aliases ou classes de middleware.
     */
    public function __construct(private readonly array $middlewares)
    {
    }

    /**
     * Registra um alias de middleware (ex: 'auth' => AuthMiddleware::class).
     */
    public static function registerAlias(string $alias, string $class): void
    {
        self::$aliases[$alias] = $class;
    }

    /**
     * Executa a pipeline. $destination e a action final da rota.
     */
    public function handle(Request $request, \Closure $destination): Response
    {
        $stack = array_reduce(
            array_reverse($this->middlewares),
            function (\Closure $next, string $middleware) {
                return function (Request $request) use ($middleware, $next) {
                    $instance = $this->resolveMiddleware($middleware);

                    return $instance->handle($request, $next);
                };
            },
            $destination
        );

        return $stack($request);
    }

    private function resolveMiddleware(string $middleware): MiddlewareInterface
    {
        $class = self::$aliases[$middleware] ?? $middleware;

        if (!class_exists($class)) {
            throw new RuntimeException("Middleware nao encontrado: {$middleware}");
        }

        $instance = Container::getInstance()->resolve($class);

        if (!$instance instanceof MiddlewareInterface) {
            throw new RuntimeException("Middleware {$class} deve implementar MiddlewareInterface");
        }

        return $instance;
    }
}
