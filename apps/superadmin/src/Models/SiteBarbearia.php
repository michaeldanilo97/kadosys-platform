<?php

declare(strict_types=1);

namespace Superadmin\Models;

use Superadmin\Core\DatabaseBarbearias;

/**
 * Leitura (e exclusao) da tabela barbearias do KADOSYS Barbearias - banco
 * unico compartilhado, isolamento logico por barbearia_id (ver
 * Barbearias\Models\Barbearia, a fonte original deste schema). Excluir
 * uma linha aqui e suficiente: todo o resto (agendamentos, clientes,
 * financeiro etc.) cai em cascata via FK ON DELETE CASCADE.
 */
final class SiteBarbearia
{
    private const SELECT_BASE = 'SELECT id, nome, slug, plano, status,
        trial_expira_em, proximo_vencimento, ultimo_acesso_em, created_at
        FROM barbearias';

    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $slug,
        public readonly string $plano,
        public readonly string $status,
        public readonly ?string $trialExpiraEm,
        public readonly ?string $proximoVencimento,
        public readonly ?string $ultimoAcessoEm,
        public readonly string $criadoEm,
    ) {
    }

    /**
     * @return array<int, self>
     */
    public static function listarTodas(): array
    {
        $stmt = DatabaseBarbearias::connection()->query(self::SELECT_BASE . ' ORDER BY id DESC');

        return array_map(self::fromRow(...), $stmt->fetchAll());
    }

    public static function find(int $id): ?self
    {
        $stmt = DatabaseBarbearias::connection()->prepare(self::SELECT_BASE . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? self::fromRow($row) : null;
    }

    public static function atualizarStatus(int $id, string $status): void
    {
        $stmt = DatabaseBarbearias::connection()->prepare(
            'UPDATE barbearias SET status = :status WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => $status]);
    }

    /**
     * A propria linha - o cascade das FKs cuida do resto (ver
     * install.sql do apps/barbearias).
     */
    public static function excluir(int $id): void
    {
        $stmt = DatabaseBarbearias::connection()->prepare('DELETE FROM barbearias WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nome: $row['nome'],
            slug: $row['slug'],
            plano: $row['plano'],
            status: $row['status'],
            trialExpiraEm: $row['trial_expira_em'],
            proximoVencimento: $row['proximo_vencimento'],
            ultimoAcessoEm: $row['ultimo_acesso_em'],
            criadoEm: $row['created_at'],
        );
    }
}
