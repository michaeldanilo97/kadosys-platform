<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de Usuario.
 *
 * Mantem apenas as operacoes necessarias para autenticacao nesta etapa
 * (Sprint 1). Operacoes de CRUD completo de usuarios serao expandidas
 * quando o modulo de Usuarios e Permissoes for implementado.
 */
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $role,
        public readonly bool $active,
    ) {
    }

    public static function findByEmail(string $email): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, password, role, active FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, email, password, role, active FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    /**
     * Armazena um token de "lembrar-me" (hash) vinculado ao usuario.
     */
    public function storeRememberToken(string $tokenHash, \DateTimeImmutable $expiresAt): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO remember_tokens (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :token_hash, :expires_at, NOW())'
        );
        $stmt->execute([
            'user_id' => $this->id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public static function findByValidRememberToken(string $tokenHash): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.name, u.email, u.password, u.role, u.active
             FROM remember_tokens rt
             INNER JOIN users u ON u.id = rt.user_id
             WHERE rt.token_hash = :token_hash AND rt.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function forgetRememberToken(string $tokenHash): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM remember_tokens WHERE token_hash = :token_hash'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
    }

    /**
     * Cria um registro de solicitacao de recuperacao de senha.
     * O envio de e-mail sera implementado em sprint futura; aqui apenas a
     * estrutura de dados fica pronta, conforme escopo desta etapa.
     */
    public static function createPasswordResetToken(string $email, string $tokenHash, \DateTimeImmutable $expiresAt): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO password_resets (email, token_hash, expires_at, created_at)
             VALUES (:email, :token_hash, :expires_at, NOW())'
        );
        $stmt->execute([
            'email' => $email,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password'],
            role: (string) $row['role'],
            active: (bool) $row['active'],
        );
    }
}
