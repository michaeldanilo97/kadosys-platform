<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Aviso do dono da plataforma (KADOSYS) para todas as igrejas
 * cadastradas, mostrado no sino de notificacoes de cada uma delas -
 * ver PlataformaController e layouts/dashboard.php. So um aviso fica
 * ativo por vez (publicar um novo encerra o anterior automaticamente),
 * mesmo padrao ja usado em ProjecaoSessao::iniciar().
 */
final class PlataformaAviso
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
        $stmt = Database::central()->prepare(
            'SELECT * FROM plataforma_avisos WHERE ativo = 1 ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * @return array<int, self>
     */
    public static function todos(): array
    {
        $stmt = Database::central()->query('SELECT * FROM plataforma_avisos ORDER BY id DESC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function publicar(string $mensagem): int
    {
        $connection = Database::central();

        $connection->prepare('UPDATE plataforma_avisos SET ativo = 0 WHERE ativo = 1')->execute();

        $stmt = $connection->prepare(
            'INSERT INTO plataforma_avisos (mensagem, ativo, created_at) VALUES (:mensagem, 1, NOW())'
        );
        $stmt->execute(['mensagem' => trim($mensagem)]);

        return (int) $connection->lastInsertId();
    }

    public static function encerrar(int $id): void
    {
        $stmt = Database::central()->prepare('UPDATE plataforma_avisos SET ativo = 0 WHERE id = :id');
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
