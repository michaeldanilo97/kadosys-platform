<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Anotacao PESSOAL de um usuario num louvor (ex.: "usar capotraste na
 * 2a casa", "trocar pra guitarra limpa") - uma por usuario por louvor,
 * NUNCA compartilhada com os demais musicos (diferente do historico de
 * tons, que e publico pra todo mundo, ver LouvorTomHistorico).
 */
final class LouvorAnotacao
{
    public function __construct(
        public readonly int $id,
        public readonly int $louvorId,
        public readonly int $userId,
        public readonly string $texto,
        public readonly string $updatedAt,
    ) {
    }

    public static function doUsuario(int $louvorId, int $userId): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM louvor_anotacoes WHERE louvor_id = :louvor_id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['louvor_id' => $louvorId, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Salva (cria ou atualiza) a anotacao do usuario nesse louvor - um
     * texto em branco apaga a anotacao existente, se houver.
     */
    public static function salvar(int $louvorId, int $userId, string $texto): void
    {
        $texto = trim($texto);

        if ($texto === '') {
            self::remover($louvorId, $userId);

            return;
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO louvor_anotacoes (louvor_id, user_id, texto, created_at)
             VALUES (:louvor_id, :user_id, :texto, NOW())
             ON DUPLICATE KEY UPDATE texto = VALUES(texto)'
        );
        $stmt->execute(['louvor_id' => $louvorId, 'user_id' => $userId, 'texto' => $texto]);
    }

    public static function remover(int $louvorId, int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM louvor_anotacoes WHERE louvor_id = :louvor_id AND user_id = :user_id'
        );
        $stmt->execute(['louvor_id' => $louvorId, 'user_id' => $userId]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            louvorId: (int) $row['louvor_id'],
            userId: (int) $row['user_id'],
            texto: (string) $row['texto'],
            updatedAt: (string) $row['updated_at'],
        );
    }
}
