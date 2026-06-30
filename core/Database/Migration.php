<?php

declare(strict_types=1);

namespace Kadosys\Core\Database;

/**
 * Migration
 *
 * Classe base abstrata para migrations da plataforma. Toda migration
 * concreta (em database/migrations/) deve estender esta classe e
 * implementar up() e down(), executando SQL diretamente via Connection.
 *
 * Exemplo:
 *   final class CreateMembersTable extends Migration
 *   {
 *       public function up(): void
 *       {
 *           $this->connection()->statement("
 *               CREATE TABLE membros (
 *                   id INT AUTO_INCREMENT PRIMARY KEY,
 *                   tenant_id INT NOT NULL,
 *                   nome VARCHAR(255) NOT NULL,
 *                   created_at DATETIME NOT NULL
 *               )
 *           ");
 *       }
 *
 *       public function down(): void
 *       {
 *           $this->connection()->statement('DROP TABLE IF EXISTS membros');
 *       }
 *   }
 */
abstract class Migration
{
    /**
     * Aplica a migration (cria/altera estrutura no banco).
     */
    abstract public function up(): void;

    /**
     * Revertes a migration (desfaz a alteracao).
     */
    abstract public function down(): void;

    protected function connection(): Connection
    {
        return Connection::getInstance();
    }
}
