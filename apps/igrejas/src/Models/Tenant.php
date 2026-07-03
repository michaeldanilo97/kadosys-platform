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
        string $subdominio,
        string $dbName,
        string $dbUser,
        string $dbPassword,
    ): int {
        $stmt = Database::central()->prepare(
            'INSERT INTO plataforma_tenants
                (slug, nome_igreja, plano, subdominio, db_name, db_user, db_password, status)
             VALUES
                (:slug, :nome_igreja, :plano, :subdominio, :db_name, :db_user, :db_password, "provisionando")'
        );
        $stmt->execute([
            'slug' => $slug,
            'nome_igreja' => $nomeIgreja,
            'plano' => $plano,
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
        $stmt = Database::central()->prepare(
            'SELECT id, slug, nome_igreja, plano, subdominio, db_name, db_user, db_password, status
             FROM plataforma_tenants WHERE subdominio = :subdominio LIMIT 1'
        );
        $stmt->execute(['subdominio' => $subdominio]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new self(
            id: (int) $row['id'],
            slug: $row['slug'],
            nomeIgreja: $row['nome_igreja'],
            plano: $row['plano'],
            subdominio: $row['subdominio'],
            dbName: $row['db_name'],
            dbUser: $row['db_user'],
            dbPassword: $row['db_password'],
            status: $row['status'],
        );
    }
}
