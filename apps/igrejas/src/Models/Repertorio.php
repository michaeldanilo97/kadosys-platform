<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de Repertorio (programacao de culto): a ordem dos louvores que
 * vao tocar num culto, montada/arrastada pelo lider de louvor (ver
 * User::liderLouvor) e acompanhada em tempo real (por polling, ver
 * RepertorioController::estado()) pelos demais musicos no Modo Culto -
 * inclusive qual musica esta tocando AGORA (atual_item_id), que o lider
 * avanca com "proxima"/"anterior" durante o culto.
 */
final class Repertorio
{
    /**
     * @param array<int, RepertorioItem> $itens
     */
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly string $status,
        public readonly ?int $atualItemId,
        public readonly int $versao,
        public readonly ?int $criadoPor,
        public readonly ?string $criadoPorNome,
        public readonly string $createdAt,
        public readonly array $itens = [],
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function todos(): array
    {
        $stmt = Database::connection()->query(
            'SELECT r.*, u.name AS criado_por_nome
             FROM repertorios r
             LEFT JOIN users u ON u.id = r.criado_por
             ORDER BY r.created_at DESC'
        );

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*, u.name AS criado_por_nome
             FROM repertorios r
             LEFT JOIN users u ON u.id = r.criado_por
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $repertorio = self::fromRow($row);

        return new self(
            id: $repertorio->id,
            titulo: $repertorio->titulo,
            status: $repertorio->status,
            atualItemId: $repertorio->atualItemId,
            versao: $repertorio->versao,
            criadoPor: $repertorio->criadoPor,
            criadoPorNome: $repertorio->criadoPorNome,
            createdAt: $repertorio->createdAt,
            itens: RepertorioItem::doRepertorio($id),
        );
    }

    public static function create(string $titulo, ?int $criadoPor): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO repertorios (titulo, criado_por, created_at) VALUES (:titulo, :criado_por, NOW())'
        );
        $stmt->execute(['titulo' => trim($titulo), 'criado_por' => $criadoPor]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function encerrar(int $id): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE repertorios SET status = 'encerrado', versao = versao + 1 WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    public static function adicionarItem(int $repertorioId, int $louvorId): void
    {
        $pdo = Database::connection();

        $stmtOrdem = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 AS proxima FROM repertorio_itens WHERE repertorio_id = :repertorio_id');
        $stmtOrdem->execute(['repertorio_id' => $repertorioId]);
        $proximaOrdem = (int) $stmtOrdem->fetch()['proxima'];

        $stmt = $pdo->prepare(
            'INSERT INTO repertorio_itens (repertorio_id, louvor_id, ordem, created_at) VALUES (:repertorio_id, :louvor_id, :ordem, NOW())'
        );
        $stmt->execute(['repertorio_id' => $repertorioId, 'louvor_id' => $louvorId, 'ordem' => $proximaOrdem]);

        self::bumparVersao($repertorioId);
    }

    public static function removerItem(int $repertorioId, int $itemId): void
    {
        $pdo = Database::connection();

        // Se o item removido era o "tocando agora", limpa o ponteiro -
        // sem isso, atual_item_id ficaria apontando pra uma linha que
        // nao existe mais (FK com ON DELETE SET NULL ja cobriria isso
        // no banco, mas fazemos explicito aqui pra bumpar a versao
        // tambem, avisando quem esta no Modo Culto).
        $stmt = $pdo->prepare('DELETE FROM repertorio_itens WHERE id = :item_id AND repertorio_id = :repertorio_id');
        $stmt->execute(['item_id' => $itemId, 'repertorio_id' => $repertorioId]);

        self::bumparVersao($repertorioId);
    }

    /**
     * Substitui a ordem de todos os itens do repertorio de uma vez -
     * usado pelo drag-and-drop do lider (ver repertorio-editor.js), que
     * sempre manda a lista COMPLETA na nova ordem apos soltar o item
     * arrastado.
     *
     * @param array<int, int> $itemIdsEmOrdem
     */
    public static function reordenar(int $repertorioId, array $itemIdsEmOrdem): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE repertorio_itens SET ordem = :ordem WHERE id = :item_id AND repertorio_id = :repertorio_id'
        );

        foreach (array_values($itemIdsEmOrdem) as $posicao => $itemId) {
            $stmt->execute(['ordem' => $posicao + 1, 'item_id' => (int) $itemId, 'repertorio_id' => $repertorioId]);
        }

        self::bumparVersao($repertorioId);
    }

    /**
     * Define qual item e o "tocando agora", sincronizado pro Modo Culto
     * de todos via polling - null tira o destaque de "tocando agora"
     * sem apagar nada do repertorio.
     */
    public static function definirAtual(int $repertorioId, ?int $itemId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE repertorios SET atual_item_id = :item_id, versao = versao + 1 WHERE id = :id'
        );
        $stmt->execute(['item_id' => $itemId, 'id' => $repertorioId]);
    }

    private static function bumparVersao(int $repertorioId): void
    {
        $stmt = Database::connection()->prepare('UPDATE repertorios SET versao = versao + 1 WHERE id = :id');
        $stmt->execute(['id' => $repertorioId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function paraJson(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'status' => $this->status,
            'versao' => $this->versao,
            'atualItemId' => $this->atualItemId,
            'itens' => array_map(static fn (RepertorioItem $item) => $item->paraJson(), $this->itens),
        ];
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            titulo: (string) $row['titulo'],
            status: (string) $row['status'],
            atualItemId: $row['atual_item_id'] !== null ? (int) $row['atual_item_id'] : null,
            versao: (int) $row['versao'],
            criadoPor: $row['criado_por'] !== null ? (int) $row['criado_por'] : null,
            criadoPorNome: $row['criado_por_nome'] ?? null,
            createdAt: (string) $row['created_at'],
        );
    }
}
