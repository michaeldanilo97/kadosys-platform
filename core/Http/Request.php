<?php

declare(strict_types=1);

namespace Kadosys\Core\Http;

/**
 * Request
 *
 * Encapsula a requisicao HTTP atual (metodo, URI, query string,
 * dados de formulario, headers, etc). Remove automaticamente o
 * APP_BASE_PATH da URI antes de entregar ao Router, garantindo
 * que o roteamento sempre trabalhe com URIs relativas (ex: "/login"),
 * independentemente do subdiretorio onde a plataforma esteja instalada.
 */
final class Request
{
    private string $method;

    private string $uri;

    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed> */
    private array $body;

    /** @var array<string, mixed> */
    private array $server;

    /** @var array<string, mixed> */
    private array $files;

    /** @var array<string, mixed> */
    private array $cookies;

    /** @var array<string, mixed> */
    private array $headers;

    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $server = [],
        array $files = [],
        array $cookies = [],
        array $headers = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->files = $files;
        $this->cookies = $cookies;
        $this->headers = $headers;
    }

    /**
     * Captura a requisicao atual a partir das superglobais do PHP,
     * removendo o APP_BASE_PATH da URI.
     */
    public static function capture(string $appBasePath = ''): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Suporte a spoofing de metodo via campo _method (PUT/PATCH/DELETE em forms HTML).
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }

        $rawUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($rawUri, PHP_URL_PATH) ?: '/';

        $uri = self::stripBasePath($path, $appBasePath);

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }

        return new self(
            $method,
            $uri,
            $_GET ?? [],
            $_POST ?? [],
            $_SERVER ?? [],
            $_FILES ?? [],
            $_COOKIE ?? [],
            $headers
        );
    }

    /**
     * Remove o APP_BASE_PATH do inicio do path da URI, garantindo
     * que rotas sejam sempre relativas (ex: "/dashboard" em vez de
     * "/app/igrejas/dashboard").
     */
    private static function stripBasePath(string $path, string $appBasePath): string
    {
        $appBasePath = '/' . trim($appBasePath, '/');

        if ($appBasePath === '/') {
            $normalized = '/' . trim($path, '/');

            return $normalized === '' ? '/' : $normalized;
        }

        if (str_starts_with($path, $appBasePath)) {
            $stripped = substr($path, strlen($appBasePath));
            $stripped = '/' . trim($stripped, '/');

            return $stripped === '' ? '/' : $stripped;
        }

        $normalized = '/' . trim($path, '/');

        return $normalized === '' ? '/' : $normalized;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri === '' ? '/' : $this->uri;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * Recupera um valor do corpo da requisicao (POST/JSON) ou query string.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Retorna todos os dados de entrada (query + body combinados).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /**
     * Retorna apenas as chaves informadas dos dados de entrada.
     *
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $all = $this->all();

        return array_intersect_key($all, array_flip($keys));
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $name, mixed $default = null): mixed
    {
        return $this->headers[strtoupper($name)] ?? $this->headers[$name] ?? $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower((string) $this->header('X-REQUESTED-WITH', '')) === 'xmlhttprequest';
    }

    public function ip(): string
    {
        return (string) ($this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
