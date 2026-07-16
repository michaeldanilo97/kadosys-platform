<?php

declare(strict_types=1);

namespace Superadmin\Models;

use Superadmin\Core\DatabaseBarbearias;

/**
 * Escrita na tabela barbearia_avisos do KADOSYS Barbearias - mesma
 * tabela/semantica de Barbearias\Models\BarbeariaAviso (leitura, la
 * dentro do proprio app), publicada aqui pelo Super Admin.
 */
final class AvisoBarbearia
{
    public function __construct(
        public readonly int $id,
        public readonly string $mensagem,
        public readonly bool $ativo,
        public readonly string $createdAt,
    ) {
    }

    public static function ativo(): ?self
    {
        $stmt = DatabaseBarbearias::connection()->query(
            'SELECT id, mensagem, ativo, created_at FROM barbearia_avisos WHERE ativo = 1 ORDER BY id DESC LIMIT 1'
        );
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return array<int, self>
     */
    public static function todos(): array
    {
        $stmt = DatabaseBarbearias::connection()->query(
            'SELECT id, mensagem, ativo, created_at FROM barbearia_avisos ORDER BY id DESC LIMIT 20'
        );

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function publicar(string $mensagem): int
    {
        $connection = DatabaseBarbearias::connection();

        $connection->prepare('UPDATE barbearia_avisos SET ativo = 0 WHERE ativo = 1')->execute();

        $stmt = $connection->prepare(
            'INSERT INTO barbearia_avisos (mensagem, ativo, created_at) VALUES (:mensagem, 1, NOW())'
        );
        $stmt->execute(['mensagem' => trim($mensagem)]);

        return (int) $connection->lastInsertId();
    }

    public static function encerrar(int $id): void
    {
        $stmt = DatabaseBarbearias::connection()->prepare('UPDATE barbearia_avisos SET ativo = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            mensagem: (string) $row['mensagem'],
            ativo: (bool) $row['ativo'],
            createdAt: (string) $row['created_at'],
        );
    }
}
