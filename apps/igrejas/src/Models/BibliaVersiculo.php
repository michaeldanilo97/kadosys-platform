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

    /**
     * Referencia do proximo versiculo (avanca capitulo/livro quando
     * necessario). Retorna null ao chegar no fim da Biblia (Ap 22:21)
     * ou se o texto da versao ainda nao foi importado.
     *
     * @return array{livro_id: int, capitulo: int, versiculo: int}|null
     */
    public static function proximaReferencia(string $versao, int $livroId, int $capitulo, int $versiculo): ?array
    {
        $totalNoCapitulo = self::totalVersiculos($versao, $livroId, $capitulo);

        if ($totalNoCapitulo === 0) {
            return null;
        }

        if ($versiculo < $totalNoCapitulo) {
            return ['livro_id' => $livroId, 'capitulo' => $capitulo, 'versiculo' => $versiculo + 1];
        }

        $livro = BibliaLivro::find($livroId);

        if ($livro === null) {
            return null;
        }

        if ($capitulo < $livro->totalCapitulos) {
            return ['livro_id' => $livroId, 'capitulo' => $capitulo + 1, 'versiculo' => 1];
        }

        $proximoLivroId = $livroId + 1;

        return $proximoLivroId <= 66 ? ['livro_id' => $proximoLivroId, 'capitulo' => 1, 'versiculo' => 1] : null;
    }

    /**
     * Referencia do versiculo anterior (retrocede capitulo/livro quando
     * necessario). Retorna null ao chegar no inicio da Biblia (Gn 1:1)
     * ou se o texto da versao ainda nao foi importado.
     *
     * @return array{livro_id: int, capitulo: int, versiculo: int}|null
     */
    public static function referenciaAnterior(string $versao, int $livroId, int $capitulo, int $versiculo): ?array
    {
        if ($versiculo > 1) {
            return ['livro_id' => $livroId, 'capitulo' => $capitulo, 'versiculo' => $versiculo - 1];
        }

        if ($capitulo > 1) {
            $totalAnterior = self::totalVersiculos($versao, $livroId, $capitulo - 1);

            return $totalAnterior > 0
                ? ['livro_id' => $livroId, 'capitulo' => $capitulo - 1, 'versiculo' => $totalAnterior]
                : null;
        }

        $livroAnteriorId = $livroId - 1;

        if ($livroAnteriorId < 1) {
            return null;
        }

        $livroAnterior = BibliaLivro::find($livroAnteriorId);

        if ($livroAnterior === null) {
            return null;
        }

        $totalAnterior = self::totalVersiculos($versao, $livroAnteriorId, $livroAnterior->totalCapitulos);

        return $totalAnterior > 0
            ? ['livro_id' => $livroAnteriorId, 'capitulo' => $livroAnterior->totalCapitulos, 'versiculo' => $totalAnterior]
            : null;
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
