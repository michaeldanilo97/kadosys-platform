<?php

declare(strict_types=1);

namespace Kadosys\Core\Http;

/**
 * Response
 *
 * Encapsula a resposta HTTP que sera enviada ao navegador:
 * conteudo, status code e headers. Oferece factories estaticas
 * para os formatos mais comuns (HTML, JSON, redirect).
 */
final class Response
{
    private string $content;

    private int $statusCode;

    /** @var array<string, string> */
    private array $headers;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function html(string $content, int $statusCode = 200): self
    {
        return new self($content, $statusCode, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * @param array<string, mixed>|object $data
     */
    public static function json(array|object $data, int $statusCode = 200): self
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new self((string) $payload, $statusCode, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public static function notFound(string $content = '404 - Pagina nao encontrada'): self
    {
        return self::html($content, 404);
    }

    public static function methodNotAllowed(string $content = '405 - Metodo nao permitido'): self
    {
        return self::html($content, 405);
    }

    public static function serverError(string $content = '500 - Erro interno do servidor'): self
    {
        return self::html($content, 500);
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Envia os headers, status code e o corpo da resposta ao cliente.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
    }
}
