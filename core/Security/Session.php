<?php

declare(strict_types=1);

namespace Kadosys\Core\Security;

use Kadosys\Core\Core\Config;

/**
 * Session
 *
 * Encapsula o gerenciamento de sessao nativa do PHP, aplicando as
 * configuracoes de cookie (nome, lifetime, secure) definidas em
 * config/session.php / .env.
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        session_name((string) Config::get('session.name', 'kadosys_session'));

        session_set_cookie_params([
            'lifetime' => (int) Config::get('session.lifetime', 120) * 60,
            'path' => '/',
            'secure' => (bool) Config::get('session.secure_cookie', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();

        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Armazena dados temporarios para a proxima requisicao apenas (flash data),
     * util para mensagens de sucesso/erro e repopulacao de formularios (old()).
     */
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        self::$started = false;
    }
}
