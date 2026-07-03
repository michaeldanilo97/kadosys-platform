<?php

declare(strict_types=1);

namespace Igrejas\Core;

/**
 * Motor de views minimalista.
 *
 * Renderiza arquivos PHP puro localizados em resources/views, com suporte
 * a layout (a view e capturada e injetada no layout como $content). Nao
 * introduz nenhuma linguagem de template nova, apenas PHP nativo - em
 * linha com a decisao de nao adicionar frameworks ao projeto.
 */
final class View
{
    private static string $viewsPath;

    public static function setBasePath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/');
    }

    /**
     * Timestamp de modificacao de um asset (public/assets/...), usado como
     * query string de cache-busting (?v=...). Sem isso, hospedagem
     * compartilhada/navegadores em tablets tendem a manter em cache a
     * versao antiga do CSS/JS por bastante tempo, fazendo um ajuste ja
     * enviado (git push) parecer que "nao aplicou" no aparelho do usuario.
     */
    public static function assetVersion(string $relativePath): string
    {
        $file = dirname(self::$viewsPath, 2) . '/public/' . ltrim($relativePath, '/');

        return is_file($file) ? (string) filemtime($file) : '1';
    }

    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::renderFile($view, $data);

        if ($layout === null) {
            return $content;
        }

        $data['content'] = $content;

        return self::renderFile('layouts/' . $layout, $data);
    }

    public static function output(string $view, array $data = [], ?string $layout = null): never
    {
        echo self::render($view, $data, $layout);
        exit;
    }

    private static function renderFile(string $view, array $data): string
    {
        $path = self::$viewsPath . '/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($path)) {
            throw new \RuntimeException("View nao encontrada: {$view} ({$path})");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }
}
