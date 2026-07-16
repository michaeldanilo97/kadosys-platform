<?php

declare(strict_types=1);

namespace Superadmin\Models;

use Superadmin\Core\DatabaseIgrejas;

/**
 * Leitura (e exclusao do registro central) da tabela plataforma_tenants
 * do KADOSYS Igrejas - cada linha e uma igreja com banco de dados
 * proprio, isolado (ver Igrejas\Models\Tenant, a fonte original deste
 * schema). O Super Admin so LE esse banco central pra listar/suspender/
 * excluir; nao duplica a logica de provisionamento.
 */
final class SiteIgreja
{
    private const SELECT_BASE = 'SELECT id, slug, nome_igreja, subdominio, plano, status,
        trial_expira_em, proximo_vencimento, ultimo_acesso_em, created_at, db_name, db_user
        FROM plataforma_tenants';

    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $nomeIgreja,
        public readonly string $subdominio,
        public readonly string $plano,
        public readonly string $status,
        public readonly ?string $trialExpiraEm,
        public readonly ?string $proximoVencimento,
        public readonly ?string $ultimoAcessoEm,
        public readonly string $criadoEm,
        public readonly string $dbName,
        public readonly string $dbUser,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function listarTodas(): array
    {
        $stmt = DatabaseIgrejas::connection()->query(self::SELECT_BASE . ' ORDER BY id DESC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = DatabaseIgrejas::connection()->prepare(self::SELECT_BASE . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function atualizarStatus(int $id, string $status): void
    {
        $stmt = DatabaseIgrejas::connection()->prepare(
            'UPDATE plataforma_tenants SET status = :status WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    /**
     * So remove o registro central - a exclusao do banco de dados/
     * usuario MySQL da igreja (cPanel) e feita a parte, ANTES desta
     * chamada, por Superadmin\Core\Desprovisionador.
     */
    public static function excluir(int $id): void
    {
        $stmt = DatabaseIgrejas::connection()->prepare('DELETE FROM plataforma_tenants WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            slug: $row['slug'],
            nomeIgreja: $row['nome_igreja'],
            subdominio: $row['subdominio'],
            plano: $row['plano'],
            status: $row['status'],
            trialExpiraEm: $row['trial_expira_em'],
            proximoVencimento: $row['proximo_vencimento'],
            ultimoAcessoEm: $row['ultimo_acesso_em'],
            criadoEm: $row['created_at'],
            dbName: $row['db_name'],
            dbUser: $row['db_user'],
        );
    }
}
