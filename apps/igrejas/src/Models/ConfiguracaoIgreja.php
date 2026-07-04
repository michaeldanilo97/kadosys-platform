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
        public readonly string $plano = Plano::ESSENCIAL,
        public readonly bool $cadastroMembrosHabilitado = false,
    ) {
    }

    public static function atual(): self
    {
        $stmt = Database::connection()->prepare(
            'SELECT nome_igreja, logo_path, plano, cadastro_membros_habilitado FROM configuracoes_igreja WHERE id = 1 LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return new self(
            nomeIgreja: $row['nome_igreja'] ?? null,
            logoPath: $row['logo_path'] ?? null,
            plano: $row['plano'] ?? Plano::ESSENCIAL,
            cadastroMembrosHabilitado: (bool) ($row['cadastro_membros_habilitado'] ?? false),
        );
    }

    public static function atualizarPlano(string $plano): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO configuracoes_igreja (id, plano)
             VALUES (1, :plano)
             ON DUPLICATE KEY UPDATE plano = VALUES(plano)'
        );
        $stmt->execute(['plano' => $plano]);
    }

    public static function atualizarCadastroMembros(bool $habilitado): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO configuracoes_igreja (id, cadastro_membros_habilitado)
             VALUES (1, :habilitado)
             ON DUPLICATE KEY UPDATE cadastro_membros_habilitado = VALUES(cadastro_membros_habilitado)'
        );
        $stmt->execute(['habilitado' => $habilitado ? 1 : 0]);
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
