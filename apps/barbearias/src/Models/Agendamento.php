<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Agendamento de um cliente com um profissional, pra um servico, numa
 * data/hora. Toda consulta PRECISA filtrar por barbearia_id (sempre
 * via o prefixo "a." - a tabela de agendamentos - ja que os JOINs
 * trazem profissional/servico/cliente de QUALQUER barbearia se a
 * clausula WHERE nao filtrar corretamente).
 */
final class Agendamento
{
    public const STATUS_AGENDADO = 'agendado';
    public const STATUS_CONCLUIDO = 'concluido';
    public const STATUS_CANCELADO = 'cancelado';

    private const SELECT_COLUNAS = 'a.id, a.barbearia_id, a.unidade_id, a.profissional_id, a.servico_id, a.cliente_id,
        a.data_hora, a.status, a.observacoes, a.created_at,
        p.nome AS profissional_nome,
        s.nome AS servico_nome, s.preco AS servico_preco, s.duracao_minutos AS servico_duracao,
        c.nome AS cliente_nome, c.telefone AS cliente_telefone,
        u.nome AS unidade_nome';

    private const JOINS = 'FROM agendamentos a
        INNER JOIN profissionais p ON p.id = a.profissional_id
        INNER JOIN servicos s ON s.id = a.servico_id
        INNER JOIN clientes c ON c.id = a.cliente_id
        LEFT JOIN unidades u ON u.id = a.unidade_id';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly ?int $unidadeId,
        public readonly int $profissionalId,
        public readonly int $servicoId,
        public readonly int $clienteId,
        public readonly string $dataHora,
        public readonly string $status,
        public readonly ?string $observacoes,
        public readonly string $profissionalNome,
        public readonly string $servicoNome,
        public readonly float $servicoPreco,
        public readonly int $servicoDuracao,
        public readonly string $clienteNome,
        public readonly ?string $clienteTelefone,
        public readonly ?string $unidadeNome,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $barbeariaId, int $page, int $perPage, string $search = '', string $status = '', int $unidadeId = 0): array
    {
        $where = 'WHERE a.barbearia_id = :barbearia_id';
        $params = ['barbearia_id' => $barbeariaId];

        if ($search !== '') {
            $where .= ' AND (c.nome LIKE :busca OR p.nome LIKE :busca OR s.nome LIKE :busca)';
            $params['busca'] = '%' . $search . '%';
        }

        if (in_array($status, [self::STATUS_AGENDADO, self::STATUS_CONCLUIDO, self::STATUS_CANCELADO], true)) {
            $where .= ' AND a.status = :status';
            $params['status'] = $status;
        }

        if ($unidadeId > 0) {
            $where .= ' AND a.unidade_id = :unidade_id';
            $params['unidade_id'] = $unidadeId;
        }

        $stmtTotal = Database::connection()->prepare('SELECT COUNT(*) ' . self::JOINS . ' ' . $where);
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . " {$where} ORDER BY a.data_hora DESC LIMIT {$perPage} OFFSET {$offset}"
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
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . ' WHERE a.id = :id AND a.barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function contarDoDia(int $barbeariaId, \DateTimeImmutable $dia): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM agendamentos
             WHERE barbearia_id = :barbearia_id AND DATE(data_hora) = :dia AND status != 'cancelado'"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'dia' => $dia->format('Y-m-d')]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, self> */
    public static function proximos(int $barbeariaId, int $limite): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE a.barbearia_id = :barbearia_id AND a.status = 'agendado' AND a.data_hora >= NOW()
             ORDER BY a.data_hora ASC LIMIT {$limite}"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Proximos agendamentos de UM cliente especifico - usado pela area
     * do cliente (Barbearias\Controllers\ClienteAreaController).
     *
     * @return array<int, self>
     */
    public static function proximosDoCliente(int $barbeariaId, int $clienteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE a.barbearia_id = :barbearia_id AND a.cliente_id = :cliente_id
               AND a.status = 'agendado' AND a.data_hora >= NOW()
             ORDER BY a.data_hora ASC"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'cliente_id' => $clienteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Historico (passado ou cancelado) de UM cliente especifico -
     * usado pela area do cliente.
     *
     * @return array<int, self>
     */
    public static function historicoDoCliente(int $barbeariaId, int $clienteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE a.barbearia_id = :barbearia_id AND a.cliente_id = :cliente_id
               AND (a.status != 'agendado' OR a.data_hora < NOW())
             ORDER BY a.data_hora DESC"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'cliente_id' => $clienteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Agendamentos ja marcados de um profissional num dia especifico
     * (nao-cancelados) - usado pra calcular quais horarios ainda estao
     * livres no agendamento publico (ver
     * Barbearias\Controllers\AgendamentoPublicoController).
     *
     * @return array<int, self>
     */
    public static function doDiaPorProfissional(int $barbeariaId, int $profissionalId, string $data): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE a.barbearia_id = :barbearia_id AND a.profissional_id = :profissional_id
               AND DATE(a.data_hora) = :data AND a.status != 'cancelado'
             ORDER BY a.data_hora ASC"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'profissional_id' => $profissionalId, 'data' => $data]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(
        int $barbeariaId,
        int $profissionalId,
        int $servicoId,
        int $clienteId,
        string $dataHora,
        ?string $observacoes,
        ?int $unidadeId = null,
    ): int {
        $stmt = Database::connection()->prepare(
            "INSERT INTO agendamentos (barbearia_id, unidade_id, profissional_id, servico_id, cliente_id, data_hora, status, observacoes, created_at)
             VALUES (:barbearia_id, :unidade_id, :profissional_id, :servico_id, :cliente_id, :data_hora, 'agendado', :observacoes, NOW())"
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'unidade_id' => $unidadeId,
            'profissional_id' => $profissionalId,
            'servico_id' => $servicoId,
            'cliente_id' => $clienteId,
            'data_hora' => $dataHora,
            'observacoes' => self::nullable($observacoes),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(
        int $id,
        int $barbeariaId,
        int $profissionalId,
        int $servicoId,
        int $clienteId,
        string $dataHora,
        string $status,
        ?string $observacoes,
        ?int $unidadeId = null,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE agendamentos SET profissional_id = :profissional_id, servico_id = :servico_id, cliente_id = :cliente_id,
                data_hora = :data_hora, status = :status, observacoes = :observacoes, unidade_id = :unidade_id
             WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute([
            'profissional_id' => $profissionalId,
            'servico_id' => $servicoId,
            'cliente_id' => $clienteId,
            'data_hora' => $dataHora,
            'status' => $status,
            'observacoes' => self::nullable($observacoes),
            'unidade_id' => $unidadeId,
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);
    }

    public static function atualizarStatus(int $id, int $barbeariaId, string $status): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE agendamentos SET status = :status WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute(['status' => $status, 'id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM agendamentos WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
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
            unidadeId: $row['unidade_id'] !== null ? (int) $row['unidade_id'] : null,
            profissionalId: (int) $row['profissional_id'],
            servicoId: (int) $row['servico_id'],
            clienteId: (int) $row['cliente_id'],
            dataHora: (string) $row['data_hora'],
            status: (string) $row['status'],
            observacoes: $row['observacoes'] ?? null,
            profissionalNome: (string) $row['profissional_nome'],
            servicoNome: (string) $row['servico_nome'],
            servicoPreco: (float) $row['servico_preco'],
            servicoDuracao: (int) $row['servico_duracao'],
            clienteNome: (string) $row['cliente_nome'],
            clienteTelefone: $row['cliente_telefone'] ?? null,
            unidadeNome: $row['unidade_nome'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
