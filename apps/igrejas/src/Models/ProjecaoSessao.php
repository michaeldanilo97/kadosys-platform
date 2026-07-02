<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Sessao de projecao: representa o "culto ao vivo" em andamento,
 * controlada pelo operador e acessada pelo telao (via token, link
 * direto) e pelo tablet do preletor (via PIN, digitado manualmente).
 *
 * Apenas uma sessao fica ativa por vez (uma igreja projeta um culto de
 * cada vez); iniciar uma nova sessao encerra automaticamente a anterior.
 */
final class ProjecaoSessao
{
    public function __construct(
        public readonly int $id,
        public readonly string $token,
        public readonly string $pin,
        public readonly ?int $criadoPor,
        public readonly bool $ativo,
        public readonly string $createdAt,
    ) {
    }

    public static function ativa(): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM projecao_sessoes WHERE ativo = 1 ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM projecao_sessoes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function findAtivaByToken(string $token): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM projecao_sessoes WHERE token = :token AND ativo = 1 LIMIT 1'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function findAtivaByPin(string $pin): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM projecao_sessoes WHERE pin = :pin AND ativo = 1 LIMIT 1'
        );
        $stmt->execute(['pin' => $pin]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Encerra qualquer sessao ativa e inicia uma nova, com token e PIN
     * unicos, alem de um estado inicial em branco.
     */
    public static function iniciar(?int $criadoPor): self
    {
        $connection = Database::connection();

        $stmt = $connection->prepare('UPDATE projecao_sessoes SET ativo = 0 WHERE ativo = 1');
        $stmt->execute();

        $token = self::gerarTokenUnico();
        $pin = self::gerarPinUnico();

        $insert = $connection->prepare(
            'INSERT INTO projecao_sessoes (token, pin, criado_por, ativo, created_at)
             VALUES (:token, :pin, :criado_por, 1, NOW())'
        );
        $insert->execute(['token' => $token, 'pin' => $pin, 'criado_por' => $criadoPor]);

        $sessaoId = (int) $connection->lastInsertId();

        $estado = $connection->prepare(
            'INSERT INTO projecao_estados (sessao_id, modo, versao, updated_at)
             VALUES (:sessao_id, "blank", 1, NOW())'
        );
        $estado->execute(['sessao_id' => $sessaoId]);

        return self::find($sessaoId);
    }

    public static function encerrar(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE projecao_sessoes SET ativo = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function gerarTokenUnico(): string
    {
        do {
            $token = bin2hex(random_bytes(20));
        } while (self::tokenExiste($token));

        return $token;
    }

    private static function tokenExiste(string $token): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM projecao_sessoes WHERE token = :token');
        $stmt->execute(['token' => $token]);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    private static function gerarPinUnico(): string
    {
        do {
            $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::pinAtivoExiste($pin));

        return $pin;
    }

    private static function pinAtivoExiste(string $pin): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM projecao_sessoes WHERE pin = :pin AND ativo = 1'
        );
        $stmt->execute(['pin' => $pin]);

        return ((int) $stmt->fetchColumn()) > 0;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            token: (string) $row['token'],
            pin: (string) $row['pin'],
            criadoPor: $row['criado_por'] !== null ? (int) $row['criado_por'] : null,
            ativo: (bool) $row['ativo'],
            createdAt: (string) $row['created_at'],
        );
    }
}
