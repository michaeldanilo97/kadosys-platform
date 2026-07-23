<?php

declare(strict_types=1);

namespace Food\Models;

use Food\Core\Database;

/**
 * Impressora cadastrada so pra referencia da equipe (nome/IP de onde
 * ela fica na rede) - sem driver/protocolo ESC-POS real, o comprovante
 * do PDV continua saindo pela impressao do navegador.
 */
final class Impressora
{
    private const SELECT_COLUNAS = 'id, restaurante_id, nome, ip, ativo, created_at';

    public function __construct(
        public readonly int $id,
        public readonly int $restauranteId,
        public readonly string $nome,
        public readonly ?string $ip,
        public readonly bool $ativo,
        public readonly ?string $createdAt = null,
    ) {
    }

    /** @return array<int, self> */
    public static function doRestaurante(int $restauranteId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SELECT_COLUNAS . ' FROM impressoras WHERE restaurante_id = :restaurante_id ORDER BY nome ASC'
        );
        $stmt->execute(['restaurante_id' => $restauranteId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function criar(int $restauranteId, string $nome, ?string $ip): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO impressoras (restaurante_id, nome, ip, ativo, created_at)
             VALUES (:restaurante_id, :nome, :ip, 1, NOW())'
        );
        $stmt->execute([
            'restaurante_id' => $restauranteId,
            'nome' => trim($nome),
            'ip' => $ip !== null && trim($ip) !== '' ? trim($ip) : null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function excluir(int $id, int $restauranteId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM impressoras WHERE id = :id AND restaurante_id = :restaurante_id'
        );
        $stmt->execute(['id' => $id, 'restaurante_id' => $restauranteId]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            restauranteId: (int) $row['restaurante_id'],
            nome: (string) $row['nome'],
            ip: $row['ip'] ?? null,
            ativo: (bool) $row['ativo'],
            createdAt: isset($row['created_at']) ? (string) $row['created_at'] : null,
        );
    }
}
