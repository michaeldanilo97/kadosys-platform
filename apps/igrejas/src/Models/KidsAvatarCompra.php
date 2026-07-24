<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Compras de itens exclusivos do Avatar (modulo KADOSYS Kids), pagos com
 * moedas em vez de desbloqueados por nivel (ver Igrejas\Models\KidsAvatar
 * - itens do catalogo com "nivel" nulo so entram aqui). Uma vez
 * comprado, o item fica permanentemente disponivel pra aquela crianca,
 * independente do nivel dela subir ou nao depois.
 */
final class KidsAvatarCompra
{
    public const RESULTADO_OK = 'ok';
    public const RESULTADO_JA_POSSUI = 'ja_possui';
    public const RESULTADO_SEM_MOEDAS = 'sem_moedas';

    /**
     * Slugs ja comprados por uma crianca, agrupados por categoria -
     * pronto pra alimentar KidsAvatar::itemDesbloqueado().
     *
     * @return array<string, array<int, string>>
     */
    public static function compradosPor(int $criancaId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT categoria, slug FROM kids_avatar_compras WHERE crianca_id = :crianca_id'
        );
        $stmt->execute(['crianca_id' => $criancaId]);

        $agrupado = ['chapeu' => [], 'acessorio' => [], 'fundo' => [], 'titulo' => [], 'roupa' => []];

        foreach ($stmt->fetchAll() as $row) {
            $agrupado[$row['categoria']][] = $row['slug'];
        }

        return $agrupado;
    }

    /**
     * Tenta comprar um item da loja - desconta as moedas (ver
     * KidsCrianca::gastarMoedas(), condicional no proprio UPDATE) e so
     * registra a compra se o desconto realmente aconteceu, numa unica
     * transacao. Retorna qual dos 3 resultados possiveis aconteceu, pra
     * a tela mostrar a mensagem certa.
     */
    public static function comprar(int $criancaId, string $categoria, string $slug, int $custoMoedas): string
    {
        $pdo = Database::connection();

        $jaTem = $pdo->prepare(
            'SELECT 1 FROM kids_avatar_compras WHERE crianca_id = :crianca_id AND categoria = :categoria AND slug = :slug'
        );
        $jaTem->execute(['crianca_id' => $criancaId, 'categoria' => $categoria, 'slug' => $slug]);

        if ($jaTem->fetchColumn() !== false) {
            return self::RESULTADO_JA_POSSUI;
        }

        $pdo->beginTransaction();

        try {
            if (!KidsCrianca::gastarMoedas($criancaId, $custoMoedas)) {
                $pdo->rollBack();

                return self::RESULTADO_SEM_MOEDAS;
            }

            $inserir = $pdo->prepare(
                'INSERT INTO kids_avatar_compras (crianca_id, categoria, slug, custo_moedas, created_at)
                 VALUES (:crianca_id, :categoria, :slug, :custo_moedas, NOW())'
            );
            $inserir->execute([
                'crianca_id' => $criancaId,
                'categoria' => $categoria,
                'slug' => $slug,
                'custo_moedas' => $custoMoedas,
            ]);

            $pdo->commit();

            return self::RESULTADO_OK;
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }
    }
}
