<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Diretorio central de igrejas provisionadas automaticamente (ver
 * Igrejas\Controllers\CadastroController). Cada linha representa um
 * banco de dados proprio, isolado, criado via API do cPanel - nao ha
 * multi-tenant de verdade (dados compartilhados num banco so); isso e
 * so o "indice" usado pra resolver, a partir do subdominio, qual banco
 * uma requisicao deve usar.
 */
final class Tenant
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $nomeIgreja,
        public readonly string $plano,
        public readonly string $metodoPagamento,
        public readonly string $subdominio,
        public readonly string $dbName,
        public readonly string $dbUser,
        public readonly string $dbPassword,
        public readonly string $status,
    ) {
    }

    public static function slugDisponivel(string $slug): bool
    {
        $stmt = Database::central()->prepare('SELECT 1 FROM plataforma_tenants WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() === false;
    }

    public static function criar(
        string $slug,
        string $nomeIgreja,
        string $plano,
        string $metodoPagamento,
        string $subdominio,
        string $dbName,
        string $dbUser,
        string $dbPassword,
    ): int {
        $stmt = Database::central()->prepare(
            'INSERT INTO plataforma_tenants
                (slug, nome_igreja, plano, metodo_pagamento, subdominio, db_name, db_user, db_password, status)
             VALUES
                (:slug, :nome_igreja, :plano, :metodo_pagamento, :subdominio, :db_name, :db_user, :db_password, "provisionando")'
        );
        $stmt->execute([
            'slug' => $slug,
            'nome_igreja' => $nomeIgreja,
            'plano' => $plano,
            'metodo_pagamento' => $metodoPagamento,
            'subdominio' => $subdominio,
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_password' => $dbPassword,
        ]);

        return (int) Database::central()->lastInsertId();
    }

    public static function marcarAtivo(int $id): void
    {
        $stmt = Database::central()->prepare('UPDATE plataforma_tenants SET status = "ativo" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function buscarPorSubdominio(string $subdominio): ?self
    {
        $stmt = Database::central()->prepare(self::SELECT_BASE . ' WHERE subdominio = :subdominio LIMIT 1');
        $stmt->execute(['subdominio' => $subdominio]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    public static function buscarPorId(int $id): ?self
    {
        $stmt = Database::central()->prepare(self::SELECT_BASE . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    /**
     * @return array<int, self>
     */
    public static function ativosComPagamentoPix(): array
    {
        $stmt = Database::central()->prepare(
            self::SELECT_BASE . " WHERE status = 'ativo' AND metodo_pagamento = 'pix'"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    private const SELECT_BASE = 'SELECT id, slug, nome_igreja, plano, metodo_pagamento, subdominio,
            db_name, db_user, db_password, status
        FROM plataforma_tenants';

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            slug: $row['slug'],
            nomeIgreja: $row['nome_igreja'],
            plano: $row['plano'],
            metodoPagamento: $row['metodo_pagamento'],
            subdominio: $row['subdominio'],
            dbName: $row['db_name'],
            dbUser: $row['db_user'],
            dbPassword: $row['db_password'],
            status: $row['status'],
        );
    }
}
