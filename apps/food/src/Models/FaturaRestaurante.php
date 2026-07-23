<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Cobranca Pix avulsa de um restaurante - cobre tanto o PRIMEIRO
 * pagamento (cadastro novo) quanto cada renovacao mensal seguinte
 * (gerada por cron/gerar_faturas_pix.php), sem distincao entre as duas.
 * Cartao nao gera linha aqui - a cobranca recorrente e feita pelo
 * proprio Mercado Pago via preapproval (ver Restaurante::mpPreapprovalId).
 */
final class FaturaRestaurante
{
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_PAGA = 'paga';
    public const STATUS_EXPIRADA = 'expirada';
    public const STATUS_CANCELADA = 'cancelada';

    public const TIPO_RENOVACAO = 'renovacao';
    public const TIPO_UPGRADE_PROPORCIONAL = 'upgrade_proporcional';

    private const SELECT_COLUNAS = 'id, restaurante_id, plano, tipo, valor, mp_payment_id, pix_qr_code,
        pix_qr_code_base64, status, vencimento, pago_em, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly string $plano,
        public readonly string $tipo,
        public readonly float $valor,
        public readonly ?string $mpPaymentId,
        public readonly ?string $pixQrCode,
        public readonly ?string $pixQrCodeBase64,
        public readonly string $status,
        public readonly string $vencimento,
        public readonly ?string $pagoEm,
        public readonly ?string $createdAt = null,
    ) {
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            self::selectBase() . ' WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function buscarPorPaymentId(string $paymentId): ?self
    {
        $stmt = Database::connection()->prepare(
            self::selectBase() . ' WHERE mp_payment_id = :payment_id LIMIT 1'
        );
        $stmt->execute(['payment_id' => $paymentId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function ultimaDoRestaurante(int $restauranteId): ?self
    {
        $stmt = Database::connection()->prepare(
            self::selectBase() . ' WHERE restaurante_id = :restaurante_id ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return array<int, self>
     */
    public static function todasDoRestaurante(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            self::selectBase() . ' WHERE restaurante_id = :restaurante_id ORDER BY id DESC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Cria sempre com status 'pendente' - so vira 'paga' quando o
     * webhook do Mercado Pago confirmar (ver marcarPaga()).
     */
    public static function criar(
        int $restauranteId,
        string $plano,
        string $tipo,
        float $valor,
        string $mpPaymentId,
        ?string $pixQrCode,
        ?string $pixQrCodeBase64,
        \DateTimeImmutable $vencimento,
    ): int {
        $stmt = Database::connection()->prepare(
            "INSERT INTO restaurante_faturas
                (restaurante_id, plano, tipo, valor, mp_payment_id, pix_qr_code, pix_qr_code_base64, status, vencimento, created_at)
             VALUES
                (:restaurante_id, :plano, :tipo, :valor, :mp_payment_id, :pix_qr_code, :pix_qr_code_base64, 'pendente', :vencimento, NOW())"
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'plano' => $plano,
            'tipo' => $tipo,
            'valor' => $valor,
            'mp_payment_id' => $mpPaymentId,
            'pix_qr_code' => $pixQrCode,
            'pix_qr_code_base64' => $pixQrCodeBase64,
            'vencimento' => $vencimento->format('Y-m-d H:i:s'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function marcarPaga(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE restaurante_faturas SET status = 'paga', pago_em = NOW() WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    /**
     * Job de manutencao - roda antes do cron gerar novas faturas, pra
     * nao deixar cobrancas velhas e nunca pagas eternamente "pendente".
     */
    public static function marcarExpiradasVencidas(): void
    {
        Database::connection()->exec(
            "UPDATE restaurante_faturas SET status = 'expirada'
             WHERE status = 'pendente' AND vencimento < NOW()"
        );
    }

    private static function selectBase(): string
    {
        return 'SELECT ' . self::SELECT_COLUNAS . ' FROM restaurante_faturas';
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            plano: (string) $row['plano'],
            tipo: (string) $row['tipo'],
            valor: (float) $row['valor'],
            mpPaymentId: $row['mp_payment_id'] ?? null,
            pixQrCode: $row['pix_qr_code'] ?? null,
            pixQrCodeBase64: $row['pix_qr_code_base64'] ?? null,
            status: (string) $row['status'],
            vencimento: (string) $row['vencimento'],
            pagoEm: $row['pago_em'] ?? null,
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
