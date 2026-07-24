<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * "Missao do dia" da Biblioteca Kids: 2 conteudos sorteados por
 * crianca, todo dia, com bonus de XP/moedas so nesse dia - da um
 * motivo concreto pra crianca abrir o app hoje, mesmo que ja tenha
 * concluido bastante coisa antes. Sorteio prioriza conteudo que a
 * crianca ainda NAO concluiu (pra empurrar progresso real de verdade,
 * nao so revisitar o que ja fez), com fallback pra qualquer conteudo
 * publicado se ela ja tiver terminado tudo. O sorteio e deterministico
 * (mesmo resultado o dia inteiro, mesmo recarregando a pagina varias
 * vezes) via hash da data+crianca, sem precisar guardar estado de RNG.
 */
final class KidsMissaoDiaria
{
    private const QUANTIDADE = 2;
    private const BONUS_XP = 15;
    private const BONUS_MOEDAS = 8;

    /**
     * Missoes de hoje pra uma crianca - gera na primeira consulta do
     * dia (INSERT IGNORE, seguro pra chamadas concorrentes), so busca
     * nas seguintes.
     *
     * @return array<int, array{conteudoId: int, titulo: string, tipo: string, emoji: string, concluida: bool}>
     */
    public static function deHoje(int $criancaId): array
    {
        $hoje = date('Y-m-d');
        $existentes = self::buscar($criancaId, $hoje);

        if ($existentes !== []) {
            return $existentes;
        }

        self::gerar($criancaId, $hoje);

        return self::buscar($criancaId, $hoje);
    }

    /**
     * Marca uma missao de hoje como concluida e concede o bonus, se o
     * conteudo concluido agora fizer parte da missao do dia e ela ainda
     * nao tiver sido reclamada - chamado de KidsAppController::concluir()/
     * concluirDesafio(), logo apos a conclusao normal do conteudo.
     *
     * @return array{xp: int, moedas: int}|null
     */
    public static function marcarConcluidaSeForMissao(int $criancaId, int $conteudoId): ?array
    {
        $hoje = date('Y-m-d');

        $stmt = Database::connection()->prepare(
            'UPDATE kids_missoes_diarias
             SET concluida_em = NOW()
             WHERE crianca_id = :crianca_id AND data = :data AND conteudo_id = :conteudo_id AND concluida_em IS NULL'
        );
        $stmt->execute(['crianca_id' => $criancaId, 'data' => $hoje, 'conteudo_id' => $conteudoId]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        KidsCrianca::adicionarPontos($criancaId, self::BONUS_XP, self::BONUS_MOEDAS);

        return ['xp' => self::BONUS_XP, 'moedas' => self::BONUS_MOEDAS];
    }

    /**
     * @return array<int, array{conteudoId: int, titulo: string, tipo: string, emoji: string, concluida: bool}>
     */
    private static function buscar(int $criancaId, string $data): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.conteudo_id, m.concluida_em, c.titulo, c.tipo
             FROM kids_missoes_diarias m
             INNER JOIN kids_conteudos c ON c.id = m.conteudo_id
             WHERE m.crianca_id = :crianca_id AND m.data = :data
             ORDER BY m.id ASC'
        );
        $stmt->execute(['crianca_id' => $criancaId, 'data' => $data]);

        return array_map(
            static fn (array $row) => [
                'conteudoId' => (int) $row['conteudo_id'],
                'titulo' => (string) $row['titulo'],
                'tipo' => (string) $row['tipo'],
                'emoji' => KidsConteudo::TIPOS[$row['tipo']]['emoji'] ?? '📦',
                'concluida' => $row['concluida_em'] !== null,
            ],
            $stmt->fetchAll()
        );
    }

    private static function gerar(int $criancaId, string $data): void
    {
        $pdo = Database::connection();

        $naoConcluidos = $pdo->prepare(
            "SELECT c.id FROM kids_conteudos c
             WHERE c.status = 'publicado'
             AND NOT EXISTS (
                 SELECT 1 FROM kids_conteudo_conclusoes k
                 WHERE k.conteudo_id = c.id AND k.crianca_id = :crianca_id
             )"
        );
        $naoConcluidos->execute(['crianca_id' => $criancaId]);
        $ids = array_column($naoConcluidos->fetchAll(), 'id');

        if (count($ids) < self::QUANTIDADE) {
            $todos = $pdo->query("SELECT id FROM kids_conteudos WHERE status = 'publicado'")->fetchAll();
            $ids = array_column($todos, 'id');
        }

        if ($ids === []) {
            return;
        }

        usort($ids, static fn ($a, $b) => crc32($data . '-' . $criancaId . '-' . $a) <=> crc32($data . '-' . $criancaId . '-' . $b));
        $escolhidos = array_slice($ids, 0, min(self::QUANTIDADE, count($ids)));

        $inserir = $pdo->prepare(
            'INSERT IGNORE INTO kids_missoes_diarias (crianca_id, data, conteudo_id, created_at)
             VALUES (:crianca_id, :data, :conteudo_id, NOW())'
        );

        foreach ($escolhidos as $conteudoId) {
            $inserir->execute(['crianca_id' => $criancaId, 'data' => $data, 'conteudo_id' => $conteudoId]);
        }
    }
}
