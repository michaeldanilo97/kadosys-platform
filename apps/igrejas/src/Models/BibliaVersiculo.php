<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Model de versiculo biblico. O texto e importado separadamente (ver
 * database/seed_biblia.php); esta classe so faz leitura.
 *
 * Cada versiculo pertence a uma versao/traducao (ver BibliaVersao).
 */
final class BibliaVersiculo
{
    public function __construct(
        public readonly int $id,
        public readonly int $livroId,
        public readonly string $versao,
        public readonly int $capitulo,
        public readonly int $versiculo,
        public readonly string $texto,
    ) {
    }

    /**
     * Todos os versiculos de um capitulo, em ordem.
     *
     * @return array<int, self>
     */
    public static function doCapitulo(string $versao, int $livroId, int $capitulo): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM biblia_versiculos
             WHERE versao = :versao AND livro_id = :livro_id AND capitulo = :capitulo
             ORDER BY versiculo ASC'
        );
        $stmt->execute(['versao' => $versao, 'livro_id' => $livroId, 'capitulo' => $capitulo]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Intervalo de versiculos (ex.: Joao 3:16-18), usado para montar o
     * texto exibido na projecao.
     *
     * @return array<int, self>
     */
    public static function doIntervalo(string $versao, int $livroId, int $capitulo, int $inicio, int $fim): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM biblia_versiculos
             WHERE versao = :versao AND livro_id = :livro_id AND capitulo = :capitulo
                   AND versiculo BETWEEN :inicio AND :fim
             ORDER BY versiculo ASC'
        );
        $stmt->execute([
            'versao' => $versao,
            'livro_id' => $livroId,
            'capitulo' => $capitulo,
            'inicio' => min($inicio, $fim),
            'fim' => max($inicio, $fim),
        ]);

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    /**
     * Maior numero de versiculo cadastrado para um capitulo (usado para
     * validar/limitar a selecao no painel de controle).
     */
    public static function totalVersiculos(string $versao, int $livroId, int $capitulo): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(MAX(versiculo), 0) FROM biblia_versiculos
             WHERE versao = :versao AND livro_id = :livro_id AND capitulo = :capitulo'
        );
        $stmt->execute(['versao' => $versao, 'livro_id' => $livroId, 'capitulo' => $capitulo]);

        return (int) $stmt->fetchColumn();
    }

    public static function textoImportado(): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM biblia_versiculos');
        $stmt->execute();

        return ((int) $stmt->fetchColumn()) > 0;
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            livroId: (int) $row['livro_id'],
            versao: (string) $row['versao'],
            capitulo: (int) $row['capitulo'],
            versiculo: (int) $row['versiculo'],
            texto: (string) $row['texto'],
        );
    }
}
