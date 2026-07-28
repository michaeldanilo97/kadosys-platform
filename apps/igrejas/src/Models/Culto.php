<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use PDO;

/**
 * Model de Culto (modulo Cultos).
 *
 * Cada culto pode ter uma lista de presentes (relacao muitos-para-muitos
 * com membros, atraves da tabela pivot culto_frequencias).
 */
final class Culto
{
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly string $data,
        public readonly ?string $hora,
        public readonly ?string $local,
        public readonly ?string $descricao,
        public readonly string $status,
        public readonly int $totalPresentes,
        public readonly string $createdAt,
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
            // Parametros nomeados repetidos na mesma query nao sao
            // suportados pelo driver MySQL do PDO com prepares nativos
            // (PDO::ATTR_EMULATE_PREPARES => false, ver Database.php) -
            // cada ocorrencia de LIKE precisa do seu proprio placeholder,
            // mesmo vinculado ao mesmo valor.
            $where = 'WHERE c.titulo LIKE :search_titulo OR c.local LIKE :search_local';
            $params['search_titulo'] = '%' . $search . '%';
            $params['search_local'] = '%' . $search . '%';
        }

        $totalStmt = Database::connection()->prepare("SELECT COUNT(*) FROM cultos c {$where}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM culto_frequencias cf WHERE cf.culto_id = c.id) AS total_presentes
             FROM cultos c
             {$where}
             ORDER BY c.data DESC, c.hora DESC
             LIMIT :limit OFFSET :offset"
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
        $stmt = Database::connection()->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM culto_frequencias cf WHERE cf.culto_id = c.id) AS total_presentes
             FROM cultos c
             WHERE c.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Proximo culto agendado (data >= hoje, nao cancelado), usado no KPI
     * do dashboard.
     */
    public static function proximoAgendado(): ?self
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM culto_frequencias cf WHERE cf.culto_id = c.id) AS total_presentes
             FROM cultos c
             WHERE c.data >= CURDATE() AND c.status != 'cancelado'
             ORDER BY c.data ASC, c.hora ASC
             LIMIT 1"
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Cultos agendados para hoje (nao cancelados), ordenados por hora -
     * usado no check-in geral por QR fixo (ver Igrejas\Controllers\
     * CheckinController): o membro que escaneia o QR confirma presenca
     * num desses cultos, sem precisar saber o id/link de cada um.
     *
     * @return array<int, self>
     */
    public static function deHoje(): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM culto_frequencias cf WHERE cf.culto_id = c.id) AS total_presentes
             FROM cultos c
             WHERE c.data = CURDATE() AND c.status != 'cancelado'
             ORDER BY c.hora ASC"
        );
        $stmt->execute();

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Cultos realizados/agendados num mes (YYYY-MM), usado no modulo
     * Relatorios.
     *
     * @return array<int, self>
     */
    public static function noMes(string $mes): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM culto_frequencias cf WHERE cf.culto_id = c.id) AS total_presentes
             FROM cultos c
             WHERE DATE_FORMAT(c.data, '%Y-%m') = :mes
             ORDER BY c.data DESC, c.hora DESC"
        );
        $stmt->execute(['mes' => $mes]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Media de presencas por culto REALIZADO num mes (YYYY-MM), usado
     * no modulo Relatorios.
     */
    public static function mediaPresencasNoMes(string $mes): float
    {
        $stmt = Database::connection()->prepare(
            "SELECT AVG(total) FROM (
                SELECT c.id, COUNT(cf.membro_id) AS total
                FROM cultos c
                LEFT JOIN culto_frequencias cf ON cf.culto_id = c.id
                WHERE DATE_FORMAT(c.data, '%Y-%m') = :mes AND c.status = 'realizado'
                GROUP BY c.id
            ) sub"
        );
        $stmt->execute(['mes' => $mes]);

        return (float) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO cultos (titulo, data, hora, local, descricao, status, created_at)
             VALUES (:titulo, :data, :hora, :local, :descricao, :status, NOW())'
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
            'UPDATE cultos SET titulo = :titulo, data = :data, hora = :hora,
                local = :local, descricao = :descricao, status = :status
             WHERE id = :id'
        );
        $stmt->execute(self::bindings($data) + ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM cultos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Membros presentes no culto, ordenados por nome.
     *
     * @return array<int, Membro>
     */
    public static function presentes(int $cultoId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT me.* FROM membros me
             INNER JOIN culto_frequencias cf ON cf.membro_id = me.id
             WHERE cf.culto_id = :culto_id
             ORDER BY me.nome ASC'
        );
        $stmt->execute(['culto_id' => $cultoId]);

        return array_map(Membro::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Cultos em que o membro esteve presente, mais recentes primeiro -
     * usado na aba "Participações" do perfil do membro (inverso de
     * presentes()).
     *
     * @return array<int, self>
     */
    public static function doMembro(int $membroId, int $limite = 0): array
    {
        $limiteSql = $limite > 0 ? ' LIMIT ' . $limite : '';

        $stmt = Database::connection()->prepare(
            "SELECT c.* FROM cultos c
             INNER JOIN culto_frequencias cf ON cf.culto_id = c.id
             WHERE cf.membro_id = :membro_id
             ORDER BY c.data DESC, c.hora DESC{$limiteSql}"
        );
        $stmt->execute(['membro_id' => $membroId]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function addPresenca(int $cultoId, int $membroId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO culto_frequencias (culto_id, membro_id, created_at)
             VALUES (:culto_id, :membro_id, NOW())'
        );
        $stmt->execute(['culto_id' => $cultoId, 'membro_id' => $membroId]);
    }

    /**
     * Usado no check-in geral por QR fixo pra distinguir "primeira
     * confirmacao" de "ja tinha confirmado" (INSERT IGNORE por si so
     * nao diferencia os dois casos, e a mensagem de sucesso muda
     * conforme o caso).
     */
    public static function jaConfirmouPresenca(int $cultoId, int $membroId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM culto_frequencias WHERE culto_id = :culto_id AND membro_id = :membro_id LIMIT 1'
        );
        $stmt->execute(['culto_id' => $cultoId, 'membro_id' => $membroId]);

        return $stmt->fetchColumn() !== false;
    }

    public static function removePresenca(int $cultoId, int $membroId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM culto_frequencias WHERE culto_id = :culto_id AND membro_id = :membro_id'
        );
        $stmt->execute(['culto_id' => $cultoId, 'membro_id' => $membroId]);
    }

    public function dataHoraFormatada(): string
    {
        $formatada = (new \DateTimeImmutable($this->data))->format('d/m/Y');

        if ($this->hora !== null) {
            $formatada .= ' as ' . substr($this->hora, 0, 5);
        }

        return $formatada;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindings(array $data): array
    {
        return [
            'titulo' => trim((string) $data['titulo']),
            'data' => (string) $data['data'],
            'hora' => self::nullable($data['hora'] ?? null),
            'local' => self::nullable($data['local'] ?? null),
            'descricao' => self::nullable($data['descricao'] ?? null),
            'status' => in_array($data['status'] ?? null, ['agendado', 'realizado', 'cancelado'], true)
                ? $data['status']
                : 'agendado',
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            titulo: (string) $row['titulo'],
            data: (string) $row['data'],
            hora: $row['hora'],
            local: $row['local'],
            descricao: $row['descricao'],
            status: (string) $row['status'],
            totalPresentes: (int) ($row['total_presentes'] ?? 0),
            createdAt: (string) $row['created_at'],
        );
    }
}
