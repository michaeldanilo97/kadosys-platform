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
            $where .= ' AND (c.nome LIKE :busca OR p.nome LIKE :busca2 OR s.nome LIKE :busca3)';
            $params['busca'] = '%' . $search . '%';
            $params['busca2'] = '%' . $search . '%';
            $params['busca3'] = '%' . $search . '%';
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

    /**
     * Fila do dia (nao-cancelados) pra um dia especifico, com filtro
     * opcional de unidade - usado pelo painel de recepcao (ver
     * Barbearias\Controllers\RecepcaoController), uma tela em tela
     * cheia com auto-atualizacao pra ficar numa TV do salao.
     *
     * @return array<int, self>
     */
    public static function doDia(int $barbeariaId, \DateTimeImmutable $dia, int $unidadeId = 0): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE a.barbearia_id = :barbearia_id AND DATE(a.data_hora) = :dia AND a.status != 'cancelado'";
        $params = ['barbearia_id' => $barbeariaId, 'dia' => $dia->format('Y-m-d')];

        if ($unidadeId > 0) {
            $sql .= ' AND a.unidade_id = :unidade_id';
            $params['unidade_id'] = $unidadeId;
        }

        $stmt = Database::connection()->prepare($sql . ' ORDER BY a.data_hora ASC');
        $stmt->execute($params);

        return array_map(self::fromRow(...), $stmt->fetchAll());
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
     * $excluirId serve pro reagendamento: o proprio agendamento que
     * esta sendo movido ainda esta com status 'agendado' ate a troca
     * ser confirmada, entao sem isso ele apareceria como conflito
     * consigo mesmo.
     *
     * @return array<int, self>
     */
    public static function doDiaPorProfissional(int $barbeariaId, int $profissionalId, string $data, int $excluirId = 0): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE a.barbearia_id = :barbearia_id AND a.profissional_id = :profissional_id
               AND DATE(a.data_hora) = :data AND a.status != 'cancelado'";
        $params = ['barbearia_id' => $barbeariaId, 'profissional_id' => $profissionalId, 'data' => $data];

        if ($excluirId > 0) {
            $sql .= ' AND a.id != :excluir_id';
            $params['excluir_id'] = $excluirId;
        }

        $stmt = Database::connection()->prepare($sql . ' ORDER BY a.data_hora ASC');
        $stmt->execute($params);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Fechamento de comissao por profissional num periodo: agrupa os
     * atendimentos CONCLUIDOS, usando como base de calculo o valor
     * efetivamente cobrado no PDV (financeiro_lancamentos.valor, pelo
     * agendamento_id) e, quando o atendimento foi concluido sem
     * registrar pagamento, cai pro preco atual do servico.
     *
     * @return array<int, array{profissionalId: int, profissionalNome: string, percentualComissao: float, quantidade: int, totalServicos: float, totalComissao: float}>
     */
    public static function comissoesPorProfissional(int $barbeariaId, string $dataInicio, string $dataFim, int $profissionalId = 0): array
    {
        $sql = "SELECT a.profissional_id, p.nome AS profissional_nome, p.percentual_comissao,
                COUNT(a.id) AS quantidade,
                COALESCE(SUM(COALESCE(fl.valor, s.preco)), 0) AS total_servicos
             FROM agendamentos a
             INNER JOIN profissionais p ON p.id = a.profissional_id
             INNER JOIN servicos s ON s.id = a.servico_id
             LEFT JOIN financeiro_lancamentos fl ON fl.agendamento_id = a.id AND fl.barbearia_id = a.barbearia_id
             WHERE a.barbearia_id = :barbearia_id AND a.status = 'concluido'
               AND a.data_hora BETWEEN :inicio AND :fim";
        $params = ['barbearia_id' => $barbeariaId, 'inicio' => $dataInicio, 'fim' => $dataFim];

        if ($profissionalId > 0) {
            $sql .= ' AND a.profissional_id = :profissional_id';
            $params['profissional_id'] = $profissionalId;
        }

        $sql .= ' GROUP BY a.profissional_id, p.nome, p.percentual_comissao ORDER BY p.nome ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return array_map(static function (array $row): array {
            $percentual = (float) $row['percentual_comissao'];
            $totalServicos = (float) $row['total_servicos'];

            return [
                'profissionalId' => (int) $row['profissional_id'],
                'profissionalNome' => (string) $row['profissional_nome'],
                'percentualComissao' => $percentual,
                'quantidade' => (int) $row['quantidade'],
                'totalServicos' => $totalServicos,
                'totalComissao' => round($totalServicos * $percentual / 100, 2),
            ];
        }, $stmt->fetchAll());
    }

    /**
     * Quantidade de agendamentos por status num periodo - usado no
     * relatorio consolidado (ver Barbearias\Controllers\RelatorioController).
     *
     * @return array{agendado: int, concluido: int, cancelado: int}
     */
    public static function contarPorStatusNoPeriodo(int $barbeariaId, string $dataInicio, string $dataFim): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT status, COUNT(*) AS total FROM agendamentos
             WHERE barbearia_id = :barbearia_id AND data_hora BETWEEN :inicio AND :fim
             GROUP BY status'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'inicio' => $dataInicio, 'fim' => $dataFim]);

        $contagem = [self::STATUS_AGENDADO => 0, self::STATUS_CONCLUIDO => 0, self::STATUS_CANCELADO => 0];

        foreach ($stmt->fetchAll() as $row) {
            $contagem[(string) $row['status']] = (int) $row['total'];
        }

        return $contagem;
    }

    /**
     * Faturamento total de atendimentos CONCLUIDOS num periodo (mesma
     * base de calculo da comissao: valor pago no PDV quando existe,
     * senao o preco atual do servico) - usado no relatorio consolidado
     * pra calcular o ticket medio.
     *
     * @return array{quantidade: int, total: float}
     */
    public static function faturamentoServicosNoPeriodo(int $barbeariaId, string $dataInicio, string $dataFim): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(a.id) AS quantidade, COALESCE(SUM(COALESCE(fl.valor, s.preco)), 0) AS total
             FROM agendamentos a
             INNER JOIN servicos s ON s.id = a.servico_id
             LEFT JOIN financeiro_lancamentos fl ON fl.agendamento_id = a.id AND fl.barbearia_id = a.barbearia_id
             WHERE a.barbearia_id = :barbearia_id AND a.status = 'concluido'
               AND a.data_hora BETWEEN :inicio AND :fim"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'inicio' => $dataInicio, 'fim' => $dataFim]);
        $row = $stmt->fetch();

        return ['quantidade' => (int) ($row['quantidade'] ?? 0), 'total' => (float) ($row['total'] ?? 0)];
    }

    /**
     * Minutos ocupados (soma da duracao dos servicos) por profissional,
     * considerando so atendimentos CONCLUIDOS no periodo - usado no
     * relatorio consolidado pra calcular a taxa de ocupacao.
     *
     * @return array<int, int> profissional_id => minutos
     */
    public static function minutosOcupadosPorProfissional(int $barbeariaId, string $dataInicio, string $dataFim): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT a.profissional_id, COALESCE(SUM(s.duracao_minutos), 0) AS minutos
             FROM agendamentos a
             INNER JOIN servicos s ON s.id = a.servico_id
             WHERE a.barbearia_id = :barbearia_id AND a.status = 'concluido'
               AND a.data_hora BETWEEN :inicio AND :fim
             GROUP BY a.profissional_id"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'inicio' => $dataInicio, 'fim' => $dataFim]);

        $mapa = [];

        foreach ($stmt->fetchAll() as $row) {
            $mapa[(int) $row['profissional_id']] = (int) $row['minutos'];
        }

        return $mapa;
    }

    /**
     * Atendimentos concluidos de UM profissional num periodo, com o
     * valor usado na base de calculo da comissao - usado no detalhe do
     * relatorio de fechamento (ver Barbearias\Controllers\ComissaoController).
     *
     * @return array<int, self>
     */
    public static function concluidosPorProfissionalNoPeriodo(int $barbeariaId, int $profissionalId, string $dataInicio, string $dataFim): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' ' . self::JOINS . "
             WHERE a.barbearia_id = :barbearia_id AND a.profissional_id = :profissional_id
               AND a.status = 'concluido' AND a.data_hora BETWEEN :inicio AND :fim
             ORDER BY a.data_hora ASC"
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'profissional_id' => $profissionalId,
            'inicio' => $dataInicio,
            'fim' => $dataFim,
        ]);

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

    /**
     * Reagendamento: so muda a data/hora, mantendo profissional,
     * servico e status como estao - usado pela area do cliente (ver
     * Barbearias\Controllers\ClienteAreaController::reagendar).
     */
    public static function reagendar(int $id, int $barbeariaId, string $novaDataHora): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE agendamentos SET data_hora = :data_hora WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute(['data_hora' => $novaDataHora, 'id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM agendamentos WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    /**
     * Agendamentos de amanha ainda sem lembrete enviado, com cliente com
     * e-mail cadastrado e barbearia ativa num plano pago (o lembrete
     * automatico e beneficio do Plus/Premium, nao do Essencial - ver
     * Barbearias\Models\Plano::FEATURES). Cruza todas as barbearias de
     * uma vez so (banco unico) - usado pelo cron
     * enviar_lembretes_agendamento.php.
     *
     * @return array<int, array{id: int, dataHora: string, clienteNome: string, clienteEmail: string, barbeariaNome: string, profissionalNome: string, servicoNome: string}>
     */
    public static function pendentesLembreteAmanha(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT a.id, a.data_hora, c.nome AS cliente_nome, c.email AS cliente_email,
                    b.nome AS barbearia_nome, p.nome AS profissional_nome, s.nome AS servico_nome
             FROM agendamentos a
             INNER JOIN barbearias b ON b.id = a.barbearia_id
             INNER JOIN clientes c ON c.id = a.cliente_id
             INNER JOIN profissionais p ON p.id = a.profissional_id
             INNER JOIN servicos s ON s.id = a.servico_id
             WHERE a.status = :status AND a.lembrete_enviado_em IS NULL
               AND DATE(a.data_hora) = CURDATE() + INTERVAL 1 DAY
               AND c.email IS NOT NULL AND c.email <> ''
               AND b.status = :barbearia_status AND b.plano != :plano_essencial
             ORDER BY a.data_hora ASC"
        );
        $stmt->execute([
            'status' => self::STATUS_AGENDADO,
            'barbearia_status' => Barbearia::STATUS_ATIVA,
            'plano_essencial' => Plano::ESSENCIAL,
        ]);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'dataHora' => (string) $row['data_hora'],
                'clienteNome' => (string) $row['cliente_nome'],
                'clienteEmail' => (string) $row['cliente_email'],
                'barbeariaNome' => (string) $row['barbearia_nome'],
                'profissionalNome' => (string) $row['profissional_nome'],
                'servicoNome' => (string) $row['servico_nome'],
            ];
        }, $stmt->fetchAll());
    }

    public static function marcarLembreteEnviado(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE agendamentos SET lembrete_enviado_em = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Segundo lembrete, de ultima hora - agendamentos comecando dentro
     * dos proximos ~25min (a janela cobre um pequeno atraso do cron, que
     * roda a cada 20min - ver cron/enviar_lembretes_proximos.php) e
     * ainda sem ESSE lembrete especifico enviado (independente do
     * lembrete do dia anterior, que usa outra coluna). Mesmas regras de
     * plano/e-mail/barbearia ativa do lembrete do dia anterior.
     *
     * @return array<int, array{id: int, dataHora: string, clienteNome: string, clienteEmail: string, barbeariaNome: string, profissionalNome: string, servicoNome: string}>
     */
    public static function pendentesLembreteProximo(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT a.id, a.data_hora, c.nome AS cliente_nome, c.email AS cliente_email,
                    b.nome AS barbearia_nome, p.nome AS profissional_nome, s.nome AS servico_nome
             FROM agendamentos a
             INNER JOIN barbearias b ON b.id = a.barbearia_id
             INNER JOIN clientes c ON c.id = a.cliente_id
             INNER JOIN profissionais p ON p.id = a.profissional_id
             INNER JOIN servicos s ON s.id = a.servico_id
             WHERE a.status = :status AND a.lembrete_proximo_enviado_em IS NULL
               AND a.data_hora BETWEEN NOW() AND NOW() + INTERVAL 25 MINUTE
               AND c.email IS NOT NULL AND c.email <> ''
               AND b.status = :barbearia_status AND b.plano != :plano_essencial
             ORDER BY a.data_hora ASC"
        );
        $stmt->execute([
            'status' => self::STATUS_AGENDADO,
            'barbearia_status' => Barbearia::STATUS_ATIVA,
            'plano_essencial' => Plano::ESSENCIAL,
        ]);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'dataHora' => (string) $row['data_hora'],
                'clienteNome' => (string) $row['cliente_nome'],
                'clienteEmail' => (string) $row['cliente_email'],
                'barbeariaNome' => (string) $row['barbearia_nome'],
                'profissionalNome' => (string) $row['profissional_nome'],
                'servicoNome' => (string) $row['servico_nome'],
            ];
        }, $stmt->fetchAll());
    }

    public static function marcarLembreteProximoEnviado(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE agendamentos SET lembrete_proximo_enviado_em = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
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
