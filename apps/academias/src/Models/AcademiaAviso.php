<?php

declare(strict_types=1);

namespace Academias\Models;

use Academias\Core\Database;

/**
 * Aviso do dono da plataforma (KADOSYS) para as academias cadastradas,
 * mostrado no sino de notificacoes do painel (ver
 * resources/views/layouts/dashboard.php). Publicado pelo Super Admin
 * (apps/superadmin), nunca por dentro deste app - mesma semantica de
 * Igrejas\Models\PlataformaAviso: so um aviso fica ativo por vez
 * (publicar um novo encerra o anterior automaticamente).
 */
final class AcademiaAviso
{
    public function __construct(
        public readonly int $id,
        public readonly string $mensagem,
        public readonly bool $ativo,
        public readonly string $createdAt,
    ) {
    }

    public static function ativo(): ?self
    {
        $stmt = Database::connection()->query(
            'SELECT * FROM academia_avisos WHERE ativo = 1 ORDER BY id DESC LIMIT 1'
        );
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            mensagem: (string) $row['mensagem'],
            ativo: (bool) $row['ativo'],
            createdAt: (string) $row['created_at'],
        );
    }
}
