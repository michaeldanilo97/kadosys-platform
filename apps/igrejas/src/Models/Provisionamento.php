<?php

declare(strict_types=1);

namespace Igrejas\Models;

use Igrejas\Core\Database;

/**
 * Cada tentativa de cadastro publico (igreja + administrador + plano,
 * ver Igrejas\Controllers\CadastroController), desde antes do pagamento
 * ser confirmado ate o banco da nova igreja estar pronto - ou ter dado
 * erro, com a mensagem registrada pra diagnostico.
 */
final class Provisionamento
{
    public function __construct(
        public readonly int $id,
        public readonly string $nomeIgreja,
        public readonly string $slug,
        public readonly string $adminNome,
        public readonly string $adminEmail,
        public readonly string $adminSenhaHash,
        public readonly string $plano,
        public readonly ?string $mpPreapprovalId,
        public readonly string $status,
        public readonly ?string $erroMensagem,
        public readonly ?int $tenantId,
    ) {
    }

    public static function criar(
        string $nomeIgreja,
        string $slug,
        string $adminNome,
        string $adminEmail,
        string $adminSenhaHash,
        string $plano,
    ): int {
        $stmt = Database::central()->prepare(
            'INSERT INTO plataforma_provisionamentos
                (nome_igreja, slug, admin_nome, admin_email, admin_senha_hash, plano, status)
             VALUES
                (:nome_igreja, :slug, :admin_nome, :admin_email, :admin_senha_hash, :plano, "aguardando_pagamento")'
        );
        $stmt->execute([
            'nome_igreja' => $nomeIgreja,
            'slug' => $slug,
            'admin_nome' => $adminNome,
            'admin_email' => $adminEmail,
            'admin_senha_hash' => $adminSenhaHash,
            'plano' => $plano,
        ]);

        return (int) Database::central()->lastInsertId();
    }

    /**
     * Verifica se algum cadastro em andamento (nao finalizado nem com
     * erro) ja reservou esse slug - reduz (nao elimina 100%, dado que
     * nao ha lock/transacao aqui) a chance de dois cadastros simultaneos
     * disputarem o mesmo subdominio antes do pagamento confirmar.
     */
    public static function slugReservado(string $slug): bool
    {
        $stmt = Database::central()->prepare(
            "SELECT 1 FROM plataforma_provisionamentos
             WHERE slug = :slug AND status IN ('aguardando_pagamento', 'pago_aguardando_provisionamento', 'provisionando')
             LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() !== false;
    }

    /**
     * Reivindica o processamento deste provisionamento de forma atomica:
     * so avanca o status para "pago_aguardando_provisionamento" se ele
     * ainda estiver em "aguardando_pagamento". Evita que duas entregas
     * duplicadas do mesmo webhook do Mercado Pago (comum, eles
     * reenviam em caso de qualquer duvida) disparem o provisionamento
     * (criacao de banco/subdominio) duas vezes em paralelo.
     */
    public static function reivindicarProcessamento(int $id): bool
    {
        $stmt = Database::central()->prepare(
            "UPDATE plataforma_provisionamentos
             SET status = 'pago_aguardando_provisionamento'
             WHERE id = :id AND status = 'aguardando_pagamento'"
        );
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public static function definirPreapprovalId(int $id, string $mpPreapprovalId): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_provisionamentos SET mp_preapproval_id = :mp_preapproval_id WHERE id = :id'
        );
        $stmt->execute(['mp_preapproval_id' => $mpPreapprovalId, 'id' => $id]);
    }

    public static function buscarPorPreapprovalId(string $mpPreapprovalId): ?self
    {
        $stmt = Database::central()->prepare(
            'SELECT id, nome_igreja, slug, admin_nome, admin_email, admin_senha_hash, plano,
                    mp_preapproval_id, status, erro_mensagem, tenant_id
             FROM plataforma_provisionamentos WHERE mp_preapproval_id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $mpPreapprovalId]);
        $row = $stmt->fetch();

        return $row === false ? null : self::fromRow($row);
    }

    public static function atualizarStatus(int $id, string $status, ?string $erroMensagem = null): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_provisionamentos SET status = :status, erro_mensagem = :erro_mensagem WHERE id = :id'
        );
        $stmt->execute(['status' => $status, 'erro_mensagem' => $erroMensagem, 'id' => $id]);
    }

    public static function vincularTenant(int $id, int $tenantId): void
    {
        $stmt = Database::central()->prepare(
            'UPDATE plataforma_provisionamentos SET tenant_id = :tenant_id, status = "concluido" WHERE id = :id'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'id' => $id]);
    }

    private static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            nomeIgreja: $row['nome_igreja'],
            slug: $row['slug'],
            adminNome: $row['admin_nome'],
            adminEmail: $row['admin_email'],
            adminSenhaHash: $row['admin_senha_hash'],
            plano: $row['plano'],
            mpPreapprovalId: $row['mp_preapproval_id'],
            status: $row['status'],
            erroMensagem: $row['erro_mensagem'],
            tenantId: $row['tenant_id'] !== null ? (int) $row['tenant_id'] : null,
        );
    }
}
