<?php

declare(strict_types=1);

namespace Kadosys\Core\Database;

use Kadosys\Core\Core\Config;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Connection
 *
 * Encapsula a conexão PDO com o banco de dados como Singleton.
 */
final class Connection
{
    private static ?Connection $instance = null;

    private PDO $pdo;

    private function __construct()
    {
        $driver = (string) Config::get('database.connection', 'mysql');
        $host = (string) Config::get('database.host', 'localhost');
        $port = (string) Config::get('database.port', '3306');
        $database = (string) Config::get('database.database', 'kadosys1_kadosys');
        $username = (string) Config::get('database.username', 'kadosys1_kadosys');
        $password = (string) Config::get('database.password', '@Michael011');
        $charset = (string) Config::get('database.charset', 'utf8mb4');

        $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";

        // DEBUG
        echo "=====================================\n";
        echo "DSN: {$dsn}\n";
        echo "USER: {$username}\n";
        echo "DATABASE: {$database}\n";
        echo "=====================================\n";

        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            echo "✅ Conexão com banco realizada com sucesso!\n";

        } catch (PDOException $exception) {
            die(
                "❌ Erro ao conectar:\n\n" .
                $exception->getMessage() . "\n"
            );
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function statement(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt;
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function transaction(\Closure $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->rollBack();
            throw $exception;
        }
    }
}