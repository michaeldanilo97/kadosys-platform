<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Cliente da barbearia. Toda consulta PRECISA filtrar por
 * barbearia_id, senao vazaria a carteira de clientes de uma barbearia
 * pra outra.
 *
 * "password" comeca nulo - vira uma conta de verdade (login na area
 * do cliente, ver Barbearias\Core\ClienteAuth) so quando o proprio
 * cliente se cadastra em /minha-conta/{slug}/cadastro.
 */
final class Cliente
{
    private const SELECT_COLUNAS = 'id, barbearia_id, nome, telefone, email, data_nascimento, password, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly string $nome,
        public readonly ?string $telefone,
        public readonly ?string $email,
        public readonly ?string $dataNascimento,
        public readonly ?string $passwordHash,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $barbeariaId, int $page, int $perPage, string $search = ''): array
    {
        $where = 'WHERE barbearia_id = :barbearia_id';
        $params = ['barbearia_id' => $barbeariaId];

        if ($search !== '') {
            $where .= ' AND (nome LIKE :busca OR telefone LIKE :busca OR email LIKE :busca)';
            $params['busca'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM clientes {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => $lastPage,
        ];
    }

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM clientes WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Usado pelo agendamento publico pra reconhecer um cliente que ja
     * agendou antes (mesmo telefone) em vez de criar um cadastro
     * duplicado a cada visita, e pelo login da area do cliente.
     */
    public static function buscarPorTelefone(int $barbeariaId, string $telefone): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM clientes WHERE barbearia_id = :barbearia_id AND telefone = :telefone LIMIT 1'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'telefone' => $telefone]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function todos(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM clientes WHERE barbearia_id = :barbearia_id ORDER BY nome ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function contar(int $barbeariaId): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM clientes WHERE barbearia_id = :barbearia_id');
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Clientes que ja tiveram pelo menos um agendamento (nao-cancelado)
     * mas cujo agendamento mais recente foi ha mais de $dias dias -
     * quem tem um agendamento futuro marcado NAO aparece aqui (o mais
     * recente seria uma data futura, maior que o limite). Usado pelo
     * CRM ("clientes inativos") pra dar uma lista de quem a barbearia
     * pode tentar reativar.
     *
     * @return array<int, array{cliente: self, ultimaVisita: string}>
     */
    public static function inativos(int $barbeariaId, int $dias): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.id, c.barbearia_id, c.nome, c.telefone, c.email, c.data_nascimento, c.password, c.created_at,
                MAX(a.data_hora) AS ultima_visita
             FROM clientes c
             INNER JOIN agendamentos a ON a.cliente_id = c.id AND a.status != 'cancelado'
             WHERE c.barbearia_id = :barbearia_id
             GROUP BY c.id
             HAVING MAX(a.data_hora) < :limite
             ORDER BY ultima_visita ASC"
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'limite' => (new \DateTimeImmutable('-' . $dias . ' days'))->format('Y-m-d H:i:s'),
        ]);

        return array_map(
            static fn (array $row) => ['cliente' => self::fromRow($row), 'ultimaVisita' => (string) $row['ultima_visita']],
            $stmt->fetchAll(),
        );
    }

    /**
     * Clientes com aniversario num mes especifico (1-12), ordenados
     * pelo dia - usado pelo CRM ("aniversariantes do mes").
     *
     * @return array<int, self>
     */
    public static function aniversariantesDoMes(int $barbeariaId, int $mes): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM clientes
             WHERE barbearia_id = :barbearia_id AND data_nascimento IS NOT NULL AND MONTH(data_nascimento) = :mes
             ORDER BY DAY(data_nascimento) ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'mes' => $mes]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(int $barbeariaId, string $nome, ?string $telefone, ?string $email, ?string $dataNascimento = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO clientes (barbearia_id, nome, telefone, email, data_nascimento, created_at)
             VALUES (:barbearia_id, :nome, :telefone, :email, :data_nascimento, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'nome' => trim($nome),
            'telefone' => self::nullable($telefone),
            'email' => self::nullable($email),
            'data_nascimento' => self::nullable($dataNascimento),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, int $barbeariaId, string $nome, ?string $telefone, ?string $email, ?string $dataNascimento): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE clientes SET nome = :nome, telefone = :telefone, email = :email, data_nascimento = :data_nascimento
             WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'telefone' => self::nullable($telefone),
            'email' => self::nullable($email),
            'data_nascimento' => self::nullable($dataNascimento),
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM clientes WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    /**
     * Define a senha de acesso a area do cliente - usado tanto pra
     * "reivindicar" um cadastro que ja existia (criado por um
     * agendamento anterior sem conta) quanto pra trocar a senha depois.
     */
    public static function definirSenha(int $id, int $barbeariaId, string $senha): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE clientes SET password = :password WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute([
            'password' => password_hash($senha, PASSWORD_BCRYPT),
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);
    }

    public function temSenha(): bool
    {
        return $this->passwordHash !== null;
    }

    public function verifyPassword(string $senha): bool
    {
        return $this->passwordHash !== null && password_verify($senha, $this->passwordHash);
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM clientes {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function nullable(?string $valor): ?string
    {
        $valor = $valor !== null ? trim($valor) : '';

        return $valor === '' ? null : $valor;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            nome: (string) $row['nome'],
            telefone: $row['telefone'] ?? null,
            email: $row['email'] ?? null,
            dataNascimento: $row['data_nascimento'] ?? null,
            passwordHash: $row['password'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
