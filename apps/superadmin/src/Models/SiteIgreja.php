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
    private const SELECT_BASE = 'SELECT id, slug, nome_igreja, subdominio, plano, metodo_pagamento, status,
        trial_expira_em, proximo_vencimento, ultimo_acesso_em, created_at, db_name, db_user
        FROM plataforma_tenants';

    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $nomeIgreja,
        public readonly string $subdominio,
        public readonly string $plano,
        public readonly string $metodoPagamento,
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
     * Libera/estende manualmente o acesso de uma igreja, sem depender
     * do proprio fluxo de pagamento (trial vencido, cartao recusado ou
     * fatura Pix nao paga) - usado quando o dono da plataforma precisa
     * ativar ou dar mais prazo pra uma igreja na mao.
     *
     * Nao mexe em `status` (ver AuthMiddleware do Igrejas: esse campo e
     * usado por TenantResolver pra decidir se troca de banco, e o
     * bloqueio de acesso por pagamento e decidido separadamente, por
     * metodo_pagamento) - so ataca o campo que de fato desbloqueia cada
     * metodo: trial_expira_em (trial), a assinatura de cartao mais
     * recente (cartao) ou a fatura Pix mais recente (pix).
     */
    public static function estenderAcesso(int $id, int $dias): string
    {
        $tenant = self::find($id);

        if ($tenant === null) {
            return 'Igreja nao encontrada.';
        }

        $agora = new \DateTimeImmutable();

        if ($tenant->metodoPagamento === 'trial') {
            $base = $tenant->trialExpiraEm !== null ? new \DateTimeImmutable($tenant->trialExpiraEm) : $agora;
            $novaData = ($base > $agora ? $base : $agora)->modify("+{$dias} days");

            $stmt = DatabaseIgrejas::connection()->prepare(
                'UPDATE plataforma_tenants SET trial_expira_em = :data WHERE id = :id'
            );
            $stmt->execute(['data' => $novaData->format('Y-m-d H:i:s'), 'id' => $id]);

            return 'Teste gratis estendido ate ' . $novaData->format('d/m/Y') . '.';
        }

        if ($tenant->metodoPagamento === 'cartao') {
            $stmt = DatabaseIgrejas::connection()->prepare(
                'SELECT id FROM plataforma_assinaturas WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['tenant_id' => $id]);
            $assinaturaId = $stmt->fetchColumn();

            if ($assinaturaId === false) {
                return 'Nenhuma assinatura de cartao encontrada para essa igreja - nada foi alterado.';
            }

            $stmt = DatabaseIgrejas::connection()->prepare(
                "UPDATE plataforma_assinaturas SET status = 'autorizada' WHERE id = :id"
            );
            $stmt->execute(['id' => $assinaturaId]);

            return 'Assinatura de cartao marcada como autorizada - acesso liberado.';
        }

        if ($tenant->metodoPagamento === 'pix') {
            $stmt = DatabaseIgrejas::connection()->prepare(
                'SELECT id, vencimento FROM plataforma_faturas WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['tenant_id' => $id]);
            $fatura = $stmt->fetch();

            if ($fatura === false) {
                return 'Nenhuma fatura Pix encontrada para essa igreja - nada foi alterado.';
            }

            $vencimentoAtual = new \DateTimeImmutable($fatura['vencimento']);
            $novaData = ($vencimentoAtual > $agora ? $vencimentoAtual : $agora)->modify("+{$dias} days");

            $stmt = DatabaseIgrejas::connection()->prepare(
                "UPDATE plataforma_faturas SET status = 'paga', pago_em = NOW(), vencimento = :vencimento WHERE id = :id"
            );
            $stmt->execute(['vencimento' => $novaData->format('Y-m-d H:i:s'), 'id' => $fatura['id']]);

            $stmt = DatabaseIgrejas::connection()->prepare(
                'UPDATE plataforma_tenants SET proximo_vencimento = :data WHERE id = :id'
            );
            $stmt->execute(['data' => $novaData->format('Y-m-d'), 'id' => $id]);

            return 'Fatura Pix marcada como paga - proximo vencimento em ' . $novaData->format('d/m/Y') . '.';
        }

        return 'Metodo de pagamento desconhecido - nada foi alterado.';
    }

    /**
     * Situacao REAL de acesso da igreja - diferente do campo `status`
     * (que so controla provisionamento/roteamento de subdominio, ver
     * TenantResolver), reproduz a mesma decisao que
     * Igrejas\Core\Middleware\AuthMiddleware usa pra bloquear o
     * dashboard: trial vencido, cartao pausado/cancelado ou fatura Pix
     * vencida. Uma igreja pode aparecer com `status = 'ativo'` aqui e
     * ainda assim estar com o painel bloqueado pro cliente - e
     * exatamente esse caso que este metodo expoe.
     *
     * @return array{bloqueado: bool, motivo: string, vencimento: ?string}
     */
    public function acesso(): array
    {
        if ($this->status !== 'ativo') {
            return ['bloqueado' => true, 'motivo' => 'Site ' . $this->status, 'vencimento' => null];
        }

        if ($this->metodoPagamento === 'trial') {
            $vencido = $this->trialExpiraEm !== null && new \DateTimeImmutable() > new \DateTimeImmutable($this->trialExpiraEm);

            return [
                'bloqueado' => $vencido,
                'motivo' => $vencido ? 'Teste gratis vencido' : 'Em teste gratis',
                'vencimento' => $this->trialExpiraEm,
            ];
        }

        if ($this->metodoPagamento === 'cartao') {
            $stmt = DatabaseIgrejas::connection()->prepare(
                'SELECT status FROM plataforma_assinaturas WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['tenant_id' => $this->id]);
            $status = $stmt->fetchColumn();

            if ($status === false) {
                return ['bloqueado' => false, 'motivo' => 'Sem assinatura de cartao ainda', 'vencimento' => null];
            }

            $bloqueado = in_array($status, ['pausada', 'cancelada'], true);

            return [
                'bloqueado' => $bloqueado,
                'motivo' => 'Cartao ' . $status,
                'vencimento' => $this->proximoVencimento,
            ];
        }

        if ($this->metodoPagamento === 'pix') {
            $stmt = DatabaseIgrejas::connection()->prepare(
                'SELECT status, vencimento FROM plataforma_faturas WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['tenant_id' => $this->id]);
            $fatura = $stmt->fetch();

            if ($fatura === false) {
                return ['bloqueado' => false, 'motivo' => 'Sem fatura Pix ainda', 'vencimento' => null];
            }

            $vencida = $fatura['status'] === 'expirada'
                || ($fatura['status'] === 'pendente' && new \DateTimeImmutable() > new \DateTimeImmutable($fatura['vencimento']));

            return [
                'bloqueado' => $vencida,
                'motivo' => $vencida ? 'Fatura Pix vencida' : ($fatura['status'] === 'paga' ? 'Pix em dia' : 'Pix pendente'),
                'vencimento' => $fatura['vencimento'],
            ];
        }

        return ['bloqueado' => false, 'motivo' => 'OK', 'vencimento' => null];
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
            metodoPagamento: $row['metodo_pagamento'],
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
