<?php

declare(strict_types=1);

namespace Kadosys\Core\Security;

use Kadosys\Core\Core\Config;
use Kadosys\Core\Database\QueryBuilder;

/**
 * Auth
 *
 * Servico de autenticacao da plataforma. Responsavel por login,
 * logout, verificacao de sessao, "lembrar-me" via cookie persistente,
 * e checagem simples de papeis/permissoes (ACL).
 *
 * Trabalha sobre uma tabela de usuarios generica, configuravel via
 * config/auth.php (nome da tabela, colunas de email/senha).
 */
final class Auth
{
    private const SESSION_KEY = '_auth_user_id';

    private const REMEMBER_COOKIE = 'kadosys_remember';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $user = null;

    private bool $resolved = false;

    /**
     * Tenta autenticar o usuario com as credenciais informadas.
     * Retorna true em caso de sucesso.
     */
    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        $table = (string) Config::get('auth.table', 'users');
        $emailColumn = (string) Config::get('auth.email_column', 'email');
        $passwordColumn = (string) Config::get('auth.password_column', 'password');

        $user = (new QueryBuilder($table))
            ->where($emailColumn, '=', $email)
            ->first();

        if ($user === null || !Hash::check($password, (string) $user[$passwordColumn])) {
            return false;
        }

        $this->login($user);

        if ($remember) {
            $this->rememberUser($user);
        }

        return true;
    }

    /**
     * Efetua o login de um usuario ja carregado (array associativo).
     *
     * @param array<string, mixed> $user
     */
    public function login(array $user): void
    {
        $primaryKey = (string) Config::get('auth.primary_key', 'id');

        Session::regenerate();
        Session::put(self::SESSION_KEY, $user[$primaryKey]);

        $this->user = $user;
        $this->resolved = true;
    }

    public function logout(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::destroy();

        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/');
        }

        $this->user = null;
        $this->resolved = true;
    }

    public function check(): bool
    {
        return $this->resolveUser() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        return $this->resolveUser();
    }

    public function id(): int|string|null
    {
        $user = $this->resolveUser();
        $primaryKey = (string) Config::get('auth.primary_key', 'id');

        return $user[$primaryKey] ?? null;
    }

    /**
     * Verifica se o usuario autenticado possui o papel/role informado.
     * Assume uma coluna "role" na tabela de usuarios (ACL simples).
     */
    public function hasRole(string $role): bool
    {
        $user = $this->resolveUser();

        return $user !== null && ($user['role'] ?? null) === $role;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveUser(): ?array
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;

        $userId = Session::get(self::SESSION_KEY);

        if ($userId === null) {
            $userId = $this->resolveFromRememberCookie();
        }

        if ($userId === null) {
            return null;
        }

        $table = (string) Config::get('auth.table', 'users');
        $primaryKey = (string) Config::get('auth.primary_key', 'id');

        $this->user = (new QueryBuilder($table))
            ->where($primaryKey, '=', $userId)
            ->first();

        return $this->user;
    }

    /**
     * Restaura a sessao a partir do cookie "remember me", se presente e valido.
     */
    private function resolveFromRememberCookie(): int|string|null
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;

        if (!is_string($cookie) || !str_contains($cookie, '|')) {
            return null;
        }

        [$userId, $token] = explode('|', $cookie, 2);

        $table = (string) Config::get('auth.table', 'users');
        $primaryKey = (string) Config::get('auth.primary_key', 'id');

        $user = (new QueryBuilder($table))
            ->where($primaryKey, '=', $userId)
            ->first();

        if ($user === null || !isset($user['remember_token'])) {
            return null;
        }

        if (!hash_equals((string) $user['remember_token'], $token)) {
            return null;
        }

        Session::put(self::SESSION_KEY, $user[$primaryKey]);

        return $user[$primaryKey];
    }

    /**
     * Gera e persiste um token "remember me", definindo o cookie persistente.
     *
     * @param array<string, mixed> $user
     */
    private function rememberUser(array $user): void
    {
        $table = (string) Config::get('auth.table', 'users');
        $primaryKey = (string) Config::get('auth.primary_key', 'id');

        $token = bin2hex(random_bytes(32));

        (new QueryBuilder($table))
            ->where($primaryKey, '=', $user[$primaryKey])
            ->update(['remember_token' => $token]);

        $expire = time() + 60 * 60 * 24 * 30; // 30 dias

        setcookie(
            self::REMEMBER_COOKIE,
            $user[$primaryKey] . '|' . $token,
            $expire,
            '/',
            '',
            (bool) Config::get('session.secure_cookie', false),
            true
        );
    }
}
