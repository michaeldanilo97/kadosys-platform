<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Configuracoes gerais da igreja (linha unica, id = 1).
 * Por enquanto guarda apenas a logo usada no fadeout da projecao de
 * video; podera ser expandida conforme o modulo Configuracoes crescer.
 */
final class ConfiguracaoIgreja
{
    public function __construct(
        public readonly ?string $nomeIgreja,
        public readonly ?string $logoPath,
    ) {
    }

    public static function atual(): self
    {
        $stmt = Database::connection()->prepare(
            'SELECT nome_igreja, logo_path FROM configuracoes_igreja WHERE id = 1 LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return new self(
            nomeIgreja: $row['nome_igreja'] ?? null,
            logoPath: $row['logo_path'] ?? null,
        );
    }

    public static function atualizarLogo(string $logoPath): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO configuracoes_igreja (id, logo_path)
             VALUES (1, :logo_path)
             ON DUPLICATE KEY UPDATE logo_path = VALUES(logo_path)'
        );
        $stmt->execute(['logo_path' => $logoPath]);
    }

    public static function removerLogo(): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE configuracoes_igreja SET logo_path = NULL WHERE id = 1'
        );
        $stmt->execute();
    }
}
