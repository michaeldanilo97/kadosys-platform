<?php

declare(strict_types=1);

namespace Superadmin\Models;

use Superadmin\Core\DatabaseIgrejas;

/**
 * Escrita na tabela plataforma_avisos do KADOSYS Igrejas (banco central)
 * - mesma tabela/semantica de Igrejas\Models\PlataformaAviso, mas
 * publicada aqui pelo Super Admin. O Super Admin nao replica a
 * segmentacao por publico_alvo admins/membros (isso e uma escolha
 * interna de cada igreja) - sempre publica com publico_alvo 'todos'.
 */
final class AvisoIgreja
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
        $stmt = DatabaseIgrejas::connection()->query(
            'SELECT id, mensagem, ativo, created_at FROM plataforma_avisos WHERE ativo = 1 ORDER BY id DESC LIMIT 1'
        );
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return array<int, self>
     */
    public static function todos(): array
    {
        $stmt = DatabaseIgrejas::connection()->query(
            'SELECT id, mensagem, ativo, created_at FROM plataforma_avisos ORDER BY id DESC LIMIT 20'
        );

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function publicar(string $mensagem): int
    {
        $connection = DatabaseIgrejas::connection();

        $connection->prepare('UPDATE plataforma_avisos SET ativo = 0 WHERE ativo = 1')->execute();

        $stmt = $connection->prepare(
            "INSERT INTO plataforma_avisos (mensagem, ativo, publico_alvo, created_at) VALUES (:mensagem, 1, 'todos', NOW())"
        );
        $stmt->execute(['mensagem' => trim($mensagem)]);

        return (int) $connection->lastInsertId();
    }

    public static function encerrar(int $id): void
    {
        $stmt = DatabaseIgrejas::connection()->prepare('UPDATE plataforma_avisos SET ativo = 0 WHERE id = :id');
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
