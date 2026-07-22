<?php

declare(strict_types=1);

namespace Academias\Core;

use Academias\Models\User;

/**
 * Servico de autenticacao da equipe (dono/recepcao/professor com
 * login). Usa hash de senha nativo do PHP (password_hash/password_verify).
 * O e-mail e unico GLOBALMENTE (uma unica tabela users compartilhada
 * entre todas as academias, ver User::findByEmail()) - cada usuario
 * pertence a exatamente uma academia (users.academia_id).
 */
final class Auth
{
    private const SESSION_USER_ID = '_auth_user_id';

    public function __construct(private readonly array $config)
    {
    }

    public function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if (!$user || !$user->active || !$user->verifyPassword($password)) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function login(User $user): void
    {
        Session::regenerate();
        Session::set(self::SESSION_USER_ID, $user->id);
    }

    public function logout(): void
    {
        Session::destroy();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?User
    {
        $userId = Session::get(self::SESSION_USER_ID);

        return $userId !== null ? User::findById((int) $userId) : null;
    }
}
