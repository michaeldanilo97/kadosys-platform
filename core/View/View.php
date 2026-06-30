<?php

declare(strict_types=1);

namespace Kadosys\Core\View;

use Kadosys\Core\Core\Application;
use RuntimeException;

/**
 * View
 *
 * Motor de views baseado em templates PHP puro (sem linguagem de
 * template propria), priorizando simplicidade e performance.
 *
 * Busca o arquivo de view primeiro dentro do aplicativo ativo
 * (apps/{app}/Views/) e, se nao encontrado, cai para resources/views/
 * do Core (views compartilhadas, como paginas de erro).
 */
final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = []): string
    {
        $path = self::resolvePath($view);

        if ($path === null) {
            throw new RuntimeException("View nao encontrada: {$view}");
        }

        return self::renderFile($path, $data);
    }

    /**
     * Resolve o caminho fisico do arquivo de view, dando prioridade
     * ao diretorio Views/ do aplicativo ativo.
     */
    private static function resolvePath(string $view): ?string
    {
        $relative = str_replace('.', '/', $view) . '.php';

        $app = Application::getInstance();

        $appViewPath = $app->appPath('Views/' . $relative);

        if (is_file($appViewPath)) {
            return $appViewPath;
        }

        $coreViewPath = $app->basePath('resources/views/' . $relative);

        if (is_file($coreViewPath)) {
            return $coreViewPath;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function renderFile(string $path, array $data): string
    {
        $renderer = function (string $__path, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            require $__path;

            return (string) ob_get_clean();
        };

        return $renderer($path, $data);
    }

    /**
     * Inclui uma sub-view (partial) dentro de outra view.
     *
     * @param array<string, mixed> $data
     */
    public static function include(string $view, array $data = []): void
    {
        echo self::render($view, $data);
    }
}
