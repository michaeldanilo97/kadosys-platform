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

    /**
     * Slug "fantasma" (nunca corresponde a um modulo de verdade) usado
     * pra marcar em user_modulos que um usuario foi criado
     * EXPLICITAMENTE sem nenhum acesso - diferente de simplesmente nao
     * ter nenhuma linha em user_modulos, que hoje significa "sem
     * restricao" (acesso a tudo que o plano libera, ver
     * podeAcessarModulo()). Usado pelo login criado automaticamente no
     * auto-cadastro publico de membros (ver MembroPublicoController) -
     * o membro ganha uma conta, mas o admin precisa liberar
     * manualmente em Permissoes o que ela pode acessar.
     */
    public const SEM_ACESSO_PADRAO = '__membro_sem_acesso__';

    /**
     * Modulos que so ficam disponiveis pra 'admin' OU pra um usuario
     * marcado como musico (ver campo "musico" abaixo) - diferente de
     * MODULOS_SOMENTE_ADMIN, aqui um 'usuario' comum (sem a flag) fica
     * de fora mesmo sem nenhuma restricao em user_modulos, ja que o
     * modulo Louvores e pensado especificamente pro time de louvor, nao
     * pra qualquer conta de acesso geral.
     *
     * @var array<int, string>
     */
    public const MODULOS_SOMENTE_MUSICO = ['louvores'];

    public const CARGO_MUSICO = 'musico';
    public const CARGO_MIDIA = 'midia';
    public const CARGO_EQUIPAMENTO = 'equipamento';
    public const CARGO_MEMBRO = 'membro';

    /**
     * Rotulo e icone (Bootstrap Icons) de cada cargo, usados na tela
     * "Equipe" (galeria estilo rede social, ver EquipeController) e no
     * cadastro de usuario - "membro" (padrao) mostra o logo da igreja
     * em vez de um icone fixo (ver dashboard/equipe/index.php).
     *
     * @var array<string, array{label: string, icon: string}>
     */
    public const CARGOS = [
        self::CARGO_MUSICO => ['label' => 'Músico', 'icon' => 'bi-music-note-beamed'],
        self::CARGO_MIDIA => ['label' => 'Mídia', 'icon' => 'bi-camera-reels-fill'],
        self::CARGO_EQUIPAMENTO => ['label' => 'Equipamento', 'icon' => 'bi-sliders2'],
        self::CARGO_MEMBRO => ['label' => 'Membro', 'icon' => 'bi-person-fill'],
    ];

    /**
     * Instrumentos de uma lista fechada, cada um com seu proprio emoji
     * (Bootstrap Icons nao tem icones de instrumento musical) - so faz
     * sentido pra quem tem cargo = musico.
     *
     * @var array<string, array{label: string, emoji: string}>
     */
    public const INSTRUMENTOS = [
        'bateria' => ['label' => 'Bateria', 'emoji' => '🥁'],
        'guitarra' => ['label' => 'Guitarra', 'emoji' => '🎸'],
        'baixo' => ['label' => 'Baixo', 'emoji' => '🎸'],
        'violao' => ['label' => 'Violão', 'emoji' => '🎸'],
        'teclado' => ['label' => 'Teclado', 'emoji' => '🎹'],
        'vocal' => ['label' => 'Vocal', 'emoji' => '🎤'],
        'outro' => ['label' => 'Outro', 'emoji' => '🎵'],
    ];

    private const SELECT_COLUNAS = 'id, name, email, password, role, active, musico, lider_louvor, cargo, instrumento, foto_path';

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $role,
        public readonly bool $active,
        public readonly bool $musico = false,
        public readonly bool $liderLouvor = false,
        public readonly string $cargo = self::CARGO_MEMBRO,
        public readonly ?string $instrumento = null,
        public readonly ?string $fotoPath = null,
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
            'SELECT u.id, u.name, u.email, u.password, u.role, u.active, u.musico, u.lider_louvor, u.cargo, u.instrumento, u.foto_path
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
        $stmt = Database::connection()->query('SELECT ' . self::SELECT_COLUNAS . ' FROM users ORDER BY name ASC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Usuarios ativos com login, ordenados por cargo (musico > midia >
     * equipamento > membro) e depois por nome - pra montar a galeria da
     * tela "Equipe" (ver EquipeController) num agrupamento visual
     * natural, sem precisar de mais uma consulta por cargo.
     *
     * @return array<int, self>
     */
    public static function todosAtivosParaEquipe(): array
    {
        $stmt = Database::connection()->query(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM users
             WHERE active = 1
             ORDER BY FIELD(cargo, \'musico\', \'midia\', \'equipamento\', \'membro\'), name ASC'
        );

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
            'INSERT INTO users (name, email, password, role, active, musico, lider_louvor, cargo, instrumento, created_at)
             VALUES (:name, :email, :password, :role, :active, :musico, :lider_louvor, :cargo, :instrumento, NOW())'
        );
        $stmt->execute([
            'name' => trim((string) $data['name']),
            'email' => trim((string) $data['email']),
            'password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'role' => in_array($data['role'] ?? null, [self::ROLE_ADMIN, self::ROLE_USUARIO], true) ? $data['role'] : self::ROLE_USUARIO,
            'active' => 1,
            'musico' => !empty($data['musico']) ? 1 : 0,
            'lider_louvor' => !empty($data['lider_louvor']) ? 1 : 0,
            'cargo' => self::cargoValido($data['cargo'] ?? null),
            'instrumento' => self::instrumentoValido($data['cargo'] ?? null, $data['instrumento'] ?? null),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): void
    {
        $sql = 'UPDATE users SET name = :name, email = :email, role = :role, active = :active, musico = :musico, lider_louvor = :lider_louvor, cargo = :cargo, instrumento = :instrumento';
        $params = [
            'id' => $this->id,
            'name' => trim((string) $data['name']),
            'email' => trim((string) $data['email']),
            'role' => in_array($data['role'] ?? null, [self::ROLE_ADMIN, self::ROLE_USUARIO], true) ? $data['role'] : self::ROLE_USUARIO,
            'active' => !empty($data['active']) ? 1 : 0,
            'musico' => !empty($data['musico']) ? 1 : 0,
            'lider_louvor' => !empty($data['lider_louvor']) ? 1 : 0,
            'cargo' => self::cargoValido($data['cargo'] ?? null),
            'instrumento' => self::instrumentoValido($data['cargo'] ?? null, $data['instrumento'] ?? null),
        ];

        $novaSenha = (string) ($data['password'] ?? '');
        if ($novaSenha !== '') {
            $sql .= ', password = :password';
            $params['password'] = password_hash($novaSenha, PASSWORD_BCRYPT);
        }

        $stmt = Database::connection()->prepare($sql . ' WHERE id = :id');
        $stmt->execute($params);
    }

    /**
     * Autoatendimento: o proprio usuario logado atualiza o cargo e o
     * instrumento do seu perfil (ver PerfilController) - deliberadamente
     * SEPARADO de update() (que so o admin usa), pra nunca dar pra essa
     * rota mexer em role/active/senha por engano.
     */
    public function atualizarCargo(string $cargo, ?string $instrumento): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET cargo = :cargo, instrumento = :instrumento WHERE id = :id'
        );
        $stmt->execute([
            'id' => $this->id,
            'cargo' => self::cargoValido($cargo),
            'instrumento' => self::instrumentoValido($cargo, $instrumento),
        ]);
    }

    public function atualizarFoto(?string $fotoPath): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET foto_path = :foto_path WHERE id = :id');
        $stmt->execute(['id' => $this->id, 'foto_path' => $fotoPath]);
    }

    private static function cargoValido(mixed $cargo): string
    {
        $cargo = (string) $cargo;

        return array_key_exists($cargo, self::CARGOS) ? $cargo : self::CARGO_MEMBRO;
    }

    private static function instrumentoValido(mixed $cargo, mixed $instrumento): ?string
    {
        if ((string) $cargo !== self::CARGO_MUSICO) {
            return null;
        }

        $instrumento = (string) $instrumento;

        return array_key_exists($instrumento, self::INSTRUMENTOS) ? $instrumento : null;
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

        if (in_array($slug, self::MODULOS_SOMENTE_MUSICO, true) && !$user->musico && !$user->liderLouvor) {
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
            musico: (bool) ($row['musico'] ?? false),
            liderLouvor: (bool) ($row['lider_louvor'] ?? false),
            cargo: (string) ($row['cargo'] ?? self::CARGO_MEMBRO),
            instrumento: $row['instrumento'] ?? null,
            fotoPath: $row['foto_path'] ?? null,
        );
    }
}
