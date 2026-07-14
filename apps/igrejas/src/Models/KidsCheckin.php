<?php

declare(strict_types=1);

namespace Igrejas\Models;

use DateTimeImmutable;
use Igrejas\Core\Database;

/**
 * Model de KidsCheckin (modulo KADOSYS Kids): entrada/saida de cada
 * crianca no dia. Cada check-in gera um codigo de seguranca de 4
 * digitos (entregue ao responsavel na entrada) e, ao ser registrado,
 * concede XP/moedas e atualiza a sequencia de presenca da crianca (ver
 * KidsCrianca::concederPontos()) - tudo automatico, sem passo manual da
 * equipe.
 */
final class KidsCheckin
{
    /** Pontos concedidos automaticamente a cada check-in (ver registrar()). */
    private const XP_POR_CHECKIN = 10;
    private const MOEDAS_POR_CHECKIN = 5;

    /** Gap maximo (dias) entre dois check-ins pra contar como sequencia
     * mantida - cadencia semanal tipica de culto/EBD infantil, com
     * folga de alguns dias. */
    private const JANELA_SEQUENCIA_DIAS = 10;

    public function __construct(
        public readonly int $id,
        public readonly int $criancaId,
        public readonly ?string $criancaNome,
        public readonly ?string $criancaFotoPath,
        public readonly ?string $turmaNome,
        public readonly string $data,
        public readonly string $codigoSeguranca,
        public readonly string $horaEntrada,
        public readonly ?string $horaSaida,
        public readonly ?string $entreguePor,
        public readonly ?string $retiradoPor,
        public readonly ?string $observacoes,
    ) {
    }

