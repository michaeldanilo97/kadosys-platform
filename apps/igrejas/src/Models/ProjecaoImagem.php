<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Galeria de imagens da igreja (cartazes, avisos especiais etc.) que o
 * operador pode exibir em tela cheia no telao com um clique (ver
 * ProjecaoEstado::mostrarImagem()). Marcar como "favorita" e o que
 * mostra a imagem na faixa de acesso rapido do painel de Projecao -
 * sem isso, uma igreja com dezenas de imagens enviadas ficaria com uma
 * lista longa demais pra usar durante o culto.
 */
final class ProjecaoImagem
{
    public function __construct(
        public readonly int $id,
        public readonly string $nomeArquivo,
        public readonly string $path,
        public readonly bool $favorita,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function todas(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM projecao_imagens ORDER BY created_at DESC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * @return array<int, self>
     */
    public static function favoritas(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM projecao_imagens WHERE favorita = 1 ORDER BY created_at DESC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM projecao_imagens WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function create(string $nomeArquivo, string $path): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO projecao_imagens (nome_arquivo, path, created_at) VALUES (:nome_arquivo, :path, NOW())'
        );
        $stmt->execute(['nome_arquivo' => $nomeArquivo, 'path' => $path]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function alternarFavorita(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE projecao_imagens SET favorita = NOT favorita WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM projecao_imagens WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nomeArquivo: (string) $row['nome_arquivo'],
            path: (string) $row['path'],
            favorita: (bool) $row['favorita'],
            createdAt: (string) $row['created_at'],
        );
    }
}
