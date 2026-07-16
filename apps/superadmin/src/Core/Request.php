<?php

declare(strict_types=1);

namespace Superadmin\Core;

/**
 * Representa a requisicao HTTP atual.
 *
 * Centraliza o acesso a metodo, URI, dados de POST/GET e arquivos,
 * evitando o uso direto de superglobais espalhado pelo restante do codigo.
 */
final class Request
{
    private function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly array $query,
        public readonly array $body,
        public readonly array $server,
        public readonly array $files,
    ) {
    }

    public static function capture(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }

        return new self(
            method: $method,
            uri: rtrim($uri, '/') === '' ? '/' : rtrim($uri, '/'),
            query: $_GET,
            body: $_POST,
            server: $_SERVER,
            files: $_FILES,
        );
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->body[$key] ?? null;
        }

        return $result;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }
}
