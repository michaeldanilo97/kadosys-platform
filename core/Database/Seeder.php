<?php

declare(strict_types=1);

namespace Kadosys\Core\Database;

/**
 * Seeder
 *
 * Classe base abstrata para seeders da plataforma. Seeders concretos
 * (em database/seeders/) populam tabelas com dados iniciais ou de teste.
 *
 * Exemplo:
 *   final class TenantSeeder extends Seeder
 *   {
 *       public function run(): void
 *       {
 *           (new QueryBuilder('tenants'))->insert([
 *               'nome' => 'Igreja Exemplo',
 *           ]);
 *       }
 *   }
 */
abstract class Seeder
{
    abstract public function run(): void;

    protected function connection(): Connection
    {
        return Connection::getInstance();
    }
}
