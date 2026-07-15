<?php

declare(strict_types=1);

namespace Barbearias\Models;

use Barbearias\Core\Database;

/**
 * Um registro por atendimento consumido de uma assinatura de cliente
 * (ver Barbearias\Models\AssinaturaCliente e
 * Barbearias\Controllers\AgendamentoController::usarAssinatura).
 */
final class AssinaturaConsumo
{
    public static function create(int $assinaturaId, int $agendamentoId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO assinatura_consumos (assinatura_id, agendamento_id, data_consumo, created_at)
             VALUES (:assinatura_id, :agendamento_id, CURDATE(), NOW())'
        );
        $stmt->execute(['assinatura_id' => $assinaturaId, 'agendamento_id' => $agendamentoId]);
    }

    /**
     * Quantos atendimentos ja foram consumidos dessa assinatura desde
     * o inicio do ciclo mensal atual (inclusive).
     */
    public static function contarNoCiclo(int $assinaturaId, \DateTimeImmutable $inicioCiclo): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM assinatura_consumos WHERE assinatura_id = :assinatura_id AND data_consumo >= :inicio_ciclo'
        );
        $stmt->execute(['assinatura_id' => $assinaturaId, 'inicio_ciclo' => $inicioCiclo->format('Y-m-d')]);

        return (int) $stmt->fetchColumn();
    }

    public static function existeParaAgendamento(int $agendamentoId): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM assinatura_consumos WHERE agendamento_id = :agendamento_id LIMIT 1');
        $stmt->execute(['agendamento_id' => $agendamentoId]);

        return $stmt->fetch() !== false;
    }
}