    /**
     * Check-ins abertos hoje (ainda na sala, sem saida registrada) -
     * lista principal da tela de Check-in.
     *
     * @return array<int, self>
     */
    public static function abertosHoje(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT k.*, c.nome AS crianca_nome, c.foto_path AS crianca_foto_path, t.nome AS turma_nome
             FROM kids_checkins k
             INNER JOIN kids_criancas c ON c.id = k.crianca_id
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             WHERE k.data = CURDATE() AND k.hora_saida IS NULL
             ORDER BY k.hora_entrada ASC"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Check-ins ja encerrados hoje (crianca ja retirada) - historico do
     * dia, exibido abaixo da lista de quem ainda esta na sala.
     *
     * @return array<int, self>
     */
    public static function encerradosHoje(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT k.*, c.nome AS crianca_nome, c.foto_path AS crianca_foto_path, t.nome AS turma_nome
             FROM kids_checkins k
             INNER JOIN kids_criancas c ON c.id = k.crianca_id
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             WHERE k.data = CURDATE() AND k.hora_saida IS NOT NULL
             ORDER BY k.hora_saida DESC"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT k.*, c.nome AS crianca_nome, c.foto_path AS crianca_foto_path, t.nome AS turma_nome
             FROM kids_checkins k
             INNER JOIN kids_criancas c ON c.id = k.crianca_id
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             WHERE k.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function criancaComCheckinAbertoHoje(int $criancaId): bool
    {
        $stmt = Database::connection()->prepare(
            "SELECT 1 FROM kids_checkins
             WHERE crianca_id = :crianca_id AND data = CURDATE() AND hora_saida IS NULL
             LIMIT 1"
        );
        $stmt->execute(['crianca_id' => $criancaId]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Registra a entrada da crianca: gera o codigo de seguranca, grava
     * o check-in e concede XP/moedas + atualiza a sequencia de presenca
     * (mantida se o check-in anterior foi ha no maximo
     * JANELA_SEQUENCIA_DIAS dias, senao reinicia em 1).
     */
    public static function registrar(int $criancaId, ?string $entreguePor, ?int $registradoPorUserId): self
    {
        $codigo = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $sequencia = self::proximaSequencia($criancaId);

        $stmt = Database::connection()->prepare(
            'INSERT INTO kids_checkins
                (crianca_id, data, codigo_seguranca, hora_entrada, entregue_por, registrado_por_user_id, created_at)
             VALUES
                (:crianca_id, CURDATE(), :codigo_seguranca, NOW(), :entregue_por, :registrado_por_user_id, NOW())'
        );
        $stmt->execute([
            'crianca_id' => $criancaId,
            'codigo_seguranca' => $codigo,
            'entregue_por' => self::nullable($entreguePor),
            'registrado_por_user_id' => $registradoPorUserId,
        ]);

        $id = (int) Database::connection()->lastInsertId();

        KidsCrianca::concederPontos($criancaId, self::XP_POR_CHECKIN, self::MOEDAS_POR_CHECKIN, $sequencia);

        return self::find($id);
    }

    /**
     * Encerra o check-in (retirada da crianca) - so aceita se o codigo
     * de seguranca informado pelo responsavel confere, evitando que
     * qualquer pessoa retire uma crianca sem o codigo entregue na
     * entrada.
     */
    public static function encerrar(int $id, string $codigoInformado, ?string $retiradoPor, ?int $encerradoPorUserId): bool
    {
        $checkin = self::find($id);

        if ($checkin === null || $checkin->horaSaida !== null) {
            return false;
        }

        if (!hash_equals($checkin->codigoSeguranca, trim($codigoInformado))) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE kids_checkins
             SET hora_saida = NOW(), retirado_por = :retirado_por, encerrado_por_user_id = :encerrado_por_user_id
             WHERE id = :id'
        );
        $stmt->execute([
            'retirado_por' => self::nullable($retiradoPor),
            'encerrado_por_user_id' => $encerradoPorUserId,
            'id' => $id,
        ]);

        return true;
    }

    /**
     * Histórico recente de check-ins de uma criança - usado na aba de
     * presença do perfil (ver KidsCriancaController::show()).
     *
     * @return array<int, self>
     */
    public static function doCrianca(int $criancaId, int $limit = 10): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT k.*, c.nome AS crianca_nome, c.foto_path AS crianca_foto_path, t.nome AS turma_nome
             FROM kids_checkins k
             INNER JOIN kids_criancas c ON c.id = k.crianca_id
             LEFT JOIN kids_turmas t ON t.id = c.turma_id
             WHERE k.crianca_id = :crianca_id
             ORDER BY k.hora_entrada DESC
             LIMIT " . max(1, $limit)
        );
        $stmt->execute(['crianca_id' => $criancaId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function totalHoje(): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM kids_checkins WHERE data = CURDATE()');
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private static function proximaSequencia(int $criancaId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT MAX(data) FROM kids_checkins WHERE crianca_id = :crianca_id'
        );
        $stmt->execute(['crianca_id' => $criancaId]);
        $ultimaData = $stmt->fetchColumn();

        if ($ultimaData === false || $ultimaData === null) {
            return 1;
        }

        $crianca = KidsCrianca::find($criancaId);
        $sequenciaAtual = $crianca?->sequenciaDias ?? 0;

        $dias = (new DateTimeImmutable('today'))->diff(new DateTimeImmutable((string) $ultimaData))->days;

        return $dias <= self::JANELA_SEQUENCIA_DIAS ? $sequenciaAtual + 1 : 1;
    }

    private static function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            criancaId: (int) $row['crianca_id'],
            criancaNome: $row['crianca_nome'] ?? null,
            criancaFotoPath: $row['crianca_foto_path'] ?? null,
            turmaNome: $row['turma_nome'] ?? null,
            data: (string) $row['data'],
            codigoSeguranca: (string) $row['codigo_seguranca'],
            horaEntrada: (string) $row['hora_entrada'],
            horaSaida: $row['hora_saida'],
            entreguePor: $row['entregue_por'],
            retiradoPor: $row['retirado_por'],
            observacoes: $row['observacoes'],
        );
    }
}
