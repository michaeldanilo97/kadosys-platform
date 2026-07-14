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
     * Nivel de acesso a um modulo liberado em user_modulos:
     * "visualizar" so ve a tela (formularios/tabelas ficam de leitura,
     * qualquer acao que grava algo - POST - e bloqueada), "editar" e o
     * acesso completo de sempre. Ver podeAcessarModulo().
     */
    public const NIVEL_VISUALIZAR = 'visualizar';
    public const NIVEL_EDITAR = 'editar';

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
     * Rotulo e icone (Bootstrap Icons) de cada cargo, usados no
     * cadastro de usuario - "membro" e o padrao de quem nao tem uma
     * funcao definida na equipe (ex.: um admin), e por isso fica de
     * fora da galeria "Equipe" (ver User::todosAtivosParaEquipe()).
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

    private const SELECT_COLUNAS = 'id, name, email, password, role, active, musico, lider_louvor, cargo, instrumento, foto_path, membro_id, created_at';

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
        public readonly ?int $membroId = null,
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
            'SELECT u.id, u.name, u.email, u.password, u.role, u.active, u.musico, u.lider_louvor, u.cargo, u.instrumento, u.foto_path, u.membro_id, u.created_at
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
     * Usuarios ativos com login E com um cargo de verdade na equipe
     * (musico, midia ou equipamento), ordenados por cargo e depois por
     * nome - pra montar a galeria da tela "Equipe" (ver
     * EquipeController). Quem esta com o cargo padrao "membro" (ou
     * seja, sem funcao definida na equipe - normalmente um admin que so
     * usa o sistema) fica de fora daqui: essa pessoa nao tem cargo pra
     * mostrar na Equipe, e o card dela pertence e ao modulo Membros,
     * nao a este.
     *
     * @return array<int, self>
     */
    public static function todosAtivosParaEquipe(): array
    {
        $stmt = Database::connection()->query(
            "SELECT " . self::SELECT_COLUNAS . " FROM users
             WHERE active = 1 AND cargo != 'membro'
             ORDER BY FIELD(cargo, 'musico', 'midia', 'equipamento'), name ASC"
        );

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Dados de acesso ao sistema (cargo/instrumento/foto) de quem tem
     * um usuario ativo vinculado a cada um desses membros - usado na
     * listagem de Membros (ver MembroController::index()) pra mostrar
     * a mesma foto de perfil de "Meu perfil"/Equipe, e o badge de
     * cargo pra quem tem um cargo de verdade (view decide, comparando
     * com CARGO_MEMBRO) - sem fazer uma consulta por linha da pagina.
     *
     * @param array<int, int> $membroIds
     * @return array<int, array{cargo: string, instrumento: ?string, fotoPath: ?string}> chave = membro_id
     */
    public static function cargosPorMembroIds(array $membroIds): array
    {
        if ($membroIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($membroIds) as $indice => $membroId) {
            $chave = "membro_id_{$indice}";
            $placeholders[] = ":{$chave}";
            $params[$chave] = $membroId;
        }

        $stmt = Database::connection()->prepare(
            'SELECT membro_id, cargo, instrumento, foto_path FROM users
             WHERE active = 1 AND membro_id IN (' . implode(', ', $placeholders) . ')'
        );
        $stmt->execute($params);

        $porMembroId = [];
        foreach ($stmt->fetchAll() as $row) {
            $porMembroId[(int) $row['membro_id']] = [
                'cargo' => (string) $row['cargo'],
                'instrumento' => $row['instrumento'] ?? null,
                'fotoPath' => $row['foto_path'] ?? null,
            ];
        }

        return $porMembroId;
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

    /**
     * Vincula este usuario a um registro de Membros (mesma pessoa) -
     * ver Membro::vincularOuCriarParaUsuario(), chamado ao criar
     * qualquer conta de acesso, pra "Meu perfil" (PerfilController)
     * editar endereco/telefone/etc no mesmo lugar que aparece em
     * Membros, sem duplicar esse cadastro.
     */
    public function vincularMembro(int $membroId): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET membro_id = :membro_id WHERE id = :id');
        $stmt->execute(['id' => $this->id, 'membro_id' => $membroId]);
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
     * Modulos liberados especificamente pra este usuario (allow-list),
     * com o nivel de acesso de cada um - ver podeAcessarModulo() pra
     * como isso se combina com o papel e o plano contratado.
     *
     * @return array<string, string> slug => nivel (NIVEL_VISUALIZAR|NIVEL_EDITAR)
     */
    public static function modulosPermitidos(int $userId): array
    {
        $stmt = Database::connection()->prepare('SELECT modulo_slug, nivel FROM user_modulos WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Substitui de uma vez toda a lista de modulos liberados pra um
     * usuario - lista vazia remove toda restricao explicita (o usuario
     * volta a ter acesso a tudo que o plano libera, ver
     * podeAcessarModulo()).
     *
     * @param array<string, string> $modulosComNivel slug => nivel
     */
    public static function definirModulosPermitidos(int $userId, array $modulosComNivel): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $delete = $pdo->prepare('DELETE FROM user_modulos WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);

        $insert = $pdo->prepare(
            'INSERT INTO user_modulos (user_id, modulo_slug, nivel, created_at) VALUES (:user_id, :modulo_slug, :nivel, NOW())'
        );
        foreach ($modulosComNivel as $slug => $nivel) {
            $nivel = in_array($nivel, [self::NIVEL_VISUALIZAR, self::NIVEL_EDITAR], true) ? $nivel : self::NIVEL_VISUALIZAR;
            $insert->execute(['user_id' => $userId, 'modulo_slug' => $slug, 'nivel' => $nivel]);
        }

        $pdo->commit();
    }

    /**
     * Aplica o perfil padrao configurado pela igreja (Configuracoes >
     * Permissoes padrao, ver PermissaoPadrao) a um usuario recem-criado
     * - usado nos tres lugares que criam uma conta 'usuario': cadastro
     * combinado membro+acesso, autocadastro publico de membro e
     * cadastro manual em Usuarios.
     */
    public static function aplicarPerfilPadrao(int $userId): void
    {
        self::definirModulosPermitidos($userId, PermissaoPadrao::todas());
    }

    /**
     * Regra completa de acesso a um modulo por USUARIO (independente do
     * plano contratado, ver Igrejas\Models\Plano::disponivel() pra essa
     * outra camada - as duas precisam liberar pro acesso valer):
     *   1. sem usuario logado -> nunca acessa.
     *   2. 'admin' -> acessa tudo, sempre com nivel "editar".
     *   3. Usuarios/Permissoes/Configuracoes -> so 'admin' acessa, nunca
     *      configuravel via user_modulos.
     *   4. 'usuario' sem nenhuma linha em user_modulos -> acessa
     *      qualquer outro modulo com nivel "editar" (padrao: tudo que o
     *      plano libera, sem restricao - comportamento legado mantido
     *      pra contas criadas antes dessa distincao existir).
     *   5. 'usuario' com pelo menos uma linha em user_modulos -> vira
     *      allow-list; cada modulo listado so libera $acaoMinima se o
     *      nivel salvo pra ele for igual ou maior (visualizar < editar).
     *
     * $acaoMinima: NIVEL_VISUALIZAR (padrao) pra checar "consegue ver a
     * tela", NIVEL_EDITAR pra checar "consegue salvar/excluir algo" -
     * ver AuthMiddleware::bloquearSePermissaoNegada(), que escolhe com
     * base no metodo HTTP da requisicao.
     */
    public static function podeAcessarModulo(?self $user, string $slug, string $acaoMinima = self::NIVEL_VISUALIZAR): bool
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

        if ($modulosPermitidos === []) {
            return true;
        }

        if (!array_key_exists($slug, $modulosPermitidos)) {
            return false;
        }

        if ($acaoMinima === self::NIVEL_EDITAR) {
            return $modulosPermitidos[$slug] === self::NIVEL_EDITAR;
        }

        return true;
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
            membroId: isset($row['membro_id']) ? (int) $row['membro_id'] : null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
