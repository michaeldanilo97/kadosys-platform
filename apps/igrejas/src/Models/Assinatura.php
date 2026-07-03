<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Historico de assinaturas recorrentes via Mercado Pago (ver
 * Igrejas\Core\MercadoPagoClient e Igrejas\Controllers\AssinaturaController).
 * Uma linha nova a cada vez que o admin inicia uma assinatura - nao e
 * singleton como configuracoes_igreja, ja que a igreja pode tentar de
 * novo ou trocar de plano.
 */
final class Assinatura
{
    public function __construct(
        public readonly int $id,
        public readonly string $plano,
        public readonly ?string $mpPreapprovalId,
        public readonly ?string $mpPayerEmail,
        public readonly string $status,
    ) {
    }

    public static function criar(string $plano, string $mpPreapprovalId, string $mpPayerEmail): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO assinaturas (plano, mp_preapproval_id, mp_payer_email, status)
             VALUES (:plano, :preapproval_id, :payer_email, "pendente")'
        );
        $stmt->execute([
            'plano' => $plano,
            'preapproval_id' => $mpPreapprovalId,
            'payer_email' => $mpPayerEmail,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function buscarPorPreapprovalId(string $mpPreapprovalId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, plano, mp_preapproval_id, mp_payer_email, status
             FROM assinaturas WHERE mp_preapproval_id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $mpPreapprovalId]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    public static function ultima(): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, plano, mp_preapproval_id, mp_payer_email, status
             FROM assinaturas ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    public static function atualizarStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare('UPDATE assinaturas SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Registra um evento bruto (notificacao de webhook recebida, erro de
     * validacao, resposta da API ao criar uma assinatura, etc.) para
     * diagnostico - serve como log de pagamento sem depender de arquivo
     * em hospedagem compartilhada.
     */
    public static function registrarEvento(?int $assinaturaId, string $tipo, string $payload): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO assinatura_eventos (assinatura_id, tipo, payload) VALUES (:assinatura_id, :tipo, :payload)'
        );
        $stmt->execute([
            'assinatura_id' => $assinaturaId,
            'tipo' => $tipo,
            'payload' => mb_substr($payload, 0, 60000),
        ]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            plano: $row['plano'],
            mpPreapprovalId: $row['mp_preapproval_id'],
            mpPayerEmail: $row['mp_payer_email'],
            status: $row['status'],
        );
    }
}
