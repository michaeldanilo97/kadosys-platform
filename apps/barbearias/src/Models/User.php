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

    public static function emailEmUso(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($exceptId !== null) {
            $sql .= ' AND id != :except_id';
            $params['except_id'] = $exceptId;
        }

        $stmt = Database::connection()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    /**
     * Atualiza os dados de um usuario da EQUIPE - sempre filtrado por
     * barbearia_id, senao um admin poderia editar usuario de outra
     * barbearia so adivinhando o id na URL.
     */
    public static function update(int $id, int $barbeariaId, string $name, string $email, string $role, bool $active): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET name = :name, email = :email, role = :role, active = :active
             WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute([
            'name' => trim($name),
            'email' => trim($email),
            'role' => in_array($role, [self::ROLE_ADMIN, self::ROLE_USUARIO], true) ? $role : self::ROLE_USUARIO,
            'active' => $active ? 1 : 0,
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);
    }

    public static function updatePassword(int $id, int $barbeariaId, string $password): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET password = :password WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute([
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    public static function contarAdminsAtivos(int $barbeariaId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM users WHERE barbearia_id = :barbearia_id AND role = 'admin' AND active = 1"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return (int) $stmt->fetchColumn();
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
