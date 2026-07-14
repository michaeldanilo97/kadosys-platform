<?php

declare(strict_types=1);

namespace Igrejas\Models;

use DateTimeImmutable;
use Igrejas\Core\Database;
use PDO;

/**
 * Model de Membro (modulo Membros).
 *
 * Segue o mesmo padrao de acesso a dados do restante da aplicacao: PDO
 * puro com prepared statements, sem ORM, sem multi-tenant (banco
 * exclusivo por instalacao).
 */
final class Membro
{
    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly ?string $email,
        public readonly ?string $telefone,
        public readonly ?string $dataNascimento,
        public readonly ?string $genero,
        public readonly ?string $estadoCivil,
        public readonly ?string $cpf,
        public readonly ?string $rg,
        public readonly ?string $naturalidade,
        public readonly ?string $endereco,
        public readonly ?string $cep,
        public readonly ?string $logradouro,
        public readonly ?string $numero,
        public readonly ?string $complemento,
        public readonly ?string $bairro,
        public readonly ?string $cidade,
        public readonly ?string $estado,
        public readonly ?string $dataMembresia,
        public readonly string $status,
        public readonly ?string $observacoes,
        public readonly string $createdAt,
    ) {
    }

    /**
     * Lista paginada de membros, com busca opcional por nome ou e-mail.
     *
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $page, int $perPage, string $search = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];

        if ($search !== '') {
            // Parametros nomeados repetidos na mesma query nao sao
            // suportados pelo driver MySQL do PDO com prepares nativos
            // (PDO::ATTR_EMULATE_PREPARES => false, ver Database.php) -
            // cada ocorrencia de LIKE precisa do seu proprio placeholder,
            // mesmo vinculado ao mesmo valor.
            $where = 'WHERE nome LIKE :search_nome OR email LIKE :search_email';
            $params['search_nome'] = '%' . $search . '%';
            $params['search_email'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM membros {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT * FROM membros {$where} ORDER BY nome ASC LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM membros WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function findByEmail(string $email): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM membros WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Vincula um usuario (login) ao registro de Membros da mesma
     * pessoa - usado ao criar uma conta de acesso "solta" (ver
     * UsuarioController::store()), sem passar pelo formulario de
     * Membros. Reaproveita um Membro existente com o mesmo e-mail se
     * houver (evita duplicar cadastro), senao cria um registro minimo
     * - garante que TODO usuario com acesso ao sistema tambem tenha um
     * perfil em Membros (endereco, telefone etc, editavel em "Meu
     * perfil"), inclusive admin.
     */
    public static function vincularOuCriarParaUsuario(User $user, string $nome, ?string $email): void
    {
        $membro = $email !== null && $email !== '' ? self::findByEmail($email) : null;
        $membroId = $membro?->id ?? self::create(['nome' => $nome, 'email' => $email, 'status' => 'ativo']);

        $user->vincularMembro($membroId);
    }

    /**
     * Lista enxuta de membros ativos (id + nome), ordenada por nome.
     * Usada em selects de outros modulos (ex.: lider/voluntarios de
     * ministerios), sem o custo de paginacao completa.
     *
     * @return array<int, self>
     */
    public static function allActive(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM membros WHERE status = 'ativo' ORDER BY nome ASC"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO membros
                (nome, email, telefone, data_nascimento, genero, estado_civil, cpf, rg, naturalidade,
                 endereco, cep, logradouro, numero, complemento, bairro, cidade, estado,
                 data_membresia, status, observacoes, created_at)
             VALUES
                (:nome, :email, :telefone, :data_nascimento, :genero, :estado_civil, :cpf, :rg, :naturalidade,
                 :endereco, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado,
                 :data_membresia, :status, :observacoes, NOW())'
        );
        $stmt->execute(self::bindings($data));

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE membros SET
                nome = :nome, email = :email, telefone = :telefone, data_nascimento = :data_nascimento,
                genero = :genero, estado_civil = :estado_civil, cpf = :cpf, rg = :rg, naturalidade = :naturalidade,
                endereco = :endereco, cep = :cep, logradouro = :logradouro, numero = :numero,
                complemento = :complemento, bairro = :bairro, cidade = :cidade,
                estado = :estado, data_membresia = :data_membresia, status = :status, observacoes = :observacoes
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM membros WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function countActive(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM membros WHERE status = 'ativo'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public static function countCreatedSince(DateTimeImmutable $since): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM membros WHERE created_at >= :since');
        $stmt->execute(['since' => $since->format('Y-m-d H:i:s')]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Usado pelo auto-cadastro publico de membros (ver
     * MembroPublicoController) pra evitar duplicidade obvia quando o
     * e-mail informado ja pertence a um membro existente.
     */
    public static function emailEmUso(string $email): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM membros WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        return $stmt->fetchColumn() !== false;
    }

    public function idade(): ?int
    {
        if ($this->dataNascimento === null) {
            return null;
        }

        return (new DateTimeImmutable($this->dataNascimento))->diff(new DateTimeImmutable())->y;
    }

    /**
     * Aniversariantes de um mes (1-12), ordenados pelo dia - usado na
     * tela de calendario (modulo Agenda).
     *
     * @return array<int, self>
     */
    public static function aniversariantesNoMes(int $mes): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM membros
             WHERE status = 'ativo' AND data_nascimento IS NOT NULL AND MONTH(data_nascimento) = :mes
             ORDER BY DAY(data_nascimento) ASC"
        );
        $stmt->execute(['mes' => $mes]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Aniversariantes de hoje, usado pelo cron que envia o e-mail
     * automatico de parabens (ver cron/enviar_parabens_aniversario.php).
     *
     * @return array<int, self>
     */
    public static function aniversariantesHoje(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM membros
             WHERE status = 'ativo' AND data_nascimento IS NOT NULL
               AND MONTH(data_nascimento) = MONTH(CURDATE()) AND DAY(data_nascimento) = DAY(CURDATE())"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        return [
            'nome' => trim((string) $data['nome']),
            'email' => self::nullable($data['email'] ?? null),
            'telefone' => self::nullable($data['telefone'] ?? null),
            'data_nascimento' => self::nullable($data['data_nascimento'] ?? null),
            'genero' => self::nullable($data['genero'] ?? null),
            'estado_civil' => self::nullable($data['estado_civil'] ?? null),
            'cpf' => self::nullable($data['cpf'] ?? null),
            'rg' => self::nullable($data['rg'] ?? null),
            'naturalidade' => self::nullable($data['naturalidade'] ?? null),
            'endereco' => self::nullable($data['endereco'] ?? null),
            'cep' => self::nullable($data['cep'] ?? null),
            'logradouro' => self::nullable($data['logradouro'] ?? null),
            'numero' => self::nullable($data['numero'] ?? null),
            'complemento' => self::nullable($data['complemento'] ?? null),
            'bairro' => self::nullable($data['bairro'] ?? null),
            'cidade' => self::nullable($data['cidade'] ?? null),
            'estado' => self::nullable($data['estado'] ?? null),
            'data_membresia' => self::nullable($data['data_membresia'] ?? null),
            'status' => in_array($data['status'] ?? null, ['ativo', 'inativo'], true) ? $data['status'] : 'ativo',
            'observacoes' => self::nullable($data['observacoes'] ?? null),
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Hidrata uma instancia a partir de uma linha bruta do banco. Publico
     * para permitir reaproveitamento por outros models que fazem JOIN com
     * a tabela membros (ex.: Ministerio::voluntarios()).
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            email: $row['email'],
            telefone: $row['telefone'],
            dataNascimento: $row['data_nascimento'],
            genero: $row['genero'],
            estadoCivil: $row['estado_civil'],
            cpf: $row['cpf'] ?? null,
            rg: $row['rg'] ?? null,
            naturalidade: $row['naturalidade'] ?? null,
            endereco: $row['endereco'],
            cep: $row['cep'],
            logradouro: $row['logradouro'] ?? null,
            numero: $row['numero'] ?? null,
            complemento: $row['complemento'] ?? null,
            bairro: $row['bairro'] ?? null,
            cidade: $row['cidade'],
            estado: $row['estado'],
            dataMembresia: $row['data_membresia'],
            status: (string) $row['status'],
            observacoes: $row['observacoes'],
            createdAt: (string) $row['created_at'],
        );
    }
}
