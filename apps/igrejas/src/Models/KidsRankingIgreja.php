<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;
use Igrejas\Core\TenantResolver;
use PDO;

/**
 * Ranking agregado de XP das criancas por igreja (tabela
 * "plataforma_kids_ranking", so existe no banco central - ver
 * database/migrations/069_create_plataforma_kids_ranking.sql) -
 * alimenta o "ranking entre igrejas" da Biblioteca Kids. So guarda o
 * TOTAL de XP por igreja, nunca dado de crianca nenhuma: o ranking
 * crianca-por-crianca continua restrito a propria igreja (ver
 * KidsCrianca::rankingDaIgreja(), direto no banco de cada uma).
 */
final class KidsRankingIgreja
{
    /**
     * Soma XP ganho por uma crianca ao total da igreja atual - chamado
     * de dentro de KidsCrianca::adicionarPontos()/concederPontos(), o
     * mesmo lugar que ja concede XP pra crianca. So faz sentido (e so
     * funciona) quando a requisicao veio de um subdominio de igreja
     * provisionada automaticamente (TenantResolver::atual()) - fora
     * disso (banco central, instalacao unica sem multi-tenant, dev
     * local) nao ha "igreja atual" pra creditar, entao nao faz nada.
     */
    public static function somarXp(int $xpGanho): void
    {
        if ($xpGanho <= 0) {
            return;
        }

        $tenant = TenantResolver::atual();

        if ($tenant === null) {
            return;
        }

        $stmt = Database::central()->prepare(
            'INSERT INTO plataforma_kids_ranking (tenant_id, xp_total_kids, atualizado_em)
             VALUES (:tenant_id, :xp, NOW())
             ON DUPLICATE KEY UPDATE xp_total_kids = xp_total_kids + :xp_incremento, atualizado_em = NOW()'
        );
        $stmt->execute(['tenant_id' => $tenant->id, 'xp' => $xpGanho, 'xp_incremento' => $xpGanho]);
    }

    /**
     * Top igrejas por XP total das criancas - so nome da igreja e o
     * total, nunca detalhe de crianca nenhuma. So mostrado quando a
     * requisicao atual veio de uma igreja provisionada (mesma condicao
     * de somarXp()), ja que fora desse cenario a tabela nem existe.
     *
     * @return array<int, array{nomeIgreja: string, xpTotal: int, souEu: bool}>
     */
    public static function topIgrejas(int $limite = 10): array
    {
        $tenantAtual = TenantResolver::atual();

        if ($tenantAtual === null) {
            return [];
        }

        $stmt = Database::central()->prepare(
            'SELECT t.id, t.nome_igreja, r.xp_total_kids
             FROM plataforma_kids_ranking r
             INNER JOIN plataforma_tenants t ON t.id = r.tenant_id
             WHERE t.status = "ativo"
             ORDER BY r.xp_total_kids DESC
             LIMIT :limite'
        );
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            static fn (array $row) => [
                'nomeIgreja' => (string) $row['nome_igreja'],
                'xpTotal' => (int) $row['xp_total_kids'],
                'souEu' => (int) $row['id'] === $tenantAtual->id,
            ],
            $stmt->fetchAll()
        );
    }
}
