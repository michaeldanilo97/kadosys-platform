<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Progresso de leitura da Biblia Interativa (modulo KADOSYS Kids): so
 * guarda QUAIS capitulos cada crianca ja leu (kids_biblia_leituras) -
 * o texto em si vem de BibliaVersiculo/BibliaLivro, dados de referencia
 * compartilhados com o resto da plataforma (projecao/preletor). Cada
 * capitulo novo lido concede um bonus fixo de XP, uma unica vez.
 */
final class KidsBibliaLeitura
{
    private const BONUS_XP = 3;

    /**
     * Marca um capitulo como lido - idempotente (UNIQUE em crianca_id+
     * livro_id+capitulo), so concede o bonus de XP na primeira vez.
     * Retorna true quando o bonus foi concedido agora (pra mostrar um
     * toast simples na tela).
     */
    public static function registrarLeitura(int $criancaId, int $livroId, int $capitulo): bool
    {
        $pdo = Database::connection();

        $inserir = $pdo->prepare(
            'INSERT IGNORE INTO kids_biblia_leituras (crianca_id, livro_id, capitulo, lido_em)
             VALUES (:crianca_id, :livro_id, :capitulo, NOW())'
        );
        $inserir->execute(['crianca_id' => $criancaId, 'livro_id' => $livroId, 'capitulo' => $capitulo]);

        if ($inserir->rowCount() > 0) {
            KidsCrianca::adicionarPontos($criancaId, self::BONUS_XP, 0);

            return true;
        }

        return false;
    }

    /**
     * Numeros dos capitulos ja lidos de um livro, pra destacar na grade
     * de capitulos.
     *
     * @return array<int, int>
     */
    public static function capitulosLidosDoLivro(int $criancaId, int $livroId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT capitulo FROM kids_biblia_leituras WHERE crianca_id = :crianca_id AND livro_id = :livro_id'
        );
        $stmt->execute(['crianca_id' => $criancaId, 'livro_id' => $livroId]);

        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Quantos capitulos distintos cada livro (por id) ja teve lido pela
     * crianca - usado na lista de livros pra mostrar "X/Y capitulos".
     *
     * @return array<int, int> livro_id => quantidade
     */
    public static function contagemPorLivro(int $criancaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT livro_id, COUNT(*) AS total FROM kids_biblia_leituras WHERE crianca_id = :crianca_id GROUP BY livro_id'
        );
        $stmt->execute(['crianca_id' => $criancaId]);

        $contagem = [];
        foreach ($stmt->fetchAll() as $linha) {
            $contagem[(int) $linha['livro_id']] = (int) $linha['total'];
        }

        return $contagem;
    }
}
