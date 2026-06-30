<?php

declare(strict_types=1);

namespace Kadosys\Core\Core;

/**
 * Env
 *
 * Responsavel por carregar e expor as variaveis de ambiente
 * definidas no arquivo .env na raiz da plataforma.
 *
 * Nao depende de bibliotecas externas (vlucas/phpdotenv) para
 * manter o Core 100% proprietario, conforme especificacao.
 */
final class Env
{
    /**
     * Indica se o .env ja foi carregado.
     */
    private static bool $loaded = false;

    /**
     * Cache das variaveis carregadas.
     *
     * @var array<string, string>
     */
    private static array $variables = [];

    /**
     * Carrega o arquivo .env informado para a memoria e para
     * as superglobais $_ENV / putenv, evitando duplo carregamento.
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!is_file($path)) {
            self::$loaded = true;
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            self::$loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = self::parseValue(trim($value));

            self::$variables[$key] = $value;

            if (getenv($key) === false) {
                putenv("{$key}={$value}");
            }

            $_ENV[$key] = $value;
        }

        self::$loaded = true;
    }

    /**
     * Recupera uma variavel de ambiente com valor padrao opcional,
     * convertendo automaticamente strings booleanas/nulas/numericas.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$variables[$key] ?? $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return self::castValue($value);
    }

    /**
     * Remove aspas externas e comentarios inline simples de um valor bruto do .env.
     */
    private static function parseValue(string $value): string
    {
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Converte representacoes textuais comuns (true/false/null/numeric)
     * para os respectivos tipos nativos do PHP.
     */
    private static function castValue(string $value): mixed
    {
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }
}
