<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de ComunicacaoAviso (modulo Comunicacao: avisos e comunicados
 * centralizados para membros e lideranca).
 */
final class ComunicacaoAviso
{
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly string $conteudo,
        public readonly string $publicoAlvo,
        public readonly string $status,
        public readonly ?string $dataPublicacao,
        public readonly string $createdAt,
        public readonly bool $lido = false,
    ) {
    }

    /**
     * @return array{items: array<int, self>, total: int, page: int, perPage: int, lastPage: int}
     */
    public static function paginate(int $page, int $perPage, string $search = ''): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $where = '';
        $params = [];

        if ($search !== '') {
            $where = 'WHERE titulo LIKE :search_titulo OR conteudo LIKE :search_conteudo';
            $params['search_titulo'] = '%' . $search . '%';
            $params['search_conteudo'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM comunicacao_avisos {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT * FROM comunicacao_avisos {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map(self::fromRow(...), $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function find(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM comunicacao_avisos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function countPublicados(): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM comunicacao_avisos WHERE status = 'publicado'");
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Avisos publicados mais recentes, usado no sino de notificacoes do
     * topo do painel.
     *
     * @return array<int, self>
     */
    public static function ultimosPublicados(int $limite = 5): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM comunicacao_avisos WHERE status = 'publicado'
             ORDER BY data_publicacao DESC, created_at DESC
             LIMIT :limite"
        );
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Avisos publicados pra "todos os membros" (exclui os marcados como
     * "so lideranca"), usado no quadro publico sem login (ver
     * AvisoPublicoController) - a congregacao pode ver, mas avisos
     * internos da lideranca continuam restritos ao painel.
     *
     * @return array<int, self>
     */
    public static function publicosParaTodos(int $limite = 30): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM comunicacao_avisos WHERE status = 'publicado' AND publico_alvo = 'todos'
             ORDER BY data_publicacao DESC, created_at DESC
             LIMIT :limite"
        );
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Avisos publicados mais recentes com a flag "lido" (por usuario),
     * usado na lista da barra lateral do painel - ver
     * layouts/dashboard.php e marcarComoLido().
     *
     * @return array<int, self>
     */
    public static function paraSidebar(int $userId, int $limite = 5): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT ca.*, (cal.id IS NOT NULL) AS lido
             FROM comunicacao_avisos ca
             LEFT JOIN comunicacao_aviso_leituras cal ON cal.aviso_id = ca.id AND cal.user_id = :user_id
             WHERE ca.status = 'publicado'
             ORDER BY ca.data_publicacao DESC, ca.created_at DESC
             LIMIT :limite"
        );
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Marca um aviso como lido por um usuario - idempotente (abrir o
     * mesmo aviso de novo nao da erro nem duplica linha).
     */
    public static function marcarComoLido(int $avisoId, int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO comunicacao_aviso_leituras (aviso_id, user_id, lido_em)
             VALUES (:aviso_id, :user_id, NOW())'
        );
        $stmt->execute(['aviso_id' => $avisoId, 'user_id' => $userId]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO comunicacao_avisos (titulo, conteudo, publico_alvo, status, data_publicacao, created_at)
             VALUES (:titulo, :conteudo, :publico_alvo, :status, :data_publicacao, NOW())'
        );
        $stmt->execute(self::bindings($data));

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE comunicacao_avisos SET
                titulo = :titulo, conteudo = :conteudo, publico_alvo = :publico_alvo,
                status = :status, data_publicacao = :data_publicacao
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM comunicacao_avisos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        $status = in_array($data['status'] ?? null, ['rascunho', 'publicado', 'arquivado'], true) ? $data['status'] : 'rascunho';
        $dataPublicacao = trim((string) ($data['data_publicacao'] ?? ''));

        return [
            'titulo' => trim((string) $data['titulo']),
            'conteudo' => trim((string) $data['conteudo']),
            'publico_alvo' => in_array($data['publico_alvo'] ?? null, ['todos', 'lideranca'], true) ? $data['publico_alvo'] : 'todos',
            'status' => $status,
            // Publicar sem escolher data assume hoje - assim o aviso ja
            // aparece ordenado corretamente sem exigir um passo extra.
            'data_publicacao' => $dataPublicacao !== '' ? $dataPublicacao : ($status === 'publicado' ? date('Y-m-d') : null),
        ];
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            titulo: (string) $row['titulo'],
            conteudo: (string) $row['conteudo'],
            publicoAlvo: (string) $row['publico_alvo'],
            status: (string) $row['status'],
            dataPublicacao: $row['data_publicacao'],
            createdAt: (string) $row['created_at'],
            lido: (bool) ($row['lido'] ?? false),
        );
    }
}
