<?php

declare(strict_types=1);

namespace Kadosys\Core\Routing;

/**
 * Route
 *
 * Representa uma unica rota registrada: metodo HTTP, URI padrao
 * (com parametros dinamicos), action (Controller@metodo ou Closure),
 * nome e middlewares associados.
 */
final class Route
{
    /** @var array<int, string> */
    private array $middlewares = [];

    private ?string $name = null;

    /**
     * @param string $method Metodo HTTP (GET, POST, PUT, PATCH, DELETE).
     * @param string $uri URI relativa com parametros no formato {param}.
     * @param string|array|\Closure $action Controller@metodo, [Controller::class, 'metodo'] ou Closure.
     */
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly string|array|\Closure $action
    ) {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function action(): string|array|\Closure
    {
        return $this->action;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Adiciona um ou mais middlewares a esta rota especifica.
     *
     * @param string|array<int, string> $middleware
     */
    public function middleware(string|array $middleware): self
    {
        $middlewares = is_array($middleware) ? $middleware : [$middleware];
        $this->middlewares = array_merge($this->middlewares, $middlewares);

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Converte a URI da rota (com {param}) em uma expressao regular
     * para fazer o match contra a URI real da requisicao.
     */
    public function toRegex(): string
    {
        $pattern = preg_replace('#\{([\w]+)\}#', '(?P<$1>[^/]+)', $this->uri);

        return '#^' . $pattern . '$#';
    }
}
