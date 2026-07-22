<?php

declare(strict_types=1);

namespace Academias\Core;

/**
 * Controller base. Todos os controllers da aplicacao devem estender esta
 * classe para ter acesso aos helpers comuns de view e redirecionamento.
 */
abstract class Controller
{
    public function __construct(
        protected readonly Request $request,
        protected readonly array $config,
    ) {
    }

    protected function view(string $view, array $data = [], ?string $layout = null): string
    {
        $data['config'] = $this->config;

        return View::render($view, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $this->url($path));
        exit;
    }

    protected function back(): never
    {
        $referer = $this->request->server['HTTP_REFERER'] ?? $this->url('/');
        header('Location: ' . $referer);
        exit;
    }

    protected function url(string $path = '/'): string
    {
        $base = $this->config['base_path'] ?? '';
        $path = '/' . ltrim($path, '/');

        return $path === '/' ? ($base === '' ? '/' : $base) : $base . $path;
    }

    protected function jsonResponse(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
