<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de AgendaEvento (modulo Agenda: eventos, reunioes e reservas
 * de espaco da igreja).
 */
final class AgendaEvento
{
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly string $tipo,
        public readonly string $data,
        public readonly ?string $horaInicio,
        public readonly ?string $horaFim,
        public readonly ?string $local,
        public readonly ?int $responsavelMembroId,
        public readonly ?string $responsavelNome,
        public readonly ?string $descricao,
        public readonly string $status,
        public readonly string $visibilidade,
        public readonly ?int $criadoPorUserId,
        public readonly string $createdAt,
    ) {
    }

    public function ehPrivado(): bool
    {
        return $this->visibilidade === 'privado';
    }

    public function podeSerVistoPor(int $userId): bool
    {
        return $this->visibilidade === 'publico' || $this->criadoPorUserId === $userId;
    }

    /**
     * Eventos publicos (visiveis pra todo mundo) mais os privados do
     * proprio usuario logado, dentro de um mes (YYYY-MM) - usado na
     * tela de calendario.
     *
     * @return array<int, self>
     */
    public static function noMesVisiveisPara(string $mes, int $userId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT ae.*, me.nome AS responsavel_nome
             FROM agenda_eventos ae
             LEFT JOIN membros me ON me.id = ae.responsavel_membro_id
             WHERE DATE_FORMAT(ae.data, '%Y-%m') = :mes
               AND (ae.visibilidade = 'publico' OR ae.criado_por_user_id = :user_id)
             ORDER BY ae.data ASC, ae.hora_inicio ASC"
        );
        $stmt->execute(['mes' => $mes, 'user_id' => $userId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $page, int $perPage, string $search, int $userId): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $condicoes = ["(ae.visibilidade = 'publico' OR ae.criado_por_user_id = :user_id)"];
        $params = ['user_id' => $userId];

        if ($search !== '') {
            // Parametros nomeados repetidos na mesma query nao sao
            // suportados pelo driver MySQL do PDO com prepares nativos
            // (PDO::ATTR_EMULATE_PREPARES => false, ver Database.php) -
            // cada ocorrencia de LIKE precisa do seu proprio placeholder,
            // mesmo vinculado ao mesmo valor.
            $condicoes[] = '(ae.titulo LIKE :search_titulo OR ae.local LIKE :search_local)';
            $params['search_titulo'] = '%' . $search . '%';
            $params['search_local'] = '%' . $search . '%';
        }

        $where = 'WHERE ' . implode(' AND ', $condicoes);

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM agenda_eventos ae {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT ae.*, me.nome AS responsavel_nome
             FROM agenda_eventos ae
             LEFT JOIN membros me ON me.id = ae.responsavel_membro_id
             {$where}
             ORDER BY ae.data ASC, ae.hora_inicio ASC
             LIMIT :limit OFFSET :offset"
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
        $stmt = Database::connection()->prepare(
            'SELECT ae.*, me.nome AS responsavel_nome
             FROM agenda_eventos ae
             LEFT JOIN membros me ON me.id = ae.responsavel_membro_id
             WHERE ae.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Proximo evento agendado (data >= hoje, nao cancelado), usado no
     * KPI do dashboard.
     */
    public static function proximoAgendado(): ?self
    {
        $stmt = Database::connection()->prepare(
            "SELECT ae.*, me.nome AS responsavel_nome
             FROM agenda_eventos ae
             LEFT JOIN membros me ON me.id = ae.responsavel_membro_id
             WHERE ae.data >= CURDATE() AND ae.status != 'cancelado'
             ORDER BY ae.data ASC, ae.hora_inicio ASC
             LIMIT 1"
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data, ?int $criadoPorUserId): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO agenda_eventos
                (titulo, tipo, data, hora_inicio, hora_fim, local, responsavel_membro_id, descricao, status, visibilidade, criado_por_user_id, created_at)
             VALUES
                (:titulo, :tipo, :data, :hora_inicio, :hora_fim, :local, :responsavel_membro_id, :descricao, :status, :visibilidade, :criado_por_user_id, NOW())'
        );
        $stmt->execute(self::bindings($data) + ['criado_por_user_id' => $criadoPorUserId]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE agenda_eventos SET
                titulo = :titulo, tipo = :tipo, data = :data, hora_inicio = :hora_inicio,
                hora_fim = :hora_fim, local = :local, responsavel_membro_id = :responsavel_membro_id,
                descricao = :descricao, status = :status, visibilidade = :visibilidade
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM agenda_eventos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function dataHoraFormatada(): string
    {
        $formatada = (new \DateTimeImmutable($this->data))->format('d/m/Y');

        if ($this->horaInicio !== null) {
            $formatada .= ' as ' . substr($this->horaInicio, 0, 5);

            if ($this->horaFim !== null) {
                $formatada .= ' - ' . substr($this->horaFim, 0, 5);
            }
        }

        return $formatada;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $responsavelId = trim((string) ($data['responsavel_membro_id'] ?? ''));
        $horaInicio = trim((string) ($data['hora_inicio'] ?? ''));
        $horaFim = trim((string) ($data['hora_fim'] ?? ''));

        return [
            'titulo' => trim((string) $data['titulo']),
            'tipo' => in_array($data['tipo'] ?? null, ['evento', 'reuniao', 'reserva', 'outro'], true) ? $data['tipo'] : 'evento',
            'data' => (string) $data['data'],
            'hora_inicio' => $horaInicio === '' ? null : $horaInicio,
            'hora_fim' => $horaFim === '' ? null : $horaFim,
            'local' => self::nullable($data['local'] ?? null),
            'responsavel_membro_id' => $responsavelId === '' ? null : (int) $responsavelId,
            'descricao' => self::nullable($data['descricao'] ?? null),
            'status' => in_array($data['status'] ?? null, ['agendado', 'realizado', 'cancelado'], true)
                ? $data['status']
                : 'agendado',
            'visibilidade' => ($data['visibilidade'] ?? null) === 'privado' ? 'privado' : 'publico',
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            titulo: (string) $row['titulo'],
            tipo: (string) $row['tipo'],
            data: (string) $row['data'],
            horaInicio: $row['hora_inicio'],
            horaFim: $row['hora_fim'],
            local: $row['local'],
            responsavelMembroId: $row['responsavel_membro_id'] !== null ? (int) $row['responsavel_membro_id'] : null,
            responsavelNome: $row['responsavel_nome'] ?? null,
            descricao: $row['descricao'],
            status: (string) $row['status'],
            visibilidade: (string) ($row['visibilidade'] ?? 'publico'),
            criadoPorUserId: $row['criado_por_user_id'] !== null ? (int) $row['criado_por_user_id'] : null,
            createdAt: (string) $row['created_at'],
        );
    }
}
