<?php

declare(strict_types=1);

namespace Kadosys\Core\Security;

/**
 * Csrf
 *
 * Gera e valida tokens CSRF armazenados na sessao, utilizados pelo
 * VerifyCsrfTokenMiddleware e pelo helper csrf() nas views.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function token(): string
    {
        if (!Session::has(self::SESSION_KEY)) {
            Session::put(self::SESSION_KEY, bin2hex(random_bytes(32)));
        }

        return (string) Session::get(self::SESSION_KEY);
    }

    public function isValid(string $token): bool
    {
        $sessionToken = (string) Session::get(self::SESSION_KEY, '');

        if ($token === '' || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public function regenerate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::put(self::SESSION_KEY, $token);

        return $token;
    }
}
