<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Fatura de renovacao mensal via Pix (ver cron/gerar_faturas_pix.php) -
 * so existe pra tenants com metodo_pagamento = "pix". Quem paga por
 * cartao renova sozinho via preapproval, sem gerar linha aqui.
 *
 * Vive no banco central (junto com plataforma_tenants), nao no banco de
 * cada igreja - assim o webhook consegue achar de qual igreja e um
 * pagamento com uma unica consulta, sem precisar varrer o banco de cada
 * tenant procurando.
 */
final class FaturaPix
{
    public const TIPO_RENOVACAO = 'renovacao';
    public const TIPO_UPGRADE_PROPORCIONAL = 'upgrade_proporcional';

    public function __construct(
        public readonly int $id,
        public readonly int $tenantId,
        public readonly string $plano,
        public readonly string $tipo,
        public readonly float $valor,
        public readonly ?string $mpPaymentId,
        public readonly ?string $pixQrCode,
        public readonly ?string $pixQrCodeBase64,
        public readonly string $status,
        public readonly string $vencimento,
        public readonly ?string $pagoEm,
    ) {
    }

    public static function criar(
        int $tenantId,
        string $plano,
        float $valor,
        string $mpPaymentId,
        string $pixQrCode,
        string $pixQrCodeBase64,
        \DateTimeImmutable $vencimento,
        string $tipo = self::TIPO_RENOVACAO,
    ): int {
        $stmt = Database::central()->prepare(
            'INSERT INTO plataforma_faturas
                (tenant_id, plano, tipo, valor, mp_payment_id, pix_qr_code, pix_qr_code_base64, status, vencimento)
             VALUES
                (:tenant_id, :plano, :tipo, :valor, :mp_payment_id, :pix_qr_code, :pix_qr_code_base64, "pendente", :vencimento)'
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'plano' => $plano,
            'tipo' => $tipo,
            'valor' => $valor,
            'mp_payment_id' => $mpPaymentId,
            'pix_qr_code' => $pixQrCode,
            'pix_qr_code_base64' => $pixQrCodeBase64,
            'vencimento' => $vencimento->format('Y-m-d H:i:s'),
        ]);

        return (int) Database::central()->lastInsertId();
    }

    public static function buscarPorPaymentId(string $mpPaymentId): ?self
    {
        $stmt = Database::central()->prepare(self::SELECT_BASE . ' WHERE mp_payment_id = :id LIMIT 1');
        $stmt->execute(['id' => $mpPaymentId]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    /**
     * Ultima fatura (pendente ou paga) de um tenant - usada pelo cron
     * pra decidir se ja e hora de gerar a proxima, e pelo painel pra
     * mostrar o aviso de vencimento.
     */
    public static function ultimaDoTenant(int $tenantId): ?self
    {
        $stmt = Database::central()->prepare(
            self::SELECT_BASE . ' WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    /**
     * Ultima fatura PAGA de um tenant - representa o ciclo/plano
     * atualmente em vigor (com o vencimento de quando essa cobranca
     * cobre ate). Usada pelo painel pra diferenciar "aviso de renovacao
     * do plano atual" de "Pix pendente de upgrade pra outro plano" (ver
     * layouts/dashboard.php) - nesses dois casos, ultimaDoTenant()
     * sozinha nao basta porque pode ser uma fatura pendente de um plano
     * diferente do que esta ativo agora.
     */
    public static function ultimaPagaDoTenant(int $tenantId): ?self
    {
        $stmt = Database::central()->prepare(
            self::SELECT_BASE . " WHERE tenant_id = :tenant_id AND status = 'paga' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    public static function marcarPaga(int $id): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_faturas SET status = "paga", pago_em = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function marcarExpiradasVencidas(): void
    {
        Database::central()->exec(
            "UPDATE plataforma_faturas SET status = 'expirada'
             WHERE status = 'pendente' AND vencimento < NOW()"
        );
    }

    private const SELECT_BASE = 'SELECT id, tenant_id, plano, tipo, valor, mp_payment_id, pix_qr_code,
            pix_qr_code_base64, status, vencimento, pago_em
        FROM plataforma_faturas';

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            tenantId: (int) $row['tenant_id'],
            plano: $row['plano'],
            tipo: $row['tipo'],
            valor: (float) $row['valor'],
            mpPaymentId: $row['mp_payment_id'],
            pixQrCode: $row['pix_qr_code'],
            pixQrCodeBase64: $row['pix_qr_code_base64'],
            status: $row['status'],
            vencimento: $row['vencimento'],
            pagoEm: $row['pago_em'],
        );
    }
}
