<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Model de Usuario.
 *
 * Cada usuario pertence a exatamente um restaurante (restaurante_id) - o
 * e-mail e unico GLOBALMENTE (nao por restaurante), entao o login (ver
 * Food\Core\Auth) resolve direto pra qual restaurante mandar o usuario,
 * sem precisar de uma etapa de selecao como o KADOSYS Igrejas (que tem
 * um banco por igreja e por isso pode ter o mesmo e-mail em varias).
 * Papel 'admin' gerencia o restaurante; 'usuario' e a equipe (caixa,
 * cozinha, etc).
 */
final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USUARIO = 'usuario';

    private const SELECT_COLUNAS = 'id, restaurante_id, name, email, password, role, active, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
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
     * restaurante_id, senao um admin poderia editar usuario de outro
     * restaurante so adivinhando o id na URL.
     */
    public static function update(int $id, int $restauranteId, string $name, string $email, string $role, bool $active): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET name = :name, email = :email, role = :role, active = :active
             WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'name' => trim($name),
            'email' => trim($email),
            'role' => in_array($role, [self::ROLE_ADMIN, self::ROLE_USUARIO], true) ? $role : self::ROLE_USUARIO,
            'active' => $active ? 1 : 0,
            'id' => $id,
            'restaurante_id' => $restauranteId,
        ]);
    }

    public static function updatePassword(int $id, int $restauranteId, string $password): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET password = :password WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute([
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'id' => $id,
            'restaurante_id' => $restauranteId,
        ]);
    }

    /**
     * Recuperacao de senha ("esqueci minha senha", ver AuthController).
     * O token bruto so existe no e-mail enviado pro usuario - aqui so
     * fica o hash (mesma logica de nunca guardar segredo em texto puro,
     * ver Csrf/Session), com validade de 1h. O prazo e calculado no
     * proprio MySQL (NOW() + INTERVAL), nunca no PHP - assim a
     * comparacao feita depois em emailDoPasswordResetValido()
     * (expires_at >= NOW()) usa sempre o mesmo relogio dos dois lados.
     */
    public static function createPasswordResetToken(string $email, string $tokenHash): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO password_resets (email, token_hash, expires_at, created_at)
             VALUES (:email, :token_hash, NOW() + INTERVAL 1 HOUR, NOW())'
        );
        $stmt->execute([
            'email' => $email,
            'token_hash' => $tokenHash,
        ]);
    }

    /**
     * Devolve o e-mail associado a um token valido (nao expirado), ou
     * null se o token nao existe/ja expirou - nunca revela qual token
     * exato falhou, so se e valido ou nao.
     */
    public static function emailDoPasswordResetValido(string $tokenHash): ?string
    {
        $stmt = Database::connection()->prepare(
            'SELECT email FROM password_resets WHERE token_hash = :token_hash AND expires_at >= NOW() LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $email = $stmt->fetchColumn();

        return $email !== false ? (string) $email : null;
    }

    /**
     * Invalida todos os tokens de um e-mail depois de usados (ou ao
     * pedir um novo) - um link de recuperacao so pode ser usado uma vez.
     */
    public static function invalidarPasswordResets(string $email): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM password_resets WHERE email = :email');
        $stmt->execute(['email' => $email]);
    }

    public static function delete(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id AND restaurante_id = :restaurante_id');
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    public static function contarAdminsAtivos(int $restauranteId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM users WHERE restaurante_id = :restaurante_id AND role = 'admin' AND active = 1"
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return (int) $stmt->fetchColumn();
    }

    public static function create(int $restauranteId, string $name, string $email, string $password, string $role = self::ROLE_ADMIN): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (restaurante_id, name, email, password, role, active, created_at)
             VALUES (:restaurante_id, :name, :email, :password, :role, 1, NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'name' => trim($name),
            'email' => trim($email),
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => in_array($role, [self::ROLE_ADMIN, self::ROLE_USUARIO], true) ? $role : self::ROLE_USUARIO,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Equipe de um restaurante especifico - toda consulta de listagem
     * PRECISA passar restaurante_id, senao vazaria a equipe de um
     * restaurante pra outro.
     *
     * @return array<int, self>
     */
    public static function doRestaurante(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM users WHERE restaurante_id = :restaurante_id ORDER BY name ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            name: (string) $row['name'],
            email: (string) $row['email'],
            passwordHash: (string) $row['password'],
            role: (string) $row['role'],
            active: (bool) $row['active'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
