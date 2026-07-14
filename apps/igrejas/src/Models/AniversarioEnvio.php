<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Controle de envio do e-mail automatico de parabens por aniversario
 * (ver cron/enviar_parabens_aniversario.php) - garante que cada membro
 * recebe no maximo um e-mail por ano, mesmo se o cron rodar mais de
 * uma vez no mesmo dia.
 */
final class AniversarioEnvio
{
    public static function jaEnviado(int $membroId, int $ano): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM aniversario_envios WHERE membro_id = :membro_id AND ano = :ano LIMIT 1'
        );
        $stmt->execute(['membro_id' => $membroId, 'ano' => $ano]);

        return $stmt->fetchColumn() !== false;
    }

    public static function registrar(int $membroId, int $ano): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO aniversario_envios (membro_id, ano, enviado_em) VALUES (:membro_id, :ano, NOW())'
        );
        $stmt->execute(['membro_id' => $membroId, 'ano' => $ano]);
    }
}
