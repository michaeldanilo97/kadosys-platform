<?php

declare(strict_types=1);

namespace Kadosys\Core\Exceptions;

use Kadosys\Core\Core\Config;
use Kadosys\Core\Http\Response;
use Kadosys\Core\Support\Logger;
use Throwable;

/**
 * ExceptionHandler
 *
 * Ponto central de tratamento de excecoes nao capturadas durante o
 * ciclo de vida da requisicao. Registra o erro no log e retorna uma
 * Response apropriada, exibindo detalhes completos apenas quando
 * APP_DEBUG estiver habilitado.
 */
final class ExceptionHandler
{
    public static function handle(Throwable $exception): Response
    {
        Logger::error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $debug = (bool) Config::get('app.debug', false);

        if ($debug) {
            return Response::html(self::renderDebugPage($exception), 500);
        }

        return Response::serverError();
    }

    private static function renderDebugPage(Throwable $exception): string
    {
        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8');
        $line = $exception->getLine();
        $trace = htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8');
        $class = htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Erro - Kadosys Platform</title>
            <style>
                body { font-family: 'Courier New', monospace; background: #1e1e2e; color: #f8f8f2; padding: 2rem; }
                h1 { color: #ff5555; font-size: 1.4rem; }
                .meta { color: #8be9fd; margin: 1rem 0; }
                pre { background: #11111b; padding: 1rem; border-radius: 8px; overflow-x: auto; white-space: pre-wrap; }
            </style>
        </head>
        <body>
            <h1>{$class}: {$message}</h1>
            <p class="meta">Arquivo: {$file} (linha {$line})</p>
            <pre>{$trace}</pre>
        </body>
        </html>
        HTML;
    }
}
