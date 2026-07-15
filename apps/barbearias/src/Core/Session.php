<?php

declare(strict_types=1);

namespace Barbearias\Core;

/**
 * Wrapper simples sobre a sessao nativa do PHP.
 *
 * Mantem toda a logica de sessao em um unico ponto, para que controllers
 * e middlewares nao manipulem $_SESSION diretamente.
 */
final class Session
{
    public static function start(string $name = 'kadosys_barbearias_session'): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($name);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if (func_num_args() === 1) {
            $value = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);

            return $value;
        }

        $_SESSION['_flash'][$key] = $value;

        return null;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    private static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }
}
