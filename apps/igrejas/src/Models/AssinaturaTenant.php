<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Ponte central entre um preapproval_id (assinatura via cartao,
 * Checkout Pro) e o tenant dono dela - so existe pra assinaturas
 * iniciadas de dentro do subdominio de um tenant ja provisionado (ver
 * AssinaturaController::iniciar()).
 *
 * Resolve uma lacuna real: a tabela "assinaturas" (ver Igrejas\Models\
 * Assinatura) vive no banco isolado de CADA igreja, entao um registro
 * criado por uma igreja de subdominio fica so no banco dela. O webhook
 * do Mercado Pago, porem, sempre chega pela URL central (nunca por um
 * subdominio especifico) - sem essa ponte, ele nunca conseguiria achar
 * a qual tenant aquele preapproval pertence pra atualizar o plano.
 *
 * Vive no banco central (junto com plataforma_tenants/plataforma_faturas),
 * mesmo padrao ja usado pro Pix.
 */
final class AssinaturaTenant
{
    public function __construct(
        public readonly int $id,
        public readonly int $tenantId,
        public readonly string $plano,
        public readonly string $mpPreapprovalId,
        public readonly string $status,
    ) {
    }

    public static function registrar(int $tenantId, string $plano, string $mpPreapprovalId): int
    {
        $stmt = Database::central()->prepare(
            'INSERT INTO plataforma_assinaturas (tenant_id, plano, mp_preapproval_id, status)
             VALUES (:tenant_id, :plano, :mp_preapproval_id, "pendente")'
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'plano' => $plano,
            'mp_preapproval_id' => $mpPreapprovalId,
        ]);

        return (int) Database::central()->lastInsertId();
    }

    public static function buscarPorPreapprovalId(string $mpPreapprovalId): ?self
    {
        $stmt = Database::central()->prepare(self::SELECT_BASE . ' WHERE mp_preapproval_id = :id LIMIT 1');
        $stmt->execute(['id' => $mpPreapprovalId]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    /**
     * Assinatura de cartao autorizada mais recente de um tenant - usada
     * pra atualizar o valor cobrado no proximo ciclo quando um
     * upgrade/downgrade entra em vigor (ver
     * Igrejas\Core\MercadoPagoClient::atualizarAssinatura()), sem
     * precisar cancelar e recriar a assinatura no Checkout Pro.
     */
    public static function ativaDoTenant(int $tenantId): ?self
    {
        $stmt = Database::central()->prepare(
            self::SELECT_BASE . " WHERE tenant_id = :tenant_id AND status = 'autorizada'
                ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    /**
     * Assinatura de cartao mais recente de um tenant, seja qual for o
     * status - diferente de ativaDoTenant() (so 'autorizada'), usada pela
     * tela de Faturas pra mostrar o status atual da assinatura recorrente
     * mesmo quando ainda pendente/pausada/cancelada.
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

    public static function atualizarStatus(int $id, string $status): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_assinaturas SET status = :status WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    private const SELECT_BASE = 'SELECT id, tenant_id, plano, mp_preapproval_id, status FROM plataforma_assinaturas';

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            tenantId: (int) $row['tenant_id'],
            plano: $row['plano'],
            mpPreapprovalId: $row['mp_preapproval_id'],
            status: $row['status'],
        );
    }
}
