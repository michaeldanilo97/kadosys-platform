<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Membro da equipe que atende clientes (barbeiro/cabeleireiro). Toda
 * consulta PRECISA filtrar por barbearia_id, senao vazaria a equipe de
 * uma barbearia pra outra.
 *
 * "diasAtendimento" guarda os dias da semana que o profissional
 * atende, no formato de DateTimeImmutable::format('w') (0 = domingo
 * ... 6 = sabado) - "horarioInicio"/"horarioFim" delimitam o
 * expediente. Os dois juntos alimentam o calculo de horarios
 * disponiveis do agendamento publico (ver
 * Barbearias\Controllers\AgendamentoPublicoController).
 */
final class Profissional
{
    private const SELECT_COLUNAS = 'id, barbearia_id, nome, especialidade, email, telefone, foto_path,
        dias_atendimento, horario_inicio, horario_fim, ativo, created_at';

    /** @param array<int, int> $diasAtendimento */
    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly string $nome,
        public readonly ?string $especialidade,
        public readonly ?string $email,
        public readonly ?string $telefone,
        public readonly ?string $fotoPath,
        public readonly array $diasAtendimento,
        public readonly ?string $horarioInicio,
        public readonly ?string $horarioFim,
        public readonly bool $ativo,
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
            $where .= ' AND (nome LIKE :busca OR especialidade LIKE :busca)';
            $params['busca'] = '%' . $search . '%';
        }

        $total = (int) self::contarComFiltro($where, $params);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . " FROM profissionais {$where} ORDER BY nome ASC LIMIT {$perPage} OFFSET {$offset}"
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
            'SELECT ' . self::SELECT_COLUNAS . ' FROM profissionais WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /** @return array<int, self> */
    public static function ativos(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM profissionais WHERE barbearia_id = :barbearia_id AND ativo = 1 ORDER BY nome ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function contarAtivos(int $barbeariaId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM profissionais WHERE barbearia_id = :barbearia_id AND ativo = 1'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Profissionais ATIVOS vinculados a uma unidade especifica - usado
     * pelo agendamento publico pra filtrar quem aparece quando a
     * barbearia tem mais de uma unidade (ver
     * Barbearias\Controllers\AgendamentoPublicoController).
     *
     * @return array<int, self>
     */
    public static function daUnidade(int $barbeariaId, int $unidadeId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.id, p.barbearia_id, p.nome, p.especialidade, p.email, p.telefone, p.foto_path,
                p.dias_atendimento, p.horario_inicio, p.horario_fim, p.ativo, p.created_at
             FROM profissionais p
             INNER JOIN profissional_unidades pu ON pu.profissional_id = p.id
             WHERE p.barbearia_id = :barbearia_id AND pu.unidade_id = :unidade_id AND p.ativo = 1
             ORDER BY p.nome ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId, 'unidade_id' => $unidadeId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /** @return array<int, int> */
    public static function unidadeIds(int $profissionalId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT unidade_id FROM profissional_unidades WHERE profissional_id = :profissional_id'
        );
        $stmt->execute(['profissional_id' => $profissionalId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Mapa profissional_id => lista de unidade_id, numa unica consulta
     * - usado pelo agendamento publico pra filtrar no navegador quais
     * profissionais aparecem conforme a unidade escolhida, sem precisar
     * de uma ida ao servidor a cada troca.
     *
     * @param array<int, int> $profissionalIds
     * @return array<int, array<int, int>>
     */
    public static function mapaUnidades(array $profissionalIds): array
    {
        if ($profissionalIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($profissionalIds), '?'));
        $stmt = Database::connection()->prepare(
            "SELECT profissional_id, unidade_id FROM profissional_unidades WHERE profissional_id IN ({$placeholders})"
        );
        $stmt->execute(array_values($profissionalIds));

        $mapa = [];

        foreach ($stmt->fetchAll() as $row) {
            $mapa[(int) $row['profissional_id']][] = (int) $row['unidade_id'];
        }

        return $mapa;
    }

    /**
     * Sincroniza as unidades onde esse profissional atende (substitui
     * o vinculo inteiro pela lista informada) - o controller e quem
     * garante que cada id em $unidadeIds realmente pertence a essa
     * barbearia antes de chamar isso.
     *
     * @param array<int, int> $unidadeIds
     */
    public static function definirUnidades(int $profissionalId, array $unidadeIds): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM profissional_unidades WHERE profissional_id = :profissional_id');
        $stmt->execute(['profissional_id' => $profissionalId]);

        $unidadeIds = array_values(array_unique(array_map('intval', $unidadeIds)));

        if ($unidadeIds === []) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO profissional_unidades (profissional_id, unidade_id) VALUES (:profissional_id, :unidade_id)'
        );

        foreach ($unidadeIds as $unidadeId) {
            $stmt->execute(['profissional_id' => $profissionalId, 'unidade_id' => $unidadeId]);
        }
    }

    /** @param array<int, int> $diasAtendimento */
    public static function create(
        int $barbeariaId,
        string $nome,
        ?string $especialidade,
        ?string $email,
        ?string $telefone,
        array $diasAtendimento,
        ?string $horarioInicio,
        ?string $horarioFim,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO profissionais
                (barbearia_id, nome, especialidade, email, telefone, dias_atendimento, horario_inicio, horario_fim, ativo, created_at)
             VALUES
                (:barbearia_id, :nome, :especialidade, :email, :telefone, :dias_atendimento, :horario_inicio, :horario_fim, 1, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'nome' => trim($nome),
            'especialidade' => self::nullable($especialidade),
            'email' => self::nullable($email),
            'telefone' => self::nullable($telefone),
            'dias_atendimento' => self::diasParaCsv($diasAtendimento),
            'horario_inicio' => self::nullable($horarioInicio),
            'horario_fim' => self::nullable($horarioFim),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<int, int> $diasAtendimento */
    public static function update(
        int $id,
        int $barbeariaId,
        string $nome,
        ?string $especialidade,
        ?string $email,
        ?string $telefone,
        array $diasAtendimento,
        ?string $horarioInicio,
        ?string $horarioFim,
        bool $ativo,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE profissionais SET
                nome = :nome, especialidade = :especialidade, email = :email, telefone = :telefone,
                dias_atendimento = :dias_atendimento, horario_inicio = :horario_inicio, horario_fim = :horario_fim, ativo = :ativo
             WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute([
            'nome' => trim($nome),
            'especialidade' => self::nullable($especialidade),
            'email' => self::nullable($email),
            'telefone' => self::nullable($telefone),
            'dias_atendimento' => self::diasParaCsv($diasAtendimento),
            'horario_inicio' => self::nullable($horarioInicio),
            'horario_fim' => self::nullable($horarioFim),
            'ativo' => $ativo ? 1 : 0,
            'id' => $id,
            'barbearia_id' => $barbeariaId,
        ]);
    }

    public static function atualizarFoto(int $id, int $barbeariaId, ?string $fotoPath): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE profissionais SET foto_path = :foto_path WHERE id = :id AND barbearia_id = :barbearia_id'
        );
        $stmt->execute(['foto_path' => $fotoPath, 'id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM profissionais WHERE id = :id AND barbearia_id = :barbearia_id');
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    private static function contarComFiltro(string $where, array $params): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM profissionais {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private static function nullable(?string $valor): ?string
    {
        $valor = $valor !== null ? trim($valor) : '';

        return $valor === '' ? null : $valor;
    }

    /** @param array<int, int> $dias */
    private static function diasParaCsv(array $dias): ?string
    {
        $dias = array_values(array_unique(array_filter($dias, static fn ($dia) => $dia >= 0 && $dia <= 6)));
        sort($dias);

        return $dias === [] ? null : implode(',', $dias);
    }

    private static function fromRow(array $row): self
    {
        $diasCsv = (string) ($row['dias_atendimento'] ?? '');
        $dias = $diasCsv === '' ? [] : array_map('intval', explode(',', $diasCsv));

        return new self(
            id: (int) $row['id'],
            barbeariaId: (int) $row['barbearia_id'],
            nome: (string) $row['nome'],
            especialidade: $row['especialidade'] ?? null,
            email: $row['email'] ?? null,
            telefone: $row['telefone'] ?? null,
            fotoPath: $row['foto_path'] ?? null,
            diasAtendimento: $dias,
            horarioInicio: $row['horario_inicio'] ?? null,
            horarioFim: $row['horario_fim'] ?? null,
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
