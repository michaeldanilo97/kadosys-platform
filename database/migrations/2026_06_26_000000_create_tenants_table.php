<?php

declare(strict_types=1);

use Kadosys\Core\Database\Migration;

/**
 * CreateTenantsTable
 *
 * Cria a tabela de tenants (igrejas, empresas, organizacoes),
 * base da arquitetura multi-tenant da plataforma.
 */
final class CreateTenantsTable extends Migration
{
    public function up(): void
    {
        $this->connection()->statement('
            CREATE TABLE IF NOT EXISTS tenants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                app VARCHAR(100) NOT NULL,
                ativo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(): void
    {
        $this->connection()->statement('DROP TABLE IF EXISTS tenants');
    }
}
