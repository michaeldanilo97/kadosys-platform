<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de Usuario.
 *
 * Cobre autenticacao (login/lembrar-me/recuperacao de senha) e o CRUD
 * do modulo Usuarios e Permissoes: cada usuario tem um papel ('admin'
 * ve tudo e gerencia outros usuarios; 'usuario' ve os modulos do plano
 * contratado, exceto Usuarios/Permissoes/Configuracoes) e uma lista
 * opcional de modulos liberados especificamente pra ele
 * (user_modulos) - ver podeAcessarModulo() pra regra completa.
 */
final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USUARIO = 'usuario';

    /**
     * Modulos que so um 'admin' pode acessar, independente do plano
     * contratado ou de qualquer permissao especifica em user_modulos -
     * gerenciar outros usuarios, permissoes e o plano/faturamento da
     * igreja nunca fica disponivel pra um papel 'usuario'.
     *
     * @var array<int, string>
     */
    public const MODULOS_SOMENTE_ADMIN = ['usuarios', 'permissoes', 'configuracoes'];
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

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        $stmt = Database::connection()->query('SELECT id, name, email, password, role, active FROM users ORDER BY name ASC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function emailEmUso(string $email, ?int $excetoId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($excetoId !== null) {
            $sql .= ' AND id != :excetoId';
            $params['excetoId'] = $excetoId;
        }

        $stmt = Database::connection()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    public static function countAdminsAtivos(): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1"
        );
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password, role, active, created_at)
             VALUES (:name, :email, :password, :role, :active, NOW())'
        );
        $stmt->execute([
            'name' => trim((string) $data['name']),
            'email' => trim((string) $data['email']),
            'password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'role' => in_array($data['role'] ?? null, [self::ROLE_ADMIN, self::ROLE_USUARIO], true) ? $data['role'] : self::ROLE_USUARIO,
            'active' => 1,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): void
    {
        $sql = 'UPDATE users SET name = :name, email = :email, role = :role, active = :active';
        $params = [
            'id' => $this->id,
            'name' => trim((string) $data['name']),
            'email' => trim((string) $data['email']),
            'role' => in_array($data['role'] ?? null, [self::ROLE_ADMIN, self::ROLE_USUARIO], true) ? $data['role'] : self::ROLE_USUARIO,
            'active' => !empty($data['active']) ? 1 : 0,
        ];

        $novaSenha = (string) ($data['password'] ?? '');
        if ($novaSenha !== '') {
            $sql .= ', password = :password';
            $params['password'] = password_hash($novaSenha, PASSWORD_BCRYPT);
        }

        $stmt = Database::connection()->prepare($sql . ' WHERE id = :id');
        $stmt->execute($params);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Modulos liberados especificamente pra este usuario (allow-list) -
     * ver podeAcessarModulo() pra como isso se combina com o papel e o
     * plano contratado.
     *
     * @return array<int, string>
     */
    public static function modulosPermitidos(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT modulo_slug FROM user_modulos WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Substitui de uma vez toda a lista de modulos liberados pra um
     * usuario - lista vazia remove toda restricao explicita (o usuario
     * volta a ter acesso a tudo que o plano libera, ver
     * podeAcessarModulo()).
     *
     * @param array<int, string> $slugs
     */
    public static function definirModulosPermitidos(int $userId, array $slugs): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $delete = $pdo->prepare('DELETE FROM user_modulos WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);

        $insert = $pdo->prepare(
            'INSERT INTO user_modulos (user_id, modulo_slug, created_at) VALUES (:user_id, :modulo_slug, NOW())'
        );
        foreach (array_unique($slugs) as $slug) {
            $insert->execute(['user_id' => $userId, 'modulo_slug' => $slug]);
        }

        $pdo->commit();
    }

    /**
     * Regra completa de acesso a um modulo por USUARIO (independente do
     * plano contratado, ver Igrejas\Models\Plano::disponivel() pra essa
     * outra camada - as duas precisam liberar pro acesso valer):
     *   1. sem usuario logado -> nunca acessa.
     *   2. 'admin' -> acessa tudo.
     *   3. Usuarios/Permissoes/Configuracoes -> so 'admin' acessa, nunca
     *      configuravel via user_modulos.
     *   4. 'usuario' sem nenhuma linha em user_modulos -> acessa
     *      qualquer outro modulo (padrao: tudo que o plano libera).
     *   5. 'usuario' com pelo menos uma linha em user_modulos -> vira
     *      allow-list, so os modulos listados ficam liberados.
     */
    public static function podeAcessarModulo(?self $user, string $slug): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->role === self::ROLE_ADMIN) {
            return true;
        }

        if (in_array($slug, self::MODULOS_SOMENTE_ADMIN, true)) {
            return false;
        }

        $modulosPermitidos = self::modulosPermitidos($user->id);

        return $modulosPermitidos === [] || in_array($slug, $modulosPermitidos, true);
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
