<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Periodo em que um profissional NAO atende, alem do expediente normal
 * (Profissional::$diasAtendimento / horarioInicio / horarioFim) -
 * bloqueio manual pontual (reuniao, compromisso), ferias ou folga.
 * Sempre respeitado pelo calculo de horarios disponiveis do
 * agendamento publico e validado ao criar/editar um agendamento pelo
 * painel (ver Barbearias\Controllers\AgendamentoPublicoController e
 * AgendamentoController).
 */
final class BloqueioAgenda
{
    public const TIPO_BLOQUEIO = 'bloqueio';
    public const TIPO_FERIAS = 'ferias';
    public const TIPO_FOLGA = 'folga';

    public const TIPOS = [self::TIPO_BLOQUEIO, self::TIPO_FERIAS, self::TIPO_FOLGA];

    private const SELECT_COLUNAS = 'b.id, b.barbearia_id, b.profissional_id, b.data_inicio, b.data_fim,
        b.motivo, b.tipo, b.created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly int $profissionalId,
        public readonly string $dataInicio,
        public readonly string $dataFim,
        public readonly ?string $motivo,
        public readonly string $tipo,
        public readonly ?string $profissionalNome = null,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM bloqueios_agenda b WHERE b.id = :id AND b.barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Bloqueios de toda a equipe que ainda nao terminaram - usado na
     * listagem do painel.
     *
     * @return array<int, self>
     */
    public static function futuros(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ', p.nome AS profissional_nome
             FROM bloqueios_agenda b
             INNER JOIN profissionais p ON p.id = b.profissional_id
             WHERE b.barbearia_id = :barbearia_id AND b.data_fim >= NOW()
             ORDER BY b.data_inicio ASC'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Bloqueios de um profissional que colidem com um periodo - usado
     * tanto pelo calculo de horarios disponiveis (periodo = o dia
     * inteiro) quanto pela validacao de um agendamento manual do painel
     * (periodo = so o horario do servico escolhido).
     *
     * @return array<int, self>
     */
    public static function doProfissionalNoPeriodo(int $profissionalId, string $inicio, string $fim): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM bloqueios_agenda b
             WHERE b.profissional_id = :profissional_id AND b.data_inicio < :fim AND b.data_fim > :inicio
             ORDER BY b.data_inicio ASC'
        );
        $stmt->execute(['profissional_id' => $profissionalId, 'inicio' => $inicio, 'fim' => $fim]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function create(
        int $barbeariaId,
        int $profissionalId,
        string $dataInicio,
        string $dataFim,
        ?string $motivo,
        string $tipo,
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO bloqueios_agenda (barbearia_id, profissional_id, data_inicio, data_fim, motivo, tipo, created_at)
             VALUES (:barbearia_id, :profissional_id, :data_inicio, :data_fim, :motivo, :tipo, NOW())'
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'profissional_id' => $profissionalId,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'motivo' => self::nullable($motivo),
            'tipo' => in_array($tipo, self::TIPOS, true) ? $tipo : self::TIPO_BLOQUEIO,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function delete(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM bloqueios_agenda WHERE id = :id AND barbearia_id = :barbearia_id');
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
            profissionalId: (int) $row['profissional_id'],
            dataInicio: (string) $row['data_inicio'],
            dataFim: (string) $row['data_fim'],
            motivo: $row['motivo'] ?? null,
            tipo: (string) $row['tipo'],
            profissionalNome: isset($row['profissional_nome']) ? (string) $row['profissional_nome'] : null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
