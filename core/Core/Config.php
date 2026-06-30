<?php

declare(strict_types=1);

namespace Kadosys\Core\Core;

/**
 * Config
 *
 * Repositorio central de configuracao da plataforma.
 * Carrega todos os arquivos PHP do diretorio config/ e expoe
 * acesso via notacao de ponto, ex: config('app.url').
 */
final class Config
{
    /**
     * Configuracoes carregadas em memoria.
     *
     * @var array<string, mixed>
     */
    private static array $items = [];

    /**
     * Indica se os arquivos de configuracao ja foram carregados.
     */
    private static bool $loaded = false;

    /**
     * Carrega todos os arquivos *.php do diretorio de configuracao,
     * usando o nome do arquivo (sem extensao) como chave raiz.
     */
    public static function load(string $configPath): void
    {
        if (self::$loaded) {
            return;
        }

        if (!is_dir($configPath)) {
            self::$loaded = true;
            return;
        }

        $files = glob(rtrim($configPath, '/') . '/*.php') ?: [];

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $value = require $file;

            if (is_array($value)) {
                self::$items[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Mescla (ou registra) configuracoes adicionais em runtime,
     * util para apps registrarem suas proprias configs especificas.
     */
    public static function merge(string $key, array $values): void
    {
        if (isset(self::$items[$key]) && is_array(self::$items[$key])) {
            self::$items[$key] = array_replace_recursive(self::$items[$key], $values);
            return;
        }

        self::$items[$key] = $values;
    }

    /**
     * Obtem um valor de configuracao por chave em notacao de ponto.
     * Exemplo: Config::get('database.connections.mysql.host')
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Define um valor de configuracao em runtime (notacao de ponto).
     */
    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $items = &self::$items;

        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $items[$segment] = $value;
                break;
            }

            if (!isset($items[$segment]) || !is_array($items[$segment])) {
                $items[$segment] = [];
            }

            $items = &$items[$segment];
        }
    }

    /**
     * Retorna todas as configuracoes carregadas (debug/teste).
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::$items;
    }
}
