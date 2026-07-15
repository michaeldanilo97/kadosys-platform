<?php

declare(strict_types=1);

namespace Igrejas\Core;

use PDO;
use PDOException;

/**
 * Acesso a conexao PDO com o MySQL.
 *
 * Continua "um banco por igreja" (sem multi-tenant de dados
 * compartilhados) - a diferenca e que agora existem duas variantes de
 * conexao:
 *
 * - connection(): o banco "desta" igreja. Por padrao e sempre o de
 *   config/database.php, IGUAL a antes - mas se usarCredenciais() for
 *   chamado bem cedo na requisicao (ver public/index.php, resolucao de
 *   tenant por subdominio), passa a apontar pro banco daquela igreja
 *   especifica. Todos os Models existentes (Membro, Culto, User, etc.)
 *   usam essa conexao sem saber da diferenca.
 * - central(): SEMPRE o banco de config/database.php, nunca sobrescrito
 *   - usado so pelo registro central de igrejas (Igrejas\Models\Tenant e
 *   Igrejas\Models\Provisionamento), que por definicao mora nesse banco
 *   fixo independente de qual igreja esta sendo atendida.
 *
 * Se usarCredenciais() nunca for chamado (comportamento de qualquer
 * requisicao que nao seja de um subdominio de igreja provisionada),
 * connection() e central() sao a mesma coisa, e o sistema funciona
 * exatamente como antes desta mudanca.
 */
final class Database
{
    private static ?PDO $connection = null;
    private static ?PDO $central = null;

    /** @var array{database:string, username:string, password:string}|null */
    private static ?array $overrideCredenciais = null;

    /**
     * Define as credenciais do banco a usar em connection() pro resto
     * desta requisicao. Precisa ser chamado antes de qualquer consulta
     * (ver public/index.php) - depois que a primeira conexao e aberta,
     * trocar de banco no meio da requisicao seria uma fonte grave de
     * bug (dado de uma igreja vazando pra outra), entao isso e proibido
     * de proposito.
     */
    public static function usarCredenciais(string $dbName, string $dbUser, string $dbPassword): void
    {
        if (self::$connection instanceof PDO) {
            throw new \RuntimeException('Database::usarCredenciais() precisa ser chamado antes da primeira conexao.');
        }

        self::$overrideCredenciais = [
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPassword,
        ];
    }

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';

        if (self::$overrideCredenciais !== null) {
            $config = array_merge($config, self::$overrideCredenciais);
        }

        self::$connection = self::conectar($config, 'Nao foi possivel conectar ao banco de dados. Verifique config/database.php.');

        return self::$connection;
    }

    public static function central(): PDO
    {
        if (self::$central instanceof PDO) {
            return self::$central;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';
        self::$central = self::conectar($config, 'Nao foi possivel conectar ao banco central. Verifique config/database.php.');

        return self::$central;
    }

    /**
     * Conexao avulsa com um banco especifico, independente da conexao
     * "desta requisicao" (connection()) e sem entrar nos singletons desta
     * classe - usada quando e preciso consultar VARIOS bancos de igrejas
     * diferentes numa mesma requisicao (ver Tenant::ativasComEmailCadastrado(),
     * tela de login do dominio raiz). Timeout curto de proposito: um
     * banco de igreja fora do ar nao pode travar a busca inteira.
     */
    public static function conexaoAvulsa(string $dbName, string $dbUser, string $dbPassword): PDO
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $config = array_merge($config, [
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPassword,
        ]);

        return self::conectar($config, 'Nao foi possivel conectar a esse banco.', timeoutSegundos: 3);
    }

    private static function conectar(array $config, string $mensagemErro, ?int $timeoutSegundos = null): PDO
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($timeoutSegundos !== null) {
            $options[PDO::ATTR_TIMEOUT] = $timeoutSegundos;
        }

        try {
            return new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $exception) {
            throw new \RuntimeException($mensagemErro, previous: $exception);
        }
    }
}
