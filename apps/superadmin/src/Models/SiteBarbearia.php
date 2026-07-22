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
    private const SELECT_BASE = 'SELECT id, nome, slug, plano, metodo_pagamento, status,
        trial_expira_em, proximo_vencimento, ultimo_acesso_em, created_at
        FROM barbearias';

    public function __construct(
        public readonly int $id,
        public readonly string $nome,
        public readonly string $slug,
        public readonly string $plano,
        public readonly string $metodoPagamento,
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
     * Libera/estende manualmente o acesso de uma barbearia. Sempre
     * reativa (status = 'ativo') e desfaz um cancelamento self-service
     * pendente (cancelado_em), senao o cron
     * suspender_assinaturas_canceladas.php suspenderia de novo assim
     * que o ciclo pago encerrar - e so entao ataca o campo que de fato
     * bloqueia cada metodo de pagamento: trial_expira_em (trial) ou a
     * fatura Pix mais recente (pix). Quem paga por cartao so depende do
     * proprio status (ver AuthMiddleware), entao reativar ja basta.
     */
    public static function estenderAcesso(int $id, int $dias): string
    {
        $barbearia = self::find($id);

        if ($barbearia === null) {
            return 'Barbearia nao encontrada.';
        }

        $agora = new \DateTimeImmutable();

        $stmt = DatabaseBarbearias::connection()->prepare(
            "UPDATE barbearias SET status = 'ativo', cancelado_em = NULL WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);

        if ($barbearia->metodoPagamento === 'trial') {
            $base = $barbearia->trialExpiraEm !== null ? new \DateTimeImmutable($barbearia->trialExpiraEm) : $agora;
            $novaData = ($base > $agora ? $base : $agora)->modify("+{$dias} days");

            $stmt = DatabaseBarbearias::connection()->prepare(
                'UPDATE barbearias SET trial_expira_em = :data WHERE id = :id'
            );
            $stmt->execute(['data' => $novaData->format('Y-m-d H:i:s'), 'id' => $id]);

            return 'Teste gratis estendido ate ' . $novaData->format('d/m/Y') . '.';
        }

        if ($barbearia->metodoPagamento === 'pix') {
            $stmt = DatabaseBarbearias::connection()->prepare(
                'SELECT id, vencimento FROM barbearia_faturas WHERE barbearia_id = :barbearia_id ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['barbearia_id' => $id]);
            $fatura = $stmt->fetch();

            if ($fatura === false) {
                return 'Site ativado. Nenhuma fatura Pix encontrada pra estender o vencimento.';
            }

            $vencimentoAtual = new \DateTimeImmutable($fatura['vencimento']);
            $novaData = ($vencimentoAtual > $agora ? $vencimentoAtual : $agora)->modify("+{$dias} days");

            $stmt = DatabaseBarbearias::connection()->prepare(
                "UPDATE barbearia_faturas SET status = 'paga', pago_em = NOW(), vencimento = :vencimento WHERE id = :id"
            );
            $stmt->execute(['vencimento' => $novaData->format('Y-m-d H:i:s'), 'id' => $fatura['id']]);

            $stmt = DatabaseBarbearias::connection()->prepare(
                'UPDATE barbearias SET proximo_vencimento = :data WHERE id = :id'
            );
            $stmt->execute(['data' => $novaData->format('Y-m-d'), 'id' => $id]);

            return 'Fatura Pix marcada como paga - proximo vencimento em ' . $novaData->format('d/m/Y') . '.';
        }

        return 'Site ativado com sucesso.';
    }

    /**
     * Situacao REAL de acesso da barbearia - diferente do campo
     * `status` sozinho, reproduz a mesma decisao que
     * Barbearias\Core\Middleware\AuthMiddleware usa pra bloquear o
     * dashboard (ver Barbearia::STATUS_SUSPENSA/STATUS_PENDENTE, trial
     * vencido ou fatura Pix vencida). Cartao so depende do proprio
     * `status` (a barbearia nao guarda historico de status de
     * assinatura de cartao como o Igrejas faz).
     *
     * @return array{bloqueado: bool, motivo: string, vencimento: ?string}
     */
    public function acesso(): array
    {
        if ($this->status === 'suspenso') {
            return ['bloqueado' => true, 'motivo' => 'Suspenso', 'vencimento' => $this->proximoVencimento];
        }

        if ($this->status === 'pendente') {
            return ['bloqueado' => true, 'motivo' => 'Pagamento inicial pendente', 'vencimento' => null];
        }

        if ($this->metodoPagamento === 'trial') {
            $vencido = $this->trialExpiraEm !== null && new \DateTimeImmutable() > new \DateTimeImmutable($this->trialExpiraEm);

            return [
                'bloqueado' => $vencido,
                'motivo' => $vencido ? 'Teste gratis vencido' : 'Em teste gratis',
                'vencimento' => $this->trialExpiraEm,
            ];
        }

        if ($this->metodoPagamento === 'pix') {
            $stmt = DatabaseBarbearias::connection()->prepare(
                'SELECT status, vencimento FROM barbearia_faturas WHERE barbearia_id = :barbearia_id ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['barbearia_id' => $this->id]);
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

        return ['bloqueado' => false, 'motivo' => 'Cartao (assinatura MP ativa)', 'vencimento' => $this->proximoVencimento];
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
            metodoPagamento: $row['metodo_pagamento'],
            status: $row['status'],
            trialExpiraEm: $row['trial_expira_em'],
            proximoVencimento: $row['proximo_vencimento'],
            ultimoAcessoEm: $row['ultimo_acesso_em'],
            criadoEm: $row['created_at'],
        );
    }
}
