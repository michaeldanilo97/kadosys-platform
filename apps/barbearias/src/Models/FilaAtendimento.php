<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Fila de atendimento por ordem de chegada - alternativa ao Agendamento
 * (ver Barbearias\Models\Barbearia::usaFila()). Uma linha por pessoa,
 * "entrou_em" define a ordem (FIFO). Sem cadastro de cliente
 * obrigatorio - guarda nome/telefone direto na propria linha.
 */
final class FilaAtendimento
{
    public const STATUS_AGUARDANDO = 'aguardando';
    public const STATUS_EM_ATENDIMENTO = 'em_atendimento';
    public const STATUS_ATENDIDO = 'atendido';
    public const STATUS_CANCELADO = 'cancelado';

    /**
     * Duracao media (minutos) usada pra estimar o tempo de espera
     * quando a barbearia nao tem nenhum servico cadastrado ainda.
     */
    private const DURACAO_PADRAO_MINUTOS = 30;

    private const SELECT_COLUNAS = 'id, barbearia_id, profissional_id, nome, telefone, status,
        entrou_em, chamado_em, atendido_em';

    public function __construct(
        public readonly int $id,
        public readonly int $barbeariaId,
        public readonly ?int $profissionalId,
        public readonly string $nome,
        public readonly ?string $telefone,
        public readonly string $status,
        public readonly string $entrouEm,
        public readonly ?string $chamadoEm,
        public readonly ?string $atendidoEm,
    ) {
    }

    public static function find(int $id, int $barbeariaId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM fila_atendimento WHERE id = :id AND barbearia_id = :barbearia_id LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Quem esta esperando ou sendo atendido agora, em ordem de
     * chegada - usado tanto pelo painel (equipe) quanto pela pagina
     * publica de status da fila.
     *
     * @return array<int, self>
     */
    public static function ativos(int $barbeariaId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT " . self::SELECT_COLUNAS . " FROM fila_atendimento
             WHERE barbearia_id = :barbearia_id AND status IN ('aguardando', 'em_atendimento')
             ORDER BY entrou_em ASC"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Quantas pessoas estao so aguardando (sem contar quem ja esta
     * sendo atendido) - usado pra "posicao na fila" e pro texto
     * publico de contagem.
     */
    public static function contarAguardando(int $barbeariaId): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM fila_atendimento WHERE barbearia_id = :barbearia_id AND status = 'aguardando'"
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Estimativa simples de espera: posicao na fila (gente aguardando
     * NA FRENTE) multiplicada pela duracao media dos servicos ativos
     * da barbearia - sem cron, sem historico, recalculada na hora.
     */
    public static function estimarEsperaMinutos(int $barbeariaId, int $pessoasNaFrente): int
    {
        return $pessoasNaFrente * self::duracaoMediaMinutos($barbeariaId);
    }

    private static function duracaoMediaMinutos(int $barbeariaId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT AVG(duracao_minutos) FROM servicos WHERE barbearia_id = :barbearia_id AND ativo = 1'
        );
        $stmt->execute(['barbearia_id' => $barbeariaId]);
        $media = $stmt->fetchColumn();

        return $media !== false && $media !== null ? (int) round((float) $media) : self::DURACAO_PADRAO_MINUTOS;
    }

    public static function entrar(int $barbeariaId, string $nome, ?string $telefone, ?int $profissionalId = null): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO fila_atendimento (barbearia_id, profissional_id, nome, telefone, status, entrou_em)
             VALUES (:barbearia_id, :profissional_id, :nome, :telefone, 'aguardando', NOW())"
        );
        $stmt->execute([
            'barbearia_id' => $barbeariaId,
            'profissional_id' => $profissionalId,
            'nome' => trim($nome),
            'telefone' => self::nullable($telefone),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function chamar(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE fila_atendimento SET status = 'em_atendimento', chamado_em = NOW()
             WHERE id = :id AND barbearia_id = :barbearia_id AND status = 'aguardando'"
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    public static function concluir(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE fila_atendimento SET status = 'atendido', atendido_em = NOW()
             WHERE id = :id AND barbearia_id = :barbearia_id"
        );
        $stmt->execute(['id' => $id, 'barbearia_id' => $barbeariaId]);
    }

    public static function cancelar(int $id, int $barbeariaId): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE fila_atendimento SET status = 'cancelado' WHERE id = :id AND barbearia_id = :barbearia_id"
        );
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
            profissionalId: $row['profissional_id'] !== null ? (int) $row['profissional_id'] : null,
            nome: (string) $row['nome'],
            telefone: $row['telefone'] ?? null,
            status: (string) $row['status'],
            entrouEm: (string) $row['entrou_em'],
            chamadoEm: $row['chamado_em'] ?? null,
            atendidoEm: $row['atendido_em'] ?? null,
        );
    }
}
