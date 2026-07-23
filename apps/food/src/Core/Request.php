<?php

declare(strict_types=1);

namespace Food\Core;

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

        // Suporte a method spoofing via campo oculto _method (PUT/PATCH/DELETE),
        // util para formularios HTML que so suportam GET/POST nativamente.
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

    /**
     * Retorna a entrada de $_FILES para o campo informado (ou null se
     * nenhum arquivo foi enviado nesse campo).
     *
     * @return array{name:string, type:string, tmp_name:string, error:int, size:int}|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
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
