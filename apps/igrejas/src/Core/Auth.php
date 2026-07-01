<?php

declare(strict_types=1);

namespace Igrejas\Core;

use Igrejas\Models\User;

/**
 * Servico de autenticacao.
 *
 * Concentra login, logout, verificacao de sessao e o fluxo de "lembrar-me"
 * via cookie persistente. Usa hash de senha nativo do PHP (password_hash /
 * password_verify) e tokens aleatorios para o cookie de longa duracao.
 */
final class Auth
{
    private const SESSION_USER_ID = '_auth_user_id';

    public function __construct(private readonly array $config)
    {
    }

    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = User::findByEmail($email);

        if (!$user || !$user->active || !$user->verifyPassword($password)) {
            return false;
        }

        $this->login($user, $remember);

        return true;
    }

    public function login(User $user, bool $remember = false): void
    {
        Session::regenerate();
        Session::set(self::SESSION_USER_ID, $user->id);

        if ($remember) {
            $this->createRememberCookie($user);
        }
    }

    public function logout(): void
    {
        $userId = Session::get(self::SESSION_USER_ID);

        if ($userId && !empty($_COOKIE[$this->config['remember_cookie']])) {
            User::forgetRememberToken(hash('sha256', (string) $_COOKIE[$this->config['remember_cookie']]));
        }

        Session::destroy();
        setcookie($this->config['remember_cookie'], '', time() - 3600, '/');
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?User
    {
        $userId = Session::get(self::SESSION_USER_ID);

        if ($userId !== null) {
            return User::findById((int) $userId);
        }

        return $this->attemptLoginFromRememberCookie();
    }

    private function attemptLoginFromRememberCookie(): ?User
    {
        $cookieName = $this->config['remember_cookie'];

        if (empty($_COOKIE[$cookieName])) {
            return null;
        }

        $tokenHash = hash('sha256', (string) $_COOKIE[$cookieName]);
        $user = User::findByValidRememberToken($tokenHash);

        if ($user) {
            Session::set(self::SESSION_USER_ID, $user->id);
        }

        return $user;
    }

    private function createRememberCookie(User $user): void
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $ttl = (int) ($this->config['remember_ttl'] ?? 2592000);
        $expiresAt = new \DateTimeImmutable('+' . $ttl . ' seconds');

        $user->storeRememberToken($tokenHash, $expiresAt);

        setcookie(
            $this->config['remember_cookie'],
            $token,
            [
                'expires' => $expiresAt->getTimestamp(),
                'path' => '/',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }
}
