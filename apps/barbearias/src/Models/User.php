<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Model de Usuario.
 *
 * Cada usuario pertence a exatamente uma barbearia (barbearia_id) - o
 * e-mail e unico GLOBALMENTE (nao por barbearia), entao o login (ver
 * Barbearias\Core\Auth) resolve direto pra qual barbearia mandar o
 * usuario, sem precisar de uma etapa de selecao como o KADOSYS Igrejas
 * (que tem um banco por igreja e por isso pode ter o mesmo e-mail em
 * varias). Papel 'admin' gerencia a barbearia; 'usuario' e a equipe.
 */
final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USUARIO = 'usuario';

    private const SELECT_COLUNAS = 'id, barbearia_id, name, email, password, role, active, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $role,
        public readonly bool $active,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function findByEmail(string $email): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    public static function emailEmUso(string $email): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() !== false;
    }

    public static function create(int $barbeariaId, string $name, string $email, string $password, string $role = self::ROLE_ADMIN): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (barbearia_id, name, email, password, role, active, created_at)
             VALUES (:barbearia_id, :name, :email, :password, :role, 1, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'name' => trim($name),
            'email' => trim($email),
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => in_array($role, [self::ROLE_ADMIN, self::ROLE_USUARIO], true) ? $role : self::ROLE_USUARIO,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Equipe de uma barbearia especifica - toda consulta de listagem
     * PRECISA passar barbearia_id, senao vazaria a equipe de uma
     * barbearia pra outra.
     *
     * @return array<int, self>
     */
    public static function daBarbearia(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM users WHERE barbearia_id = :barbearia_id ORDER BY name ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password'],
            role: (string) $row['role'],
            active: (bool) $row['active'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
