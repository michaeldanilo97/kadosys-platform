<?php

declare(strict_types=1);

namespace Superadmin\Core;

use PDO;
use PDOException;

/**
 * Conexao PDO com o banco compartilhado do KADOSYS Barbearias (tabela
 * barbearias, barbearia_avisos - ver config/database_barbearias.php).
 */
final class DatabaseBarbearias
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require dirname(__DIR__, 2) . '/config/database_barbearias.php';

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new \RuntimeException(
                'Nao foi possivel conectar ao banco do Barbearias. Verifique config/database_barbearias.php.',
                previous: $exception
            );
        }

        return self::$connection;
    }
}
