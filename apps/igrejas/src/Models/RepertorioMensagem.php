<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Avisos rapidos entre musicos durante o culto (ex.: "abaixa um tom",
 * "repete o refrao") - canal discreto dentro do Modo Culto, sincronizado
 * junto do mesmo polling que acompanha a musica atual (ver
 * RepertorioController::estado()). Fica guardado no historico do
 * repertorio (nao some ao encerrar o culto).
 */
final class RepertorioMensagem
{
    private const LIMITE_TAMANHO = 280;

    public function __construct(
        public readonly int $id,
        public readonly int $repertorioId,
        public readonly ?int $userId,
        public readonly ?string $userNome,
        public readonly string $texto,
        public readonly string $createdAt,
    ) {
    }

    public static function enviar(int $repertorioId, ?int $userId, string $texto): void
    {
        $texto = trim($texto);

        if ($texto === '') {
            return;
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO repertorio_mensagens (repertorio_id, user_id, texto, created_at)
             VALUES (:repertorio_id, :user_id, :texto, NOW())'
        );
        $stmt->execute([
            'repertorio_id' => $repertorioId,
            'user_id' => $userId,
            'texto' => mb_substr($texto, 0, self::LIMITE_TAMANHO),
        ]);
    }

    /**
     * Ultimas mensagens do repertorio (mais recente por ultimo, pra
     * exibir de cima pra baixo direto no chat) - o cliente compara os
     * ids ja renderizados e so acrescenta as novas a cada poll, sem
     * precisar redesenhar a lista inteira.
     *
     * @return array<int, self>
     */
    public static function ultimas(int $repertorioId, int $limite = 50): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT rm.*, u.name AS user_nome
             FROM repertorio_mensagens rm
             LEFT JOIN users u ON u.id = rm.user_id
             WHERE rm.repertorio_id = :repertorio_id
             ORDER BY rm.id DESC
             LIMIT :limite'
        );
        $stmt->bindValue('repertorio_id', $repertorioId, PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return array_reverse(array_map(self::fromRow(...), $stmt->fetchAll()));
    }

    /**
     * @return array<string, mixed>
     */
    public function paraJson(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->userNome ?? 'Alguém',
            'texto' => $this->texto,
            'createdAt' => $this->createdAt,
        ];
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            repertorioId: (int) $row['repertorio_id'],
            userId: $row['user_id'] !== null ? (int) $row['user_id'] : null,
            userNome: $row['user_nome'] ?? null,
            texto: (string) $row['texto'],
            createdAt: (string) $row['created_at'],
        );
    }
}
