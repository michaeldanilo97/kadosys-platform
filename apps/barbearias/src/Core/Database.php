<?php

declare(strict_types=1);

namespace Barbearias\Core;

use PDO;
use PDOException;

/**
 * Acesso a conexao PDO com o MySQL.
 *
 * Diferente do KADOSYS Igrejas (um banco por tenant, com troca de
 * credenciais em tempo de requisicao), o Barbearias usa um UNICO banco
 * compartilhado - entao esta classe e so um singleton PDO simples, sem
 * variantes de conexao. O isolamento entre barbearias acontece na
 * camada de query (WHERE barbearia_id = ...), nao na camada de conexao.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';

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
                'Nao foi possivel conectar ao banco de dados. Verifique config/database.php.',
                previous: $exception
            );
        }

        return self::$connection;
    }
}
