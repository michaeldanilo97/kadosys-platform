<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Aviso do dono da plataforma (KADOSYS) para os restaurantes
 * cadastrados, mostrado no sino de notificacoes do painel (ver
 * resources/views/layouts/dashboard.php). Publicado pelo Super Admin
 * (apps/superadmin), nunca por dentro deste app - so leitura aqui,
 * mesma semantica de Barbearias\Models\BarbeariaAviso.
 */
final class RestauranteAviso
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
            'SELECT * FROM restaurante_avisos WHERE ativo = 1 ORDER BY id DESC LIMIT 1'
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
